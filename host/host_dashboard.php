<?php
session_start();
require 'script/db.php';

// Redirect if not logged in
if (!isset($_SESSION['host_id'])) {
    header("Location: host_login.html");
    exit();
}

$host_id = $_SESSION['host_id'];
$host_name = $_SESSION['host_name'];

// ---------------------------------------------
// 1. TOTAL APPROVED CONTENTS
// ---------------------------------------------
$q1 = $conn->prepare("
    SELECT COUNT(*) 
    FROM experience 
    WHERE host_id = ? AND status = 'Approved'
");
$q1->bind_param("i", $host_id);
$q1->execute();
$q1->bind_result($totalContents);
$q1->fetch();
$q1->close();


// ---------------------------------------------
// 2. TOTAL DRAFTS
// ---------------------------------------------
$q2 = $conn->prepare("
    SELECT COUNT(*) 
    FROM experience 
    WHERE host_id=? AND status='Draft'
");
$q2->bind_param("i", $host_id);
$q2->execute();
$q2->bind_result($drafts);
$q2->fetch();
$q2->close();


// ---------------------------------------------
// 3. TOTAL PENDING
// ---------------------------------------------
$q3 = $conn->prepare("
    SELECT COUNT(*) 
    FROM experience 
    WHERE host_id=? AND status='Pending'
");
$q3->bind_param("i", $host_id);
$q3->execute();
$q3->bind_result($pendingApproval);
$q3->fetch();
$q3->close();


// ---------------------------------------------
// 4. TOTAL LIKES (user_like table)
// ---------------------------------------------
$q4 = $conn->prepare("
    SELECT COUNT(*) 
    FROM user_like 
    WHERE exp_id IN (
        SELECT exp_id FROM experience WHERE host_id = ?
    )
");
$q4->bind_param("i", $host_id);
$q4->execute();
$q4->bind_result($totalLikes);
$q4->fetch();
$q4->close();


// ---------------------------------------------
// 5. RECENT HOST NOTIFICATIONS
// ---------------------------------------------
$sql = "
    SELECT 
        notif_id, message, type, created_at,
        TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS mins
    FROM host_notifications
    WHERE host_id=?
    ORDER BY created_at DESC
    LIMIT 5
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $host_id);
$stmt->execute();
$notifications = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Host Dashboard - LokalKita</title>
  <link rel="stylesheet" href="dashboard.css">
      <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />


  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .delete-note {
    background: transparent;
    border: none;
    color: #888;
    font-size: 18px;
    cursor: pointer;
    margin-left: 10px;
    float: right;
}

    .delete-note:hover {
        color: red;
    }
  </style>
</head>

<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="logo">LokalKita</div>

    <ul class="nav-links">
        <li><a href="host_dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>

        <!-- Profile Dropdown -->
        <li class="has-submenu">
            <a href="#" class="submenu-toggle">
                <i class="fas fa-user"></i> 
                <span>Profile</span>
                <i class="fas fa-chevron-down arrow"></i>
            </a>

            <ul class="submenu">
                <li><a href="myprofile.php"><i class="fas fa-id-card"></i> My Profile</a></li>
                <li><a href="profile.php"><i class="fas fa-edit"></i> Edit Profile</a></li>
            </ul>
        </li>

        <li><a href="list.php"><i class="fas fa-list"></i><span>My Contents</span></a></li>
        <li><a href="add_content.php"><i class="fas fa-plus-circle"></i><span>New Content</span></a></li>
    </ul>

    <a href="host_login.html" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>


<!-- Toggle Button -->
<button id="toggle-btn" class="toggle-btn"><i class="fas fa-bars"></i></button>


<!-- Main Content -->
<main class="main">

    <header class="topbar">
      <h2>Welcome back, <?php echo htmlspecialchars($host_name); ?>!</h2>
    </header>

    <!-- Stats Cards -->
    <section class="cards">
      <div class="card">
        <i class="fas fa-file-alt"></i>
        <h4>Total Contents</h4>
        <p><?php echo $totalContents; ?></p>
      </div>

      <div class="card">
        <i class="fas fa-heart"></i>
        <h4>Total Likes</h4>
        <p><?php echo $totalLikes; ?></p>
      </div>

      <div class="card">
        <i class="fas fa-save"></i>
        <h4>Drafts</h4>
        <p><?php echo $drafts; ?></p>
      </div>

      <div class="card">
        <i class="fas fa-hourglass-half"></i>
        <h4>Pending Approval</h4>
        <p><?php echo $pendingApproval; ?></p>
      </div>
    </section>


    <!-- Recent Activity -->
    <div class="dashboard-grid">
      <section class="quick-activity">
        <h3>Recent Activity</h3>
        <ul>

          <?php if ($notifications->num_rows === 0): ?>
              <li class="info">No recent notifications.</li>
          <?php endif; ?>

          <?php while($note = $notifications->fetch_assoc()): ?>
            <li class="<?php echo $note['type']; ?>" id="note-<?php echo $note['notif_id']; ?>">

              <i class="
                  <?php echo $note['type']=='success' ? 'fas fa-check-circle' : ''; ?>
                  <?php echo $note['type']=='error'   ? 'fas fa-times-circle' : ''; ?>
                  <?php echo $note['type']=='info'    ? 'fas fa-info-circle'  : ''; ?>">
              </i>

              <?php echo htmlspecialchars($note['message']); ?>

              <span class="time">
                <?php
                  $totalMins = $note['mins'];

                  if ($totalMins < 60) {
                      echo $totalMins . " min ago";

                  } elseif ($totalMins < 1440) { // 24 hours * 60
                      echo floor($totalMins / 60) . " hours ago";

                  } else {
                      echo floor($totalMins / 1440) . " days ago";
                  }
                ?>
              </span>

              <!-- delete notification after reading -->
              <button class="delete-note" onclick="deleteNotification(<?php echo $note['notif_id']; ?>)">
                  ×
              </button>

            </li>

          <?php endwhile; ?>

        </ul>
      </section>
    </div>


    <!-- Quick Actions -->
    <section class="quick-actions">
      <h3>Quick Actions</h3>
      <div class="actions-grid">
        <a href="add_content.php" class="action-btn"><i class="fas fa-plus-circle"></i> Add New Content</a>
        <a href="profile.php" class="action-btn"><i class="fas fa-user-cog"></i> Edit Profile</a>
        <a href="myprofile.php" class="action-btn"><i class="fa-solid fa-address-card"></i> My Profile</a>
      </div>
    </section>

</main>


<script>
// Handle sidebar submenu
document.querySelectorAll(".submenu-toggle").forEach(btn => {
    btn.addEventListener("click", function(e) {
        e.preventDefault();
        this.parentElement.classList.toggle("open");

        const submenu = this.parentElement.querySelector(".submenu");
        submenu.style.display = submenu.style.display === "block" ? "none" : "block";
    });
});


function deleteNotification(id) {
    fetch("script/delete_notification.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "notif_id=" + id
    })
    .then(res => res.text())
    .then(data => {
        if (data === "OK") {
            document.getElementById("note-" + id).remove();
        }
    });
}

</script>

<script src="dashboard.js"></script>

</body>
</html>
