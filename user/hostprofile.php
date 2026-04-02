<?php
session_start();
require 'script/db.php'; 

$host_id = (int)($_GET['id'] ?? 0);
if ($host_id <= 0) {
    die("Host ID is missing or invalid.");
}

/* ==============================
   FETCH HOST INFORMATION
============================== */
$hostQ = $conn->prepare("
  SELECT username, biz_name, biz_email, phone_num, profile_pic,
         focus_area, about, host_rank, state
  FROM host_acc
  WHERE host_id = ?
");
$hostQ->bind_param("i", $host_id);
$hostQ->execute();
$host = $hostQ->get_result()->fetch_assoc();

if (!$host) {
    die("Host not found.");
}

/* NULL-SAFE DEFAULTS */
$host_name    = htmlspecialchars($host['username'] ?? 'Unknown Host');
$biz_email    = htmlspecialchars($host['biz_email'] ?? '');
$phone        = htmlspecialchars($host['phone_num'] ?? '');
$about        = htmlspecialchars($host['about'] ?? '');
$focus        = htmlspecialchars($host['focus_area'] ?? '');
$state        = htmlspecialchars($host['state'] ?? '');

$profile_pic = !empty($host['profile_pic'])
    ? "../host/uploads/profile/" . htmlspecialchars($host['profile_pic'])
    : "../pic/default_profile.jpg";

/* ==============================
   FETCH HOST EXPERIENCES
============================== */
$expQ = $conn->prepare("
    SELECT 
        e.exp_id,
        e.exp_title,
        e.category,
        e.state,
        e.min_price,
        e.rating,
        (
            SELECT ei.img_path
            FROM experience_images ei
            WHERE ei.exp_id = e.exp_id
            ORDER BY ei.seq_no ASC
            LIMIT 1
        ) AS first_img
    FROM experience e
    WHERE e.host_id = ?
      AND e.status = 'Approved'
");
$expQ->bind_param("i", $host_id);
$expQ->execute();
$experiences = $expQ->get_result();

/* ==============================
   HOST RANK CALCULATION
============================== */
$rankQ = $conn->prepare("
    SELECT COUNT(*) AS total_reviews, AVG(r.rating) AS avg_rating
    FROM review r
    JOIN experience e ON r.exp_id = e.exp_id
    WHERE e.host_id = ?
");
$rankQ->bind_param("i", $host_id);
$rankQ->execute();
$rank_data = $rankQ->get_result()->fetch_assoc();

$total_reviews = (int)($rank_data['total_reviews'] ?? 0);
$avg_rating    = (float)($rank_data['avg_rating'] ?? 0);

if ($total_reviews >= 100 && $avg_rating >= 4.6) {
    $rank = "MegaHost";
} elseif ($total_reviews >= 50 && $avg_rating >= 4.0) {
    $rank = "SuperHost";
} elseif ($total_reviews > 0) {
    $rank = "Trusted Host";
} else {
    $rank = "New Host";
}

$rank_class = strtolower(str_replace(" ", "", $rank));

/* ==============================
   BACK LINK
============================== */
$returnUrl = $_GET['return'] ?? '';
if ($returnUrl && !preg_match('/^https?:\/\//i', $returnUrl)) {
    $backLink = $returnUrl;
} else {
    $backLink = $_SERVER['HTTP_REFERER'] ?? 'user_mainpage.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>LokalKita - Host Profile</title>

<link rel="stylesheet" href="../host/myprofile.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;900&display=swap" rel="stylesheet">
<style> 
.biz_name { display: flex; align-items: center; gap: 12px; } 
.rank-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 13px; position: relative; z-index: 1; transition: 0.2s ease-in-out; } 
.rank-badge i { font-size: 16px; } 
.rank-megahost { background: #ffe08a; color: #7a5300; } 
.rank-superhost { background: #d5d5d5; color: #444; } 
.rank-trustedhost { background: #c9a27b; color: #4d3827; } 
.rank-newhost { background: #ffd3d3; color: #a33a3a; } 
.rank-badge { box-shadow: 0 0 8px rgba(255, 215, 0, 0.6); } 
.rank-badge:hover { transform: scale(1.05); box-shadow: 0 0 15px rgba(255, 220, 50, 0.9), 0 0 25px rgba(255, 180, 40, 0.7); } 
</style>
</head>

<body>

<div class="profile-container">

<header>
  <h1 class="logo">LokalKita</h1>
  <nav>
    <ul>
      <li><a href="<?= htmlspecialchars($backLink); ?>">Back</a></li>
    </ul>
  </nav>
</header>

<!-- PROFILE HEADER -->
<div class="profile-header">
  <img 
    src="<?= $profile_pic; ?>"
    class="profile-img"
    alt="Host profile image"
    onerror="this.onerror=null; this.src='../pic/default_profile.jpg';"
  >

  <div class="header-text">
    <h1 class="biz_name">
      <?= htmlspecialchars($host['biz_name'] ?? ''); ?>
      <span class="rank-badge rank-<?= $rank_class; ?>">
        <i class="fa-solid fa-trophy"></i> <?= $rank; ?>
      </span>
    </h1>
    <p class="state">
      <i class="fa-solid fa-location-dot"></i> <?= $state; ?>
    </p>
    <p class="username">by <?= $host_name; ?></p>
  </div>
</div>

<!-- ABOUT + FOCUS -->
<div class="main-grid">
  <div class="about-box"><?= nl2br($about); ?></div>
  <div class="focus-box">
    <h3>Focus Area</h3>
    <p><?= $focus; ?></p>
  </div>
</div>

<!-- CONTACT -->
<div class="contact-box">
  <div class="contact-row"><i class="fa-solid fa-phone"></i> <?= $phone; ?></div>
  <div class="contact-row"><i class="fa-solid fa-envelope"></i> <?= $biz_email; ?></div>
</div>

<!-- EXPERIENCES -->
<section id="popular">
<h3 class="exp-title">Experiences</h3>
<div class="grid">

<?php if ($experiences->num_rows > 0): ?>
<?php while ($exp = $experiences->fetch_assoc()): ?>

<?php
$rawImg = trim($exp['first_img'] ?? '');
$first_img = $rawImg !== ''
    ? "../host/uploads/experience/" . htmlspecialchars($rawImg)
    : "../pic/p1.png";
?>

<a href="experience-detail.php?exp_id=<?= (int)$exp['exp_id']; ?>" class="card-link">
<article class="card">

<div class="card-media">
  <img 
    src="<?= $first_img; ?>"
    alt="Experience image"
    onerror="this.onerror=null; this.src='../pic/p1.png';"
  >
  <span class="badge"><?= htmlspecialchars($exp['category']); ?></span>
</div>

<div class="content">
  <h4><?= htmlspecialchars($exp['exp_title']); ?></h4>
  <p class="meta">
    📍 <?= htmlspecialchars($exp['state']); ?> • RM<?= number_format($exp['min_price'], 2); ?>
  </p>
  <div class="rating">
    <i class="fa-solid fa-star"></i>
    <span><?= $exp['rating'] > 0 ? number_format($exp['rating'], 1) : 'New'; ?></span>
  </div>
</div>

</article>
</a>

<?php endwhile; ?>
<?php else: ?>
<p style="text-align:center;">No published experiences yet.</p>
<?php endif; ?>

</div>
</section>

</div>
</body>
</html>
