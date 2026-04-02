<?php
session_start();
require 'script/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.html");
    exit();
}

$user_id = intval($_SESSION['user_id']);


// ========================================
// 1. Get all saved exp IDs (for button state)
// ========================================
$savedIds = [];
$savedQ = $conn->prepare("SELECT exp_id FROM user_save WHERE user_id = ?");
$savedQ->bind_param("i", $user_id);
$savedQ->execute();
$savedR = $savedQ->get_result();
while ($row = $savedR->fetch_assoc()) {
    $savedIds[] = (int)$row['exp_id'];
}


// ========================================
// 2. Get all liked exp IDs (for button state)
// ========================================
$likedIds = [];
$likedQ = $conn->prepare("SELECT exp_id FROM user_like WHERE user_id = ?");
$likedQ->bind_param("i", $user_id);
$likedQ->execute();
$likedR = $likedQ->get_result();
while ($row = $likedR->fetch_assoc()) {
    $likedIds[] = (int)$row['exp_id'];
}


// ========================================
// 3. Fetch saved experiences
// ========================================
$sql = "
    SELECT 
        e.exp_id, e.exp_title, e.state, e.category, e.min_price, 
        COALESCE(e.rating, 0) AS rating,
        (SELECT img_path FROM experience_images ei 
         WHERE ei.exp_id = e.exp_id 
         ORDER BY seq_no ASC LIMIT 1) AS img_path
    FROM user_like us
    JOIN experience e ON e.exp_id = us.exp_id
    WHERE us.user_id = ?
    ORDER BY us.exp_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$results = $stmt->get_result();

$savedExperiences = [];
while ($row = $results->fetch_assoc()) {
    $row['img'] = $row['img_path']
        ? "../host/uploads/experience/" . $row['img_path']
        : "../pic/default.png";
    $savedExperiences[] = $row;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  
  <title>LokalKita - Saved Experiences</title>
  <link rel="stylesheet" href="save-like.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />

</head>

<body>

<header>
  <h1 class="logo">LokalKita</h1>

  <div class="menu-toggle" id="menu-toggle">
    <i class="fas fa-grip-horizontal"></i>
  </div>

  <nav id="navbar">
    <ul>
      <li><a href="user_dashboard.php">Back</a></li>
    </ul>
  </nav>
</header>

<section id="popular">
  <h3>Liked Experiences</h3>


  <div class="grid">

  <?php if (empty($savedExperiences)): ?>
      <p style="color:#666; font-size:1rem; padding:20px;">
          You have no saved experiences yet.
      </p>

  <?php else: ?>
      <?php foreach ($savedExperiences as $exp): 
            $id = $exp['exp_id'];

            $isSaved = in_array($id, $savedIds);
            $isLiked = in_array($id, $likedIds);
      ?>
      
      <a href="experience-detail.php?exp_id=<?= $id ?>" class="card-link">
        <article class="card"
          data-category="<?= strtolower($exp['category']) ?>"
          data-state="<?= $exp['state'] ?>"
          data-price="<?= $exp['min_price'] ?>"
        >

          <div class="card-media">
            <img src="<?= $exp['img'] ?>" onerror="this.src='../pic/default.png'">
            <span class="badge"><?= htmlspecialchars($exp['category']) ?></span>
          </div>

          <div class="content">
            <h4><?= htmlspecialchars($exp['exp_title']) ?></h4>
            <p class="meta">📍 <?= $exp['state'] ?> • RM<?= $exp['min_price'] ?></p>

            <div class="rating">
              <i class="fa-solid fa-star"></i>
              <span class="rating-num"><?= number_format($exp['rating'],1) ?></span>
            </div>
          </div>

          <div class="card-actions">

            <!-- LIKE BUTTON -->
            <button class="btn-like <?= $isLiked ? 'active' : '' ?>" 
                    data-exp="<?= $id ?>">
              <i class="<?= $isLiked ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
            </button>

            <!-- SAVE BUTTON -->
            <button class="btn-save <?= $isSaved ? 'active' : '' ?>" 
                    data-exp="<?= $id ?>">
              <i class="<?= $isSaved ? 'fa-solid' : 'fa-regular' ?> fa-bookmark"></i>
            </button>

          </div>

        </article>
      </a>

      <?php endforeach; ?>
  <?php endif; ?>

  </div>
</section>




<script>
// =============== NAVBAR ===============
document.getElementById("menu-toggle").addEventListener("click", () => {
  document.getElementById("navbar").classList.toggle("active");
});


// =====================================
//  LIKE BUTTON
// =====================================
document.querySelectorAll('.btn-like').forEach(btn => {
  btn.addEventListener('click', e => {
    e.preventDefault();
    e.stopPropagation();

    const expId = btn.dataset.exp;

    fetch("script/like_save.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ exp_id: expId, type: "like" })
    })
    .then(r => r.json())
    .then(res => {
      const icon = btn.querySelector('i');

      if (res.status === "liked") {
        btn.classList.add("active");
        icon.classList.replace("fa-regular", "fa-solid");
      } 
      else if (res.status === "unliked") {
        btn.classList.remove("active");
        icon.classList.replace("fa-solid", "fa-regular");
      }
    });
  });
});



// =====================================
//  LIKE BUTTON (also removes card if unsaved)
// =====================================
document.querySelectorAll('.btn-like').forEach(btn => {
  btn.addEventListener('click', e => {
    e.preventDefault();
    e.stopPropagation();

    const expId = btn.dataset.exp;

    fetch("script/like_save.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ exp_id: expId, type: "save" })
    })
    .then(r => r.json())
    .then(res => {
      const icon = btn.querySelector('i');

      if (res.status === "saved") {
        btn.classList.add("active");
        icon.classList.replace("fa-regular", "fa-solid");
      } 
      else if (res.status === "unsaved") {
        btn.closest(".card-link").remove();
      }
    });
  });
});
</script>

</body>
</html>
