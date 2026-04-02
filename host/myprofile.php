<?php
session_start();
require 'script/db.php';

// Ensure user is logged in
if (!isset($_SESSION['host_id'])) {
    header("Location: host_login.html");
    exit();
}

$host_id = $_SESSION['host_id'];

/* -----------------------------------------------------
   FETCH APPROVED EXPERIENCES
----------------------------------------------------- */
$sql = "
    SELECT 
        e.exp_id,
        e.exp_title,
        e.category,
        e.state,
        e.min_price,
        e.rating,
        (SELECT img_path 
         FROM experience_images 
         WHERE exp_id = e.exp_id 
         ORDER BY seq_no ASC LIMIT 1) AS first_img
    FROM experience e
    WHERE e.host_id = ?
      AND e.status = 'Approved'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $host_id);
$stmt->execute();
$experiences = $stmt->get_result();

/* -----------------------------------------------------
   FETCH HOST INFO
----------------------------------------------------- */
$stmt2 = $conn->prepare("
    SELECT username, biz_name, biz_email, phone_num, state, focus_area, about, profile_pic, host_rank
    FROM host_acc 
    WHERE host_id = ?
");
$stmt2->bind_param("i", $host_id);
$stmt2->execute();
$host = $stmt2->get_result()->fetch_assoc();

/* Safe profile picture */
$profile_pic = !empty($host['profile_pic'])
    ? "uploads/profile/" . $host['profile_pic']
    : "img/default_profile.png";

/* -----------------------------------------------------
   AUTO CALCULATE RANK (same as admin)
----------------------------------------------------- */
$rank_sql = "
    SELECT 
        COUNT(*) AS total_reviews,
        AVG(r.rating) AS avg_rating
    FROM review r
    JOIN experience e ON r.exp_id = e.exp_id
    WHERE e.host_id = $host_id
";

$rank_data = $conn->query($rank_sql)->fetch_assoc();

$total_reviews = $rank_data['total_reviews'] ?? 0;
$avg_rating    = $rank_data['avg_rating'] ?? 0;

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LokalKita - Host Profile</title>
    <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="myprofile.css">

  <style>
      /* --- BADGE CONTAINER NEXT TO BUSINESS NAME --- */
      .biz_name {
          display: flex;
          align-items: center;
          gap: 12px;
      }

      /* --- BASE BADGE STYLE --- */
      .rank-badge {
          display: inline-flex;
          align-items: center;
          gap: 6px;
          padding: 6px 12px;
          border-radius: 20px;
          font-weight: 600;
          font-size: 13px;
          position: relative;
          z-index: 1;
          transition: 0.2s ease-in-out;
      }

      .rank-badge i {
          font-size: 16px;
      }

      /* --- BADGE COLORS BASED ON RANK --- */
      .rank-megahost {
          background: #ffe08a;
          color: #7a5300;
      }

      .rank-superhost {
          background: #d5d5d5;
          color: #444;
      }

      .rank-trustedhost {
          background: #c9a27b;
          color: #4d3827;
      }

      .rank-newhost {
          background: #ffd3d3;
          color: #a33a3a;
      }

      /* --- GLOW EFFECT (always on) --- */
      .rank-badge {
          box-shadow: 0 0 8px rgba(255, 215, 0, 0.6);
      }

      /* --- Hover Glow Animation --- */
      .rank-badge:hover {
          transform: scale(1.05);
          box-shadow: 0 0 15px rgba(255, 220, 50, 0.9), 
                      0 0 25px rgba(255, 180, 40, 0.7);
      }
  </style>
</head>
<body>

<div class="profile-container">

  <!-- Header -->
  <header>
    <h1 class="logo">LokalKita</h1>

    <div class="menu-toggle" id="menu-toggle">
      <i class="fas fa-grip-horizontal"></i>
    </div>

    <nav id="navbar">
      <ul>
        <li><a href="host_dashboard.php">Back</a></li>
      </ul>
    </nav>
  </header>


  <!-- HOST PROFILE HEADER -->
  <div class="profile-header">
    <img src="<?php echo $profile_pic; ?>" class="profile-img">

    <div class="header-text">

        <!-- BUSINESS NAME + BADGE SIDE-BY-SIDE -->
        <h1 class="biz_name">
            <?php echo htmlspecialchars($host['biz_name']); ?>

            <span class="rank-badge rank-<?php echo $rank_class; ?>">
                <i class="fa-solid fa-trophy"></i> <?php echo $rank; ?>
            </span>
        </h1>

        <p class="state">
            <i class="fa-solid fa-location-dot"></i>
            <?php echo htmlspecialchars($host['state']); ?>
        </p>

        <p class="username">by <?php echo htmlspecialchars($host['username']); ?></p>
    </div>
  </div>


  <!-- ABOUT + FOCUS AREA -->
  <div class="main-grid">
      <div class="about-box">
          <?php echo nl2br(htmlspecialchars($host['about'])); ?>
      </div>

      <div class="focus-box">
          <h3>Focus Area</h3>
          <p><?php echo htmlspecialchars($host['focus_area']); ?></p>
      </div>
  </div>


  <!-- CONTACT -->
  <div class="contact-box">
      <div class="contact-row">
          <i class="fa-solid fa-phone"></i>
          <span><?php echo htmlspecialchars($host['phone_num']); ?></span>
      </div>

      <div class="contact-row">
          <i class="fa-solid fa-envelope"></i>
          <span><?php echo htmlspecialchars($host['biz_email']); ?></span>
      </div>
  </div>


  <!-- EXPERIENCES -->
  <section id="popular">
    <h3 class="exp-title">Experiences</h3>
    <br>

    <div class="grid">

    <?php while ($exp = $experiences->fetch_assoc()): ?>

        <?php
            $first_img = !empty($exp['first_img'])
                ? "uploads/experience/" . $exp['first_img']
                : "img/default.jpg";
        ?>

        <a href="experience-detail.php?id=<?php echo $exp['exp_id']; ?>" class="card-link">

            <article class="card">
                <div class="card-media">
                    <img src="<?php echo $first_img; ?>">
                    <span class="badge"><?php echo htmlspecialchars($exp['category']); ?></span>
                </div>

                <div class="content">
                    <h4><?php echo htmlspecialchars($exp['exp_title']); ?></h4>

                    <p class="meta" style="text-decoration: none !important;">
                        📍 <?php echo htmlspecialchars($exp['state']); ?> 
                        • RM<?php echo number_format($exp['min_price'], 2); ?>
                    </p>

                    <div class="rating">
                        <i class="fa-solid fa-star"></i>
                        <span class="rating-num">
                            <?php echo ($exp['rating'] > 0) ? number_format($exp['rating'], 1) : "New"; ?>
                        </span>
                    </div>
                </div>
            </article>

        </a>

    <?php endwhile; ?>


    <?php if ($experiences->num_rows == 0): ?>
        <p style="text-align:center; margin-top:20px;">
            You have no published experiences yet.
        </p>
    <?php endif; ?>

    </div>
  </section>

</div>

<script>
const menuToggle = document.getElementById("menu-toggle");
const navbar = document.getElementById("navbar");

menuToggle.addEventListener("click", () => {
  navbar.classList.toggle("active");
});
</script>

</body>
</html>
