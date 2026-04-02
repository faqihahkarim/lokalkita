<?php
session_start();
require 'script/db.php';

// Ensure admin logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.html");
    exit();
}

// Get host_id
$host_id = $_GET['id'] ?? 0;
if ($host_id == 0) {
    die("Invalid host ID.");
}

/* -------------------- FETCH HOST INFO -------------------- */
$query = "
    SELECT 
        username,
        biz_name,
        biz_email,
        phone_num,
        state,
        focus_area,
        about,
        profile_pic,
        host_rank
    FROM host_acc
    WHERE host_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $host_id);
$stmt->execute();
$host = $stmt->get_result()->fetch_assoc();

if (!$host) {
    die("Host not found.");
}

/* PROFILE PIC */
$profile_pic = !empty($host['profile_pic'])
    ? "../host/uploads/profile/" . $host['profile_pic']
    : "../host/img/default_profile.png";

/* -------------------- FETCH APPROVED EXPERIENCES -------------------- */
$sql2 = "
    SELECT 
        e.exp_id,
        e.exp_title,
        e.category,
        e.state,
        e.min_price,
        e.rating,
        (
            SELECT img_path 
            FROM experience_images 
            WHERE exp_id = e.exp_id 
            ORDER BY seq_no ASC LIMIT 1
        ) AS first_img
    FROM experience e
    WHERE e.host_id = ?
      AND e.status = 'Approved'
";

$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $host_id);
$stmt2->execute();
$experiences = $stmt2->get_result();

/* -------------------- RANK CALCULATION -------------------- */
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
    $new_rank = "MegaHost";
} elseif ($total_reviews >= 50 && $avg_rating >= 4.0) {
    $new_rank = "SuperHost";
} elseif ($total_reviews > 0) {
    $new_rank = "Trusted Host";
} else {
    $new_rank = "New Host";
}

// Update rank in DB
$update = $conn->prepare("UPDATE host_acc SET host_rank=? WHERE host_id=?");
$update->bind_param("si", $new_rank, $host_id);
$update->execute();

$rank = $new_rank;
$rank_class = strtolower(str_replace(" ", "", $rank));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LokalKita - Host Profile</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="pic/logo2.png" sizes="32x16" rel="shortcut icon" />
  <link rel="stylesheet" href="../host/myprofile.css" />

  <style>
      /* Sama macam myprofile: biz name + badge side-by-side */
      .biz_name {
          display: flex;
          align-items: center;
          gap: 12px;
      }

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
          box-shadow: 0 0 8px rgba(255, 215, 0, 0.6);
      }

      .rank-badge i {
          font-size: 16px;
      }

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
        <li><a href="host_list.php">Back</a></li>
      </ul>
    </nav>
  </header>

  <!-- HOST PROFILE -->
  <div class="profile-header">
    <img src="<?php echo $profile_pic; ?>" 
     class="profile-img"
     onerror="this.onerror=null; this.src='../host/img/default_image.png';">


    <div class="header-text">
        <h1 class="biz_name">
            <?php echo htmlspecialchars($host['biz_name'] ?? 'Unnamed Business'); ?>

            <span class="rank-badge rank-<?php echo $rank_class; ?>">
                <i class="fa-solid fa-trophy"></i>
                <?php echo htmlspecialchars($rank); ?>
            </span>
        </h1>

        <p class="state">
            <i class="fa-solid fa-location-dot" style="color:red"></i>
            <?php echo htmlspecialchars($host['state'] ?? ''); ?>
        </p>

        <p class="username">
            by <?php echo htmlspecialchars($host['username'] ?? ''); ?>
        </p>
    </div>
  </div>

  <!-- MAIN INFO GRID -->
  <div class="main-grid">

      <!-- ABOUT -->
      <div class="about-box">
          <?php echo nl2br(htmlspecialchars($host['about'] ?? 'No description provided.')); ?>
      </div>

      <!-- FOCUS AREA -->
      <div class="focus-box">
          <h3>Focus Area</h3>
          <p><?php echo htmlspecialchars($host['focus_area'] ?? ''); ?></p>
      </div>

  </div>

  <!-- CONTACT BOX -->
  <div class="contact-box">
      <div class="contact-row">
          <i class="fa-solid fa-phone"></i>
          <span><?php echo htmlspecialchars($host['phone_num'] ?? ''); ?></span>
      </div>

      <div class="contact-row">
          <i class="fa-solid fa-envelope"></i>
          <span><?php echo htmlspecialchars($host['biz_email'] ?? ''); ?></span>
      </div>
  </div>

  <!-- EXPERIENCES -->
  <section id="popular">
    <h3 class="exp-title">Approved Experiences</h3>
    <br>

    <div class="grid">

    <?php while ($exp = $experiences->fetch_assoc()): ?>

        <?php
            $first_img = !empty($exp['first_img'])
                ? "../host/uploads/experience/" . $exp['first_img']
                : "img/default.jpg";
        ?>

        <a href="approval_detail.php?id=<?php echo $exp['exp_id']; ?>" 
           class="card-link" style="text-decoration:none; color:inherit;">

            <article class="card">

                <!-- IMAGE -->
                <div class="card-media">
                    <img src="<?php echo htmlspecialchars($first_img); ?>" alt="Experience Image" />
                    <span class="badge"><?php echo htmlspecialchars($exp['category']); ?></span>
                </div>

                <!-- CONTENT -->
                <div class="content">
                    <h4><?php echo htmlspecialchars($exp['exp_title']); ?></h4>

                    <p class="meta">
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
