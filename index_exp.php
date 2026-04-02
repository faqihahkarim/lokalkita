<?php
session_start();
require 'db.php';

/* ==========================================
   1. LOAD EXPERIENCE
========================================== */
if (!isset($_GET['exp_id'])) {
    die("Experience not found.");
}
$exp_id = (int)$_GET['exp_id'];

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

/* ==========================================
   2. HOST INFO
========================================== */
$host_name = "Unknown Host";
$host_contact = $experience['contact_info'] ?? 'Not Provided';

if (!empty($experience['host_id'])) {
    $hostQ = $conn->prepare("
        SELECT username, phone_num, profile_pic 
        FROM host_acc 
        WHERE host_id = ?
    ");
    $hostQ->bind_param("i", $experience['host_id']);
    $hostQ->execute();
    $host = $hostQ->get_result()->fetch_assoc();

    if ($host) {
        $host_name = htmlspecialchars($host['username'] ?? $host_name);
        if (!empty($host['phone_num'])) {
            $host_contact = $host['phone_num'];
        }
    }
}

/* ==========================================
   3. EXPERIENCE IMAGES (FIXED PATH)
========================================== */
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
    // ✅ CORRECT PATH BASED ON YOUR DIRECTORY
    $images[] = "host/uploads/experience/" . $row['img_path'];
}

if (empty($images)) {
    $images[] = "pic/default.png";
}

$background_image = $images[0];

/* ==========================================
   4. REVIEWS
========================================== */
$revQ = $conn->prepare("
    SELECT r.review, r.rating, r.date_create, u.username
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($experience['exp_title']); ?></title>

<link rel="stylesheet" href="exp.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
    background-image: url('<?= htmlspecialchars($background_image); ?>');
}
</style>
</head>

<body>

<!-- NAV -->
<nav class="detail-nav">
    <span class="logo">LokalKita</span>
    <div class="nav-links">
        <a href="index.php">Experiences</a>
        <a href="login/login_type.php">Login</a>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="detail-container">

<!-- IMAGE SECTION -->
<div class="image-section">
    <div class="main-image">
        <img id="mainPreview"
             src="<?= htmlspecialchars($background_image); ?>"
             onerror="this.onerror=null; this.src='pic/default.png';">
    </div>

    <div class="thumbnail-row">
        <?php foreach ($images as $img): ?>
            <img class="thumb"
                 src="<?= htmlspecialchars($img); ?>"
                 onerror="this.onerror=null; this.src='pic/default.png';"
                 onclick="changeImage('<?= htmlspecialchars($img); ?>')">
        <?php endforeach; ?>
    </div>
</div>

<!-- INFO SECTION -->
<div class="info-section">

    <h1><?= htmlspecialchars($experience['exp_title']); ?></h1>

    <div class="category"><?= htmlspecialchars($experience['category']); ?></div>

    <div class="price-rating">
        <span class="price">RM <?= number_format($experience['min_price'], 0); ?> - RM <?= number_format($experience['max_price'], 0); ?></span>
        <span class="stars">
            <?php for ($i = 0; $i < round($experience['rating']); $i++): ?>
                <i class="fa-solid fa-star"></i>
            <?php endfor; ?>
        </span>
    </div>

    <p class="description"><?= nl2br(htmlspecialchars($experience['exp_desc'])); ?></p>

    <ul class="meta-list">
        <li><strong>Location:</strong> <?= htmlspecialchars($experience['location']); ?>, <?= htmlspecialchars($experience['state']); ?></li>
        <li><strong>Tags:</strong> <?= htmlspecialchars($experience['tags']); ?></li>
        <li><strong>Operating Hours:</strong> <?= htmlspecialchars($experience['operating_hours']); ?></li>
        <li><strong>Operating Days:</strong> <?= htmlspecialchars($experience['open_day']); ?></li>
        <li><strong>Closing Days:</strong> <?= htmlspecialchars($experience['close_day']); ?></li>
    </ul>

    <h3>Hosted by</h3>
    <p>
        <a class="host-link"
           href="hostprofile.php?id=<?= (int)$experience['host_id']; ?>&return=<?= urlencode($_SERVER['REQUEST_URI']); ?>">
           <?= htmlspecialchars($host_name); ?>
        </a>
    </p>
    <p>📞 <?= htmlspecialchars($host_contact); ?></p>

    <?php if (!empty($experience['web_link'])): ?>
        <a href="<?= htmlspecialchars($experience['web_link']); ?>" target="_blank" class="more-info">
            More Info
        </a>
    <?php endif; ?>

</div>
    </div>



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

    </script>

    

</body>
</html>
