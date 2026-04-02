<?php
session_start();
require 'script/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.html");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// ==================================================
// 1. FETCH USER PROFILE
// ==================================================
$uStmt = $conn->prepare("
    SELECT username, state, min_budget, max_budget, profile_pic
    FROM user_acc
    WHERE user_id = ?
");
$uStmt->bind_param("i", $user_id);
$uStmt->execute();
$user = $uStmt->get_result()->fetch_assoc();
$uStmt->close();

if (!$user) {
    die("User not found.");
}

$user_name   = $user['username'];
$user_state  = $user['state'] ?: 'Malaysia';

$budget_label = (!is_null($user['min_budget']) && !is_null($user['max_budget']))
    ? "Budget: RM{$user['min_budget']}–RM{$user['max_budget']}"
    : "Budget: Not set";

$profilePic = (!empty($user['profile_pic']))
    ? "user/uploads/profile/" . $user['profile_pic']
    : "pic/default-user.png";


// ==================================================
// 2. USER INTERESTS
// ==================================================
$interestNames = [];
$iStmt = $conn->prepare("
    SELECT i.interest_name
    FROM user_interest ui
    JOIN interest i ON ui.interest_id = i.interest_id
    WHERE ui.user_id = ?
");
$iStmt->bind_param("i", $user_id);
$iStmt->execute();
$res = $iStmt->get_result();
while ($r = $res->fetch_assoc()) {
    $interestNames[] = $r['interest_name'];
}
$iStmt->close();


// ==================================================
// 3. LIKED & SAVED IDS
// ==================================================
$likedIds = [];
$savedIds = [];

$stmt = $conn->prepare("SELECT exp_id FROM user_like WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $likedIds[] = (int)$r['exp_id'];
}
$stmt->close();

$stmt = $conn->prepare("SELECT exp_id FROM user_save WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $savedIds[] = (int)$r['exp_id'];
}
$stmt->close();


// ==================================================
// 4. ML PERSONALIZED RECOMMENDATION (ONLY SOURCE)
// ==================================================

$recommended = [];
$recommendedIds = [];

$apiUrl = "https://lokalkita-ai-github-io.onrender.com/recommend/personalized"; 

$query = http_build_query([
    "user_id"   => $user_id,
    "liked"     => $likedIds,
    "saved"     => $savedIds,
    "interests" => $interestNames
]);

// This builds the full URL with the user data attached
$fullUrl = $apiUrl . "?" . $query;

// Fetch the data from Render
$response = file_get_contents($fullUrl);

$apiResponse = file_get_contents("$apiUrl?$query");

$apiData = json_decode($apiResponse, true);

if (is_array($apiData)) {
    foreach ($apiData as $row) {
        if (!empty($row['item_id'])) {
            $recommendedIds[] = (int)$row['item_id'];
        }
    }
}

if (empty($recommendedIds)) {
    // fallback: interest-based SQL (NOT rating-based)
    $likeParts = [];
    $params = [];
    $types = "";

    foreach ($interestNames as $name) {
        $likeParts[] = "(e.category LIKE ? OR e.tags LIKE ?)";
        $like = "%" . $name . "%";
        $params[] = $like;
        $params[] = $like;
        $types .= "ss";
    }

    if (!empty($likeParts)) {
        $whereLike = implode(" OR ", $likeParts);

        $sql = "
            SELECT 
                e.exp_id, e.exp_title, e.state, e.category,
                e.min_price, COALESCE(e.rating,0) AS rating,
                (
                    SELECT img_path FROM experience_images ei
                    WHERE ei.exp_id = e.exp_id
                    ORDER BY seq_no ASC LIMIT 1
                ) AS img_path
            FROM experience e
            WHERE $whereLike
            LIMIT 10
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $row['img'] = $row['img_path']
                ? "../host/uploads/experience/" . $row['img_path']
                : "../pic/default.png";
            $recommended[] = $row;
        }
        $stmt->close();
    }
}




// ==================================================
// 5. FETCH EXPERIENCE DETAILS (KEEP ML ORDER)
// ==================================================
if (!empty($recommendedIds)) {

    $placeholders = implode(',', array_fill(0, count($recommendedIds), '?'));
$orderPlaceholders = implode(',', $recommendedIds);
$types = str_repeat('i', count($recommendedIds));

$sql = "
    SELECT 
        e.exp_id,
        e.exp_title,
        e.state,
        e.category,
        e.min_price,
        COALESCE(e.rating,0) AS rating,
        (
            SELECT img_path
            FROM experience_images ei
            WHERE ei.exp_id = e.exp_id
            ORDER BY seq_no ASC
            LIMIT 1
        ) AS img_path
    FROM experience e
    WHERE e.exp_id IN ($placeholders)
    ORDER BY FIELD(e.exp_id, $orderPlaceholders)
";


    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$recommendedIds);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $row['img'] = $row['img_path']
            ? "../host/uploads/experience/" . $row['img_path']
            : "../pic/default.png";
        $recommended[] = $row;
    }
    $stmt->close();
}


