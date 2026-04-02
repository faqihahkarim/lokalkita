<?php
session_start();
require 'script/db.php'; 

// ==========================================
// 0. HANDLE AJAX ACTIONS (save, bucket, review)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please log in first.']);
        exit;
    }

    $user_id = intval($_SESSION['user_id']);
    $exp_id  = intval($_POST['exp_id'] ?? 0);
    $action  = $_POST['action'];

    if ($exp_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid experience.']);
        exit;
    }

    if ($action === 'save') {
        // toggle save in user_save table
        $check = $conn->prepare("SELECT 1 FROM user_save WHERE user_id = ? AND exp_id = ?");
        $check->bind_param("ii", $user_id, $exp_id);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;

        if ($exists) {
            $del = $conn->prepare("DELETE FROM user_save WHERE user_id = ? AND exp_id = ?");
            $del->bind_param("ii", $user_id, $exp_id);
            $del->execute();
            echo json_encode(['success' => true, 'saved' => false]);
        } else {
            $ins = $conn->prepare("INSERT INTO user_save (user_id, exp_id) VALUES (?, ?)");
            $ins->bind_param("ii", $user_id, $exp_id);
            $ins->execute();
            echo json_encode(['success' => true, 'saved' => true]);
        }
        exit;
    }

    if ($action === 'bucket') {
        // toggle in bucketlist (status simple 'Active')
        $check = $conn->prepare("SELECT 1 FROM bucketlist WHERE user_id = ? AND exp_id = ?");
        $check->bind_param("ii", $user_id, $exp_id);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;

        if ($exists) {
            $del = $conn->prepare("DELETE FROM bucketlist WHERE user_id = ? AND exp_id = ?");
            $del->bind_param("ii", $user_id, $exp_id);
            $del->execute();
            echo json_encode(['success' => true, 'added' => false]);
        } else {
            $ins = $conn->prepare("INSERT INTO bucketlist (user_id, exp_id, status) VALUES (?, ?, 'Active')");
            $ins->bind_param("ii", $user_id, $exp_id);
            $ins->execute();
            echo json_encode(['success' => true, 'added' => true]);
        }
        exit;
    }

    if ($action === 'review') {
        $rating = intval($_POST['rating'] ?? 0);
        $review_text = trim($_POST['review'] ?? '');

        if ($rating < 1 || $rating > 5 || $review_text === '') {
            echo json_encode(['success' => false, 'message' => 'Please provide rating and review text.']);
            exit;
        }

        $ins = $conn->prepare("
            INSERT INTO review (exp_id, user_id, review, rating, date_create)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $ins->bind_param("iisi", $exp_id, $user_id, $review_text, $rating);
        $ins->execute();

        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

// ==========================================
// 1. LOAD EXPERIENCE (GET REQUEST)
// ==========================================
if (!isset($_GET['exp_id'])) {
    die("Experience not found.");
}
$exp_id = intval($_GET['exp_id']);

$expQ = $conn->prepare("
    SELECT exp_id, host_id, exp_title, exp_desc, tags, category, state, location,
           min_price, max_price, open_day, close_day, operating_hours,
           contact_info, web_link, rating
    FROM experience
    WHERE exp_id = ?
");
$expQ->bind_param("i", $exp_id);
$expQ->execute();
$experience = $expQ->get_result()->fetch_assoc();

if (!$experience) {
    die("Experience not found in database.");
}

// ==========================================
// 2. HOST INFO
// ==========================================
$host_name = "Unknown Host";
$host_contact = $experience['contact_info']; // fallback to experience contact

// Check if host_id exists in the experience table
if (!empty($experience['host_id'])) {
    $hostQ = $conn->prepare("SELECT username, phone_num, profile_pic FROM host_acc WHERE host_id = ?");
    $hostQ->bind_param("i", $experience['host_id']);
    $hostQ->execute();
    $host = $hostQ->get_result()->fetch_assoc();

    if ($host) {
        // Use host's username if available
        $host_name = isset($host['username']) ? htmlspecialchars($host['username']) : $host_name;

        // Use host's phone number if available
        if (!empty($host['phone_num'])) {
            $host_contact = $host['phone_num'];
        }

        // Use host's profile picture if available, otherwise default
        $profile_pic = !empty($host['profile_pic']) ? "uploads/profile/" . $host['profile_pic'] : "img/default_profile.png";
    }
}




// ==========================================
// 3. IMAGES
// ==========================================
$imgQ = $conn->prepare("
    SELECT img_path 
    FROM experience_images 
    WHERE exp_id = ?
    ORDER BY seq_no ASC
");
$imgQ->bind_param("i", $exp_id);
$imgQ->execute();
$imgRes = $imgQ->get_result();

$images = [];
while ($row = $imgRes->fetch_assoc()) {
    $images[] = "../host/uploads/experience/" . $row['img_path'];
}

if (empty($images)) {
    $images[] = "../pic/default.png";
}

$background_image = $images[0];

// ==========================================
// 4. CHECK IF CURRENT USER HAS SAVED / BUCKET
// ==========================================
$user_id = $_SESSION['user_id'] ?? null;
$is_saved = false;
$is_in_bucket = false;

if ($user_id) {
    $s = $conn->prepare("SELECT 1 FROM user_save WHERE user_id = ? AND exp_id = ?");
    $s->bind_param("ii", $user_id, $exp_id);
    $s->execute();
    $is_saved = $s->get_result()->num_rows > 0;

    $b = $conn->prepare("SELECT 1 FROM bucketlist WHERE user_id = ? AND exp_id = ?");
    $b->bind_param("ii", $user_id, $exp_id);
    $b->execute();
    $is_in_bucket = $b->get_result()->num_rows > 0;
}

// ==========================================
// 5. REVIEWS
// ==========================================
$revQ = $conn->prepare("
    SELECT r.review_id, r.review, r.rating, r.date_create,
           u.username
    FROM review r
    JOIN user_acc u ON r.user_id = u.user_id
    WHERE r.exp_id = ?
    ORDER BY r.date_create DESC
");
$revQ->bind_param("i", $exp_id);
$revQ->execute();
$reviewRes = $revQ->get_result();

$top_reviews = [];
while ($r = $reviewRes->fetch_assoc()) {
    $top_reviews[] = $r;
}

// ==========================================
// 6. YOU MAY ALSO LIKE (same category)
// ==========================================
$recQ = $conn->prepare("
    SELECT e.exp_id, e.exp_title, e.state, e.category, e.min_price, e.rating,
           (
             SELECT img_path FROM experience_images ei
             WHERE ei.exp_id = e.exp_id
             ORDER BY seq_no ASC LIMIT 1
           ) AS main_img
    FROM experience e
    WHERE e.exp_id != ? AND e.category = ?
    LIMIT 6
");
$recQ->bind_param("is", $exp_id, $experience['category']);
$recQ->execute();
$recRes = $recQ->get_result();

$recommended = [];
while ($row = $recRes->fetch_assoc()) {
    $row['img'] = $row['main_img']
        ? "../host/uploads/experience/" . $row['main_img']
        : "../pic/default_exp.jpg";
    $recommended[] = $row;
}


$likedIds = [];
$savedIds = [];

// Liked
$lk = $conn->prepare("SELECT exp_id FROM user_like WHERE user_id = ?");
$lk->bind_param("i", $_SESSION['user_id']);
$lk->execute();
$lkRes = $lk->get_result();
while ($r = $lkRes->fetch_assoc()) { $likedIds[] = $r['exp_id']; }

// Saved
$sv = $conn->prepare("SELECT exp_id FROM user_save WHERE user_id = ?");
$sv->bind_param("i", $_SESSION['user_id']);
$sv->execute();
$svRes = $sv->get_result();
while ($r = $svRes->fetch_assoc()) { $savedIds[] = $r['exp_id']; }

?>






<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($experience["exp_title"]); ?></title>

    <link rel="stylesheet" href="experience-detail.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />

    <style>
        body {
            background-image: url('<?= $background_image ?>');
        }
    </style>
</head>

<body>

    <!-- NAV -->
    <nav class="detail-nav">
        <span class="logo">LokalKita</span>
        <div class="nav-links">
            <a href="main_page.php">Browse</a>
            <a href="user_dashboard.php">Profile</a>
            
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="detail-container">

        <!-- LEFT IMAGE SLIDER -->
        <div class="image-section">

            <div class="main-image">
                <img id="mainPreview" src="<?= $background_image ?>">
            </div>

            <div class="thumbnail-row">
                <?php foreach ($images as $img): ?>
                    <img class="thumb" src="<?= $img ?>" onclick="changeImage('<?= $img ?>')">
                <?php endforeach; ?>
            </div>

        </div>

        <!-- RIGHT INFORMATION PANEL -->
        <div class="info-section">

            <h1><?= htmlspecialchars($experience["exp_title"]); ?></h1>

            <div class="category"><?= htmlspecialchars($experience["category"]); ?></div>

            <div class="price-rating">
                <span class="price">RM <?= number_format($experience["min_price"], 0); ?> - RM <?= number_format($experience["max_price"], 0); ?></span>
                <span class="stars">
                    <?php
                    $starCount = (int)round($experience["rating"] ?? 0);
                    for ($i = 0; $i < $starCount; $i++): ?>
                        <i class="fa-solid fa-star"></i>
                    <?php endfor; ?>
                </span>
            </div>

            <p class="description"><?= nl2br(htmlspecialchars($experience["exp_desc"])); ?></p>

            <ul class="meta-list">
                <li><strong>Location:</strong> <?= htmlspecialchars($experience["location"]); ?>, <?= htmlspecialchars($experience["state"]); ?></li>
                <li><strong>Tags:</strong> <?= htmlspecialchars($experience["tags"]); ?></li>
                <li><strong>Operating Hours:</strong> <?= htmlspecialchars($experience["operating_hours"]); ?></li>
                <li><strong>Operating Days:</strong> <?= htmlspecialchars($experience["open_day"]); ?></li>
                <li><strong>Closing Days:</strong> <?= htmlspecialchars($experience["close_day"]); ?></li>
            </ul>

            <h3 class="host-title">Hosted by</h3>

            <div class="host-box">
                <p>
                    <strong>
                        <a href="hostprofile.php?id=<?= (int)$experience['host_id']; ?>&return=<?= urlencode($_SERVER['REQUEST_URI']); ?>"
                            class="host-link">
                            <?= htmlspecialchars($host_name); ?>
                        </a>


                    </strong>
                </p>

                <p>📞 <?= htmlspecialchars($host_contact); ?></p>
                <?php if (!empty($experience["web_link"])): ?>
                    <a href="<?= htmlspecialchars($experience["web_link"]); ?>" target="_blank" style="text-decoration: none; color: #ff652a;">More Info</a>
                <?php endif; ?>
            </div>

            

            <div class="action-row">
                <button class="save-btn<?= $is_saved ? ' active' : '' ?>" id="saveBtn">
                    <i class="fa-regular fa-bookmark"></i> Save
                </button>
                <button class="bucket-btn<?= $is_in_bucket ? ' active' : '' ?>" id="bucketBtn">
                    <i class="fa-solid fa-list-check"></i> Add to Bucket List
                </button>
            </div>

        </div><!-- END INFO SECTION -->

    </div><!-- END DETAIL CONTAINER -->



    <!-- ======================= -->
    <!--   USER REVIEWS SECTION   -->
    <!-- ======================= -->
    <section class="reviews-section fade-in-up">

        <h2 class="review-title">What Travelers Say</h2>

        <div class="review-list" id="reviewList">
            <?php if (empty($top_reviews)): ?>
                <p class="no-review">No reviews yet. Be the first to share your experience!</p>
            <?php else: ?>
                <?php foreach ($top_reviews as $index => $r): ?>
                    <div class="review-card review-item <?= $index >= 8 ? 'hidden-review' : '' ?>">
                        <div class="review-header">
                            <strong><?= htmlspecialchars($r["username"]); ?></strong>
                            <span class="review-stars">
                                <?php for ($i=0; $i < $r["rating"]; $i++): ?>
                                    <i class="fa-solid fa-star"></i>
                                <?php endfor; ?>
                            </span>
                        </div>
                        <p class="review-text"><?= nl2br(htmlspecialchars($r["review"])); ?></p>
                        <p class="review-date"><?= htmlspecialchars($r["date_create"]); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (count($top_reviews) > 10): ?>
            <button id="loadMoreBtn" class="load-more-btn">Load More Reviews</button>
        <?php endif; ?>

        <!-- Review Form -->
        <div class="add-review-box">
            <h3>Rate Your Experience</h3>
            <form id="reviewForm" class="review-form">
                <div class="star-input">
                    <i class="fa-regular fa-star" data-value="1"></i>
                    <i class="fa-regular fa-star" data-value="2"></i>
                    <i class="fa-regular fa-star" data-value="3"></i>
                    <i class="fa-regular fa-star" data-value="4"></i>
                    <i class="fa-regular fa-star" data-value="5"></i>
                </div>

                <input type="hidden" name="rating" id="ratingInput" value="0">
                <textarea name="review" id="reviewText" class="review-textarea" placeholder="Share your experience..." required></textarea>
                <button type="submit" class="submit-review-btn">Submit Review</button>
            </form>
        </div>

    </section>


<!-- ======================= -->
<!--  YOU MAY ALSO LIKE       -->
<!-- ======================= -->
<section class="recommend-section fade-in-up">

    <h2 class="recommend-title">You May Also Like</h2>

    <div class="recommend-row">
        <?php if (empty($recommended)): ?>
            <p>No similar experiences yet.</p>
        <?php else: ?>
            <?php foreach ($recommended as $rec): ?>

                <?php
                    // Check if this experience is liked or saved
                    $liked = in_array($rec['exp_id'], $likedIds);
                    $saved = in_array($rec['exp_id'], $savedIds);
                ?>

                <div class="rec-card hover-elevate">

                    <!-- Make the card clickable EXCEPT buttons -->
                    <a href="experience-detail.php?exp_id=<?= $rec['exp_id']; ?>" class="rec-link-area">

                        <div class="rec-tag" style="background-color: #373736e5;">
                            <?= htmlspecialchars($rec["category"]); ?>
                        </div>

                        <img 
                            src="<?= $rec['img']; ?>" 
                            class="rec-img"
                            onerror="this.onerror=null; this.src='../pic/default.png';"
                        >

                        <div class="rec-body">
                            <h4 style="font-color: #454545ff; margin-bottom: 10px;"><?= htmlspecialchars($rec["exp_title"]); ?></h4>

                            <p class="rec-location">
                                <i class="fa-solid fa-location-dot" style="color:#ff1d19ff;"></i> 
                                <?= htmlspecialchars($rec["state"]); ?>
                                <span> • RM<?= number_format($rec["min_price"], 0); ?></span>
                            </p>

                            <p class="rec-rating">
                                <i class="fa-solid fa-star" style="color: #ffc107;"></i> 
                                <?= number_format($rec["rating"], 1); ?>
                            </p>
                        </div>

                    </a>

                    <!-- LIKE + SAVE BUTTONS -->
                    <div class="rec-actions">

                        <!-- LIKE -->
                        <button
                            class="like-btn <?= $liked ? 'active' : '' ?>" 
                            data-exp="<?= $rec['exp_id'] ?>"
                        >
                            <i class="<?= $liked ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                        </button>

                        <!-- SAVE -->
                        <button
                            class="save-btn <?= $saved ? 'active' : '' ?>" 
                            data-exp="<?= $rec['exp_id'] ?>"
                        >
                            <i class="<?= $saved ? 'fa-solid' : 'fa-regular' ?> fa-bookmark"></i>
                        </button>

                    </div>

                </div>

            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</section>



    <!-- FULLSCREEN IMAGE MODAL -->
    <div id="imageModal" class="modal" onclick="closeModal()">
        <img id="modalImage">
    </div>













    <!-- SCRIPTS -->
    <script>
        const expId = <?= $exp_id ?>;

        // Change main image when clicking thumbnail
        function changeImage(src) {
            document.getElementById("mainPreview").src = src;
        }

        // Open modal on main image click
        document.getElementById("mainPreview").onclick = function() {
            document.getElementById("modalImage").src = this.src;
            document.getElementById("imageModal").style.display = "flex";
        };

        function closeModal() {
            document.getElementById("imageModal").style.display = "none";
        }

        // Star rating input
        document.querySelectorAll('.star-input i').forEach(star => {
            star.addEventListener('click', function() {
                let rating = this.getAttribute('data-value');
                document.getElementById('ratingInput').value = rating;

                document.querySelectorAll('.star-input i').forEach(s => {
                    s.classList.remove('active');
                });

                for (let i = 0; i < rating; i++) {
                    document.querySelectorAll('.star-input i')[i].classList.add('active');
                }
            });
        });

        // Save button
        const saveBtn = document.getElementById('saveBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', function(e) {
                e.preventDefault();
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'save',
                        exp_id: expId
                    })
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        saveBtn.classList.toggle('active', data.saved);
                    } else {
                        alert(data.message || 'Error while saving.');
                    }
                });
            });
        }

        // Bucket button
        const bucketBtn = document.getElementById('bucketBtn');
        if (bucketBtn) {
            bucketBtn.addEventListener('click', function(e) {
                e.preventDefault();

                fetch(window.location.href, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'bucket',
                        exp_id: expId
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {

                        // make the logoc to state added if clicked the bucket button
                        if (data.added) {
                            bucketBtn.classList.add("active");
                            bucketBtn.innerHTML = `<i class="fa-solid fa-list-check"></i> Added to Bucket`;
                        } else {
                            bucketBtn.classList.remove("active");
                            bucketBtn.innerHTML = `<i class="fa-solid fa-list-check"></i> Add to Bucket List`;
                        }

                    } else {
                        alert(data.message || 'Error while updating bucket list.');
                    }
                });
            });
        }


        // Submit review
        const reviewForm = document.getElementById('reviewForm');
        if (reviewForm) {
            reviewForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const rating = document.getElementById('ratingInput').value;
                const reviewText = document.getElementById('reviewText').value.trim();

                fetch(window.location.href, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'review',
                        exp_id: expId,
                        rating: rating,
                        review: reviewText
                    })
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        alert('Review submitted!');
                        location.reload();
                    } else {
                        alert(data.message || 'Error submitting review.');
                    }
                });
            });
        }

        // Load more reviews
        document.addEventListener("DOMContentLoaded", () => {
            const loadMoreBtn = document.getElementById("loadMoreBtn");
            if (!loadMoreBtn) return;

            loadMoreBtn.addEventListener("click", () => {
                const hiddenReviews = document.querySelectorAll(".hidden-review");

                hiddenReviews.forEach(review => review.classList.remove("hidden-review"));

                loadMoreBtn.style.display = "none"; // hide button after showing all
            });
        });


        // LIKE HANDLER
        document.querySelectorAll(".like-btn").forEach(btn => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();

                const expId = btn.dataset.exp;

                fetch("script/like_save.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `exp_id=${expId}&type=like`
                })
                .then(r => r.json())
                .then(res => {
                    const icon = btn.querySelector("i");

                    if (res.status === "liked") {
                        btn.classList.add("active");
                        icon.classList.remove("fa-regular");
                        icon.classList.add("fa-solid");
                    } 
                    else if (res.status === "unliked") {
                        btn.classList.remove("active");
                        icon.classList.remove("fa-solid");
                        icon.classList.add("fa-regular");
                    }
                });
            });
        });


        // SAVE HANDLER
        document.querySelectorAll(".save-btn").forEach(btn => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();

                const expId = btn.dataset.exp;

                fetch("script/like_save.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `exp_id=${expId}&type=save`
                })
                .then(r => r.json())
                .then(res => {
                    const icon = btn.querySelector("i");

                    if (res.status === "saved") {
                        btn.classList.add("active");
                        icon.classList.remove("fa-regular");
                        icon.classList.add("fa-solid");
                    } 
                    else if (res.status === "unsaved") {
                        btn.classList.remove("active");
                        icon.classList.remove("fa-solid");
                        icon.classList.add("fa-regular");
                    }
                });
            });
        });

    </script>

    

</body>
</html>
