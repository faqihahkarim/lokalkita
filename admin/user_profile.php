<?php
session_start();
require "script/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.html");
    exit();
}

$user_id = $_GET['id'] ?? 0;
if ($user_id == 0) {
    die("Invalid user.");
}

/* ===============================
   FETCH USER INFORMATION
================================= */
$userSQL = $conn->prepare("SELECT * FROM user_acc WHERE user_id = ?");
$userSQL->bind_param("i", $user_id);
$userSQL->execute();
$user = $userSQL->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}

/* ===============================
   USER STATISTICS
================================= */

// Likes
$likeSQL = $conn->query("SELECT COUNT(*) AS totalLikes FROM user_like WHERE user_id = $user_id");
$totalLikes = $likeSQL->fetch_assoc()['totalLikes'];

// Saves
$saveSQL = $conn->query("SELECT COUNT(*) AS totalSaves FROM user_save WHERE user_id = $user_id");
$totalSaves = $saveSQL->fetch_assoc()['totalSaves'];

// Reviews
$reviewSQL = $conn->query("SELECT COUNT(*) AS totalReviews, AVG(rating) AS avgRating 
                           FROM review WHERE user_id = $user_id");
$reviewData = $reviewSQL->fetch_assoc();
$totalReviews = $reviewData['totalReviews'];
$avgRating = $reviewData['avgRating'] ? number_format($reviewData['avgRating'], 1) : "0.0";

// Recent activity (last 5 reviews)
$recentSQL = $conn->query("
    SELECT r.*, e.exp_title 
    FROM review r
    JOIN experience e ON r.exp_id = e.exp_id
    WHERE r.user_id = $user_id
    ORDER BY r.date_create DESC
    LIMIT 5
");
$recent = [];
while ($r = $recentSQL->fetch_assoc()) {
    $recent[] = $r;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - LokalKita</title>
    <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />

    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />

    <style>
        .profile-container {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: 30px auto;
        }

        .profile-header {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .profile-header img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
        }

        .stats-box {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            flex: 1;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-card h3 {
            margin: 5px 0;
            font-size: 28px;
            color: #2b6cb0;
        }

        .recent-box {
            margin-top: 30px;
        }

        .recent-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .btn-back {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: #2b6cb0;
            color: white;
            border-radius: 8px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="logo">LokalKita</div>
      <ul class="nav-links">
        <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
        <li><a href="approval.php"><i class="fas fa-box"></i> <span>Approval</span></a></li>
        <li><a href="user_list.php" class="active"><i class="fas fa-users"></i> <span>Users</span></a></li>
        <li><a href="host_list.php"><i class="fas fa-user-tie"></i> <span>Hosts</span></a></li>
      </ul>

    <a href="admin_login.html" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>

<button id="toggle-btn" class="toggle-btn">☰</button>


<main class="main">

    <a href="user_list.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>

    <div class="profile-container">

        <!-- Header -->
        <div class="profile-header">
            <?php if (!empty($user['profile_pic'])): ?>
                <img src="../uploads/<?php echo htmlspecialchars($user['profile_pic']); ?>">
            <?php else: ?>
                <img src="https://cdn-icons-png.flaticon.com/512/3177/3177440.png">
            <?php endif; ?>

            <div>
                <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                <p><?php echo htmlspecialchars($user['reg_email']); ?></p>
                <p><strong>State:</strong> <?php echo htmlspecialchars($user['state'] ?? '-'); ?></p>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-box">
            <div class="stat-card">
                <h3><?php echo $totalLikes; ?></h3>
                <p>Total Likes</p>
            </div>

            <div class="stat-card">
                <h3><?php echo $totalSaves; ?></h3>
                <p>Total Saves</p>
            </div>

            <div class="stat-card">
                <h3><?php echo $totalReviews; ?></h3>
                <p>Total Reviews</p>
            </div>

            <div class="stat-card">
                <h3><?php echo $avgRating; ?></h3>
                <p>Avg Rating Given</p>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="recent-box">
            <h3>Recent Reviews</h3>

            <?php if (empty($recent)): ?>
                <p style="opacity:0.6;">No recent activity.</p>
            <?php else: ?>
                <?php foreach ($recent as $r): ?>
                    <div class="recent-item">
                        <strong><?php echo htmlspecialchars($r['exp_title']); ?></strong><br>
                        Rating: <?php echo $r['rating']; ?>/5 <br>
                        <small><?php echo $r['date_create']; ?></small><br>
                        <p><?php echo htmlspecialchars($r['review']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

    </div>

</main>

</body>
</html>