// ==================================================
// 6. BUCKET LIST, SAVED, LIKED, REVIEWS
// ==================================================
$bucketItems = [];
$bStmt = $conn->prepare("
    SELECT 
        b.bucket_id,
        b.exp_id,
        b.status,
        e.exp_title
    FROM bucketlist b
    JOIN experience e ON e.exp_id = b.exp_id
    WHERE b.user_id = ?
    ORDER BY b.bucket_id DESC
");

$bStmt->bind_param("i", $user_id);
$bStmt->execute();
$bRes = $bStmt->get_result();
while ($row = $bRes->fetch_assoc()) {
    $bucketItems[] = $row;
}
$bStmt->close();


// =========================
// 7. SAVED EXPERIENCES (PREVIEW)
// =========================
$savedPreview = [];
$svPrevStmt = $conn->prepare("
    SELECT 
        e.exp_id, e.exp_title,
        (
            SELECT img_path FROM experience_images ei
            WHERE ei.exp_id = e.exp_id
            ORDER BY seq_no ASC LIMIT 1
        ) AS img_path
    FROM user_save us
    JOIN experience e ON e.exp_id = us.exp_id
    WHERE us.user_id = ?
    ORDER BY us.exp_id DESC
    LIMIT 2
");
$svPrevStmt->bind_param("i", $user_id);
$svPrevStmt->execute();
$svPrevRes = $svPrevStmt->get_result();
while ($row = $svPrevRes->fetch_assoc()) {
    $row['img'] = $row['img_path']
        ? "../host/uploads/experience/" . $row['img_path']
        : "../pic/default.png";
    $savedPreview[] = $row;
}
$svPrevStmt->close();


// =========================
// 8. LIKED EXPERIENCES (PREVIEW)
// =========================
$likedPreview = [];
$lkPrevStmt = $conn->prepare("
    SELECT 
        e.exp_id, e.exp_title,
        (
            SELECT img_path FROM experience_images ei
            WHERE ei.exp_id = e.exp_id
            ORDER BY seq_no ASC LIMIT 1
        ) AS img_path
    FROM user_like ul
    JOIN experience e ON e.exp_id = ul.exp_id
    WHERE ul.user_id = ?
    ORDER BY ul.exp_id DESC
    LIMIT 2
");
$lkPrevStmt->bind_param("i", $user_id);
$lkPrevStmt->execute();
$lkPrevRes = $lkPrevStmt->get_result();
while ($row = $lkPrevRes->fetch_assoc()) {
    $row['img'] = $row['img_path']
        ? "../host/uploads/profile/" . $row['img_path']
        : "../pic/default.png";
    $likedPreview[] = $row;
}
$lkPrevStmt->close();


// =========================
// 9. USER REVIEWS
// =========================
$reviews = [];
$rStmt = $conn->prepare("
    SELECT 
        r.review_id, r.exp_id, r.review, r.rating, r.date_create,
        e.exp_title
    FROM review r
    JOIN experience e ON e.exp_id = r.exp_id
    WHERE r.user_id = ?
    ORDER BY r.date_create DESC
");
$rStmt->bind_param("i", $user_id);
$rStmt->execute();
$rRes = $rStmt->get_result();
while ($row = $rRes->fetch_assoc()) {
    $reviews[] = $row;
}
$rStmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LokalKita | User Dashboard</title>

  <link rel="stylesheet" href="dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />
</head>

<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <div class="logo">
      <span>LokalKita</span>
    </div>

    <div class="hamburger" id="hamburger">
      <i class="fas fa-grip-horizontal"></i>
    </div>

    <div class="nav-right" id="nav-menu">
      <ul class="nav-links">
        <li><a href="main_page.php">Browse</a></li>
        <!--li><a href="memory-home.php">Journal</a></li-->
        <li><a href="user_login.html">Logout</a></li>
      </ul>
      <div class="profile-icon">
        <a href="profile-edit.php">
          <img src="<?= htmlspecialchars($profilePic); ?>" alt="Profile" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
        </a>
      </div>
    </div>
  </nav>

  <!-- MAIN DASHBOARD -->
  <div class="dashboard-wrapper">
    <main class="dashboard-main">

      <!-- TOP PROFILE SUMMARY -->
      <section class="section-panel profile-summary fade-in-up">
        <div class="profile-summary-main">
          <div class="profile-avatar">
            <img src="<?= htmlspecialchars($profilePic); ?>" alt="User Profile Picture">
          </div>

          <div class="profile-summary-text">
            <h2 class="profile-username" style="text-transform: capitalize;"><?= htmlspecialchars($user_name); ?></h2>
            <p class="profile-bio">Welcome back! Plan your next LokalKita adventure based on what you’ve liked & saved.</p>

            <div class="profile-meta">
              <span class="pill"><?= htmlspecialchars($user_state); ?>, Malaysia</span>
              
            </div>
          </div>
        </div>
      </section>


      <!-- RECOMMENDED SECTION -->
      <section class="section-panel fade-in-up">
        <div class="section-header">
          <h2 class="section-title">Recommended For You</h2>
          <p class="section-subtitle">Based on your interests, likes & saved experiences</p>
        </div>

        <div class="recommend-grid">

          <?php if (empty($recommended)): ?>
            <p>No recommendations yet. Try saving or liking some experiences first!</p>
          <?php else: ?>
            <?php foreach ($recommended as $exp): 
                $expId = (int)$exp['exp_id'];
                $liked   = in_array($expId, $likedIds);
                $saved   = in_array($expId, $savedIds);
            ?>
              <a href="experience-detail.php?exp_id=<?= $expId; ?>" class="experience-card hover-elevate">
                <div class="experience-tag"><?= htmlspecialchars($exp['category']); ?></div>
                <div class="img-wrapper">
                  <img 
                    src="<?= htmlspecialchars($exp['img']); ?>" 
                    class="experience-img"
                    onerror="this.onerror=null;this.src='../pic/default.png';"
                  >
                </div>

                <div class="experience-body" >
                  <h4  style="text-decoration: none; color: black;"><?= htmlspecialchars($exp['exp_title']); ?></h4>
                  <p class="experience-location"  style="text-decoration: none; color: black;">
                    <i class="fa-solid fa-location-dot" style="color:#e74c3c;"></i>
                    <?= htmlspecialchars($exp['state']); ?> · RM <?= (int)$exp['min_price']; ?>
                  </p>
                  <p class="experience-rating" style="text-decoration: none; color: black;">
                    <i class="fa-solid fa-star" style="color:#FFEE8C;"></i>
                    <?= number_format($exp['rating'],1); ?>
                  </p>
                </div>

                <div class="experience-actions">
                  <button 
                    class="icon-btn like-btn <?= $liked ? 'active' : ''; ?>" 
                    data-exp="<?= $expId; ?>"
                  >
                    <i class="<?= $liked ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
                  </button>
                  <button 
                    class="icon-btn save-btn <?= $saved ? 'active' : ''; ?>" 
                    data-exp="<?= $expId; ?>"
                  >
                    <i class="<?= $saved ? 'fa-solid' : 'fa-regular'; ?> fa-bookmark"></i>
                  </button>
                </div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>

        </div>
      </section>



      <!-- BUCKET + SAVED + LIKED TITLE -->
      <div class="section-header">
        <h2 class="section-title">Bucket List, Saved and Liked Experiences</h2>
        <p class="section-subtitle" style="margin-bottom:-20px;">Make your list come true!</p>
      </div>


      <!-- MIDDLE GRID -->
      <section class="section-panel middle-grid fade-in-up">

        <!-- BUCKET LIST -->
       
        <section class="bucket-card hover-elevate" id="bucketBox">
          <div class="bucket-header">
            <h3>Bucket List</h3>
            <p class="bucket-subtitle">Plan your next adventure</p>
            <p class="bucket-subtitle" style="margin-top:-15px; font-size: 0.7rem;">click title to undo bucketlist ⭐</p>
          </div>

          <!-- Progress Bar -->
          <div class="bucket-progress-container">
            <div class="bucket-progress-fill" id="bucket-progress"></div>
          </div>

          
          <div class="bucket-list">
            <?php if (empty($bucketItems)): ?>
              <p style="font-size:0.9rem; color:#666;">You have no bucket list items yet.</p>
            <?php else: ?>

              <?php foreach ($bucketItems as $b): ?>
                  <div class="bucket-item">

                      <div class="bucket-star <?= ($b['status'] === 'Done' ? 'done' : ''); ?>" 
                          data-bucket-id="<?= (int)$b['bucket_id']; ?>">
                          <i class="fa-solid fa-star"></i>
                      </div>


                      <span>
                          <a href="experience-detail.php?exp_id=<?= (int)$b['exp_id']; ?>">
                              <?= htmlspecialchars($b['exp_title']); ?>
                          </a>
                      </span>

                  </div>
              <?php endforeach; ?>

            <?php endif; ?>
          </div>
        </section>



        <!-- SAVED -->
        <a href="saved-experiences.php" class="strip-card hover-elevate">
          <div class="strip-header"><h3>Saved Experiences</h3></div>

          <div class="collage-wrapper">
            <div class="collage-left">
              <?php if (!empty($savedPreview)): ?>
                <img src="<?= htmlspecialchars($savedPreview[0]['img']); ?>" 
                     onerror="this.onerror=null;this.src='../pic/default.png';">
              <?php else: ?>
                <img src="../pic/p1.png">
              <?php endif; ?>
            </div>
            <div class="collage-right">
              <?php if (count($savedPreview) > 1): ?>
                <img src="<?= htmlspecialchars($savedPreview[1]['img']); ?>" 
                     onerror="this.onerror=null;this.src='../pic/default.png';">
              <?php else: ?>
                <img src="../pic/loka.jpg">
              <?php endif; ?>
            </div>
          </div>
        </a>

        <!-- Liked -->
        <a href="liked-experiences.php" class="strip-card hover-elevate">
          <div class="strip-header"><h3>Liked Experiences</h3></div>

          <div class="collage-wrapper">
            <div class="collage-left">
              <?php if (!empty($likedPreview)): ?>
                <img src="<?= htmlspecialchars($likedPreview[0]['img']); ?>" 
                     onerror="this.onerror=null;this.src='../pic/default.png';">
              <?php else: ?>
                <img src="../pic/p1.png">
              <?php endif; ?>
            </div>
            <div class="collage-right">
              <?php if (count($likedPreview) > 1): ?>
                <img src="<?= htmlspecialchars($likedPreview[1]['img']); ?>" 
                     onerror="this.onerror=null;this.src='../pic/default.png';">
              <?php else: ?>
                <img src="../pic/loka.jpg">
              <?php endif; ?>
            </div>
          </div>
        </a>

      
      </section>


      <!-- REVIEWS -->
      <section class="section-panel fade-in-up past-reviews">
        <div class="section-header">
          <h2 class="section-title">Your Reviews</h2>
          <p class="section-subtitle">See and manage reviews you've shared</p>
        </div>

        <div class="review-list-dashboard">

          <?php if (empty($reviews)): ?>
            <p style="font-size:0.9rem; color:#666;">You haven’t written any reviews yet.</p>
          <?php else: ?>
            <?php foreach ($reviews as $rv): ?>
              <div class="review-item-card" data-review-id="<?= (int)$rv['review_id']; ?>">
                <div class="review-item-header">
                  <h4 class="review-experience-title"><?= htmlspecialchars($rv['exp_title']); ?></h4>
                  <span class="review-date"><?= htmlspecialchars($rv['date_create']); ?></span>
                </div>

                <!-- view mode -->
                <div class="review-view">
                  <div class="review-stars">
                    <?php for ($i=1; $i<=5; $i++): ?>
                      <i class="fa-<?= $i <= $rv['rating'] ? 'solid' : 'regular'; ?> fa-star"></i>
                    <?php endfor; ?>
                    <span class="rating-num"><?= number_format($rv['rating'],1); ?></span>
                  </div>

                  <p class="review-text"><?= nl2br(htmlspecialchars($rv['review'])); ?></p>

                  <div class="review-actions">
                    <button class="review-btn edit-btn"><i class="fa-regular fa-pen-to-square"></i> Edit</button>
                    <button class="review-btn delete-btn"><i class="fa-regular fa-trash-can"></i> Delete</button>
                  </div>
                </div>

                <!-- edit mode -->
                <div class="review-edit" style="display:none;">
                  <div class="edit-stars">
                    <?php for ($i=1; $i<=5; $i++): ?>
                      <i class="fa-regular fa-star <?= $i <= $rv['rating'] ? 'active' : ''; ?>" data-value="<?= $i; ?>"></i>
                    <?php endfor; ?>
                  </div>

                  <textarea class="edit-textarea"><?= htmlspecialchars($rv['review']); ?></textarea>

                  <div class="review-actions">
                    <button class="review-btn save-btn"><i class="fa-regular fa-floppy-disk"></i> Save</button>
                    <button class="review-btn cancel-btn"><i class="fa-regular fa-circle-xmark"></i> Cancel</button>
                  </div>
                </div>

              </div>
            <?php endforeach; ?>
          <?php endif; ?>

        </div>
      </section>

    </main>
  </div>


  <!-- JAVASCRIPT -->
  <script>
    const userId = <?= $user_id; ?>;

    // NAV HAMBURGER
    document.getElementById('hamburger').addEventListener('click', () => {
      document.getElementById('nav-menu').classList.toggle('active');
    });

    // LIKE & SAVE BUTTONS (call like_save.php)
    document.querySelectorAll('.like-btn').forEach(btn => {
      btn.addEventListener('click', e => {
        e.preventDefault(); e.stopPropagation();
        const expId = btn.dataset.exp;

        fetch("script/like_save.php", {
          method: "POST",
          headers: {"Content-Type": "application/x-www-form-urlencoded"},
          body: new URLSearchParams({ exp_id: expId, type: "like" })
        })
        .then(r => r.json())
        .then(res => {
          if (res.status === "liked") {
            btn.classList.add('active');
            const icon = btn.querySelector('i');
            icon.classList.remove('fa-regular');
            icon.classList.add('fa-solid');
          } else if (res.status === "unliked") {
            btn.classList.remove('active');
            const icon = btn.querySelector('i');
            icon.classList.remove('fa-solid');
            icon.classList.add('fa-regular');
          }
        });
      });
    });

    document.querySelectorAll('.save-btn').forEach(btn => {
      btn.addEventListener('click', e => {
        e.preventDefault(); e.stopPropagation();
        const expId = btn.dataset.exp;

        fetch("script/like_save.php", {
          method: "POST",
          headers: {"Content-Type": "application/x-www-form-urlencoded"},
          body: new URLSearchParams({ exp_id: expId, type: "save" })
        })
        .then(r => r.json())
        .then(res => {
          if (res.status === "saved") {
            btn.classList.add('active');
            const icon = btn.querySelector('i');
            icon.classList.remove('fa-regular');
            icon.classList.add('fa-solid');
          } else if (res.status === "unsaved") {
            btn.classList.remove('active');
            const icon = btn.querySelector('i');
            icon.classList.remove('fa-solid');
            icon.classList.add('fa-regular');
          }
        });
      });
    });


/* =========================================================
   BUCKET LIST STAR + CONFETTI + PROGRESS + ACHIEVEMENT
========================================================= */

// Confetti inside bucket box only
function spawnConfettiInBucket(x, y) {
    const box = document.getElementById("bucketBox");
    const boxRect = box.getBoundingClientRect();

    for (let i = 0; i < 14; i++) {
        const c = document.createElement("div");
        c.classList.add("confetti");

        const colors = ["#ff5f7a", "#ffd95d", "#7dd87d", "#5ab4ff"];
        c.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];

        // Position inside bucket box
        c.style.left = (x - boxRect.left) + (Math.random() * 40 - 20) + "px";
        c.style.top  = (y - boxRect.top) + "px";

        box.appendChild(c);

        setTimeout(() => c.remove(), 1200);
    }
}

function updateBucketProgress() {
    const stars = document.querySelectorAll(".bucket-star");
    const completed = document.querySelectorAll(".bucket-star.done").length;
    const percent = stars.length ? (completed / stars.length) * 100 : 0;
    document.getElementById("bucket-progress").style.width = percent + "%";
    return completed;
}

function showAchievement(msg) {
    const popup = document.getElementById("achievement-popup");
    const text = document.getElementById("achievement-text");

    text.textContent = msg;
    popup.classList.add("show");

    setTimeout(() => popup.classList.remove("show"), 2500);
}

// CLICK STAR
document.querySelectorAll('.bucket-star').forEach(star => {
    star.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const bucketId = this.dataset.bucketId;
        const isDone = this.classList.toggle('done');
        const newStatus = isDone ? "Done" : "Active";

        updateBucketProgress();

        // CONFETTI INSIDE BUCKET BOX ONLY
        if (isDone) {
            const rect = this.getBoundingClientRect();
            spawnConfettiInBucket(rect.left + 10, rect.top + 10);
            showAchievement("Yeay, bucketlist achieved!");
        }

        // UPDATE DATABASE
        fetch("script/update-bucket-status.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `bucket_id=${bucketId}&status=${newStatus}`
        })
        .then(res => res.text())
        .then(data => console.log("Bucket update:", data));
    });
});

updateBucketProgress();


    // SCROLL ANIMATION
    const obs = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view');
          obs.unobserve(entry.target);
        }
      });
    }, {threshold:0.15});
    document.querySelectorAll('.fade-in-up').forEach(el => obs.observe(el));


    // REVIEW EDITING (with backend calls)
    document.querySelectorAll('.review-item-card').forEach(card => {

      const view = card.querySelector(".review-view");
      const edit = card.querySelector(".review-edit");

      const editBtn = card.querySelector(".edit-btn");
      const cancelBtn = card.querySelector(".cancel-btn");
      const saveBtn = card.querySelector(".save-btn");
      const deleteBtn = card.querySelector(".delete-btn");

      const ratingDisplay = card.querySelector(".rating-num");
      const reviewText = card.querySelector(".review-text");

      const reviewId = card.dataset.reviewId;

      editBtn.addEventListener("click", () => {
        view.style.display = "none";
        edit.style.display = "block";
      });

      cancelBtn.addEventListener("click", () => {
        edit.style.display = "none";
        view.style.display = "block";
      });

      edit.querySelectorAll(".edit-stars i").forEach(star => {
        star.addEventListener("click", () => {
          let value = parseInt(star.dataset.value);
          edit.querySelectorAll(".edit-stars i").forEach((s,i) => {
            s.classList.toggle("active", i < value);
          });
        });
      });

      saveBtn.addEventListener("click", () => {
        let newRating = edit.querySelectorAll(".edit-stars i.active").length;
        let newText   = edit.querySelector(".edit-textarea").value.trim();

        if (!newRating || !newText) {
          alert("Please provide rating and review text.");
          return;
        }

        fetch("script/review_actions.php", {
          method: "POST",
          headers: {"Content-Type": "application/x-www-form-urlencoded"},
          body: new URLSearchParams({
            action: "update",
            review_id: reviewId,
            rating: newRating,
            review: newText
          })
        })
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            // update UI
            reviewText.textContent = newText;
            ratingDisplay.textContent = newRating.toFixed(1);

            let starsHTML = "";
            for (let i=1; i<=5; i++){
              starsHTML += `<i class="fa-${i<=newRating?'solid':'regular'} fa-star"></i>`;
            }
            card.querySelector(".review-stars").innerHTML =
              starsHTML + ` <span class="rating-num">${newRating}.0</span>`;

            edit.style.display = "none";
            view.style.display = "block";
          } else {
            alert(res.message || "Error updating review.");
          }
        });
      });

      deleteBtn.addEventListener("click", () => {
        if (!confirm("Delete this review?")) return;

        fetch("script/review_actions.php", {
          method: "POST",
          headers: {"Content-Type": "application/x-www-form-urlencoded"},
          body: new URLSearchParams({
            action: "delete",
            review_id: reviewId
          })
        })
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            card.remove();
          } else {
            alert(res.message || "Error deleting review.");
          }
        });
      });

    });

  </script>

  <!-- Achievement Popup -->
  <div id="achievement-popup" class="achievement-popup">
    <i class="fa-solid fa-award"></i>
    <span id="achievement-text">Achievement Unlocked!</span>
  </div>

</body>
</html>
