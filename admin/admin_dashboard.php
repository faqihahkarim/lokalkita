<?php
session_start();
require "script/db.php";

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.html");
    exit();
}

$admin_id = $_SESSION['admin_id'];

/* --------------------------------------------------
   1. FETCH ADMIN DATA
-------------------------------------------------- */
$stmt = $conn->prepare("SELECT name FROM admin_acc WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

$admin_name = $admin ? $admin['name'] : "Admin";

/* --------------------------------------------------
   2. DASHBOARD STATISTICS
-------------------------------------------------- */

// total hosts
$hostCount = $conn->query("SELECT COUNT(*) AS total FROM host_acc")->fetch_assoc()['total'];

// total users
$userCount = $conn->query("SELECT COUNT(*) AS total FROM user_acc")->fetch_assoc()['total'];

// pending experiences
$pendingCount = $conn->query("SELECT COUNT(*) AS total FROM experience WHERE status='Pending'")->fetch_assoc()['total'];

// count all experiences
$totalExp = $conn->query("SELECT COUNT(*) AS total FROM experience")->fetch_assoc()['total'];

// count rejected experiences
$rejectedExp = $conn->query("SELECT COUNT(*) AS total FROM experience WHERE status='Rejected'")->fetch_assoc()['total'];

// average rating platform-wide
$avgRating = $conn->query("SELECT AVG(rating) AS avg FROM experience")->fetch_assoc()['avg'];
$avgRating = $avgRating ? number_format($avgRating, 1) : "–";

/* --------------------------------------------------
   3. CHART DATA: EXPERIENCE TYPES
-------------------------------------------------- */
$cat_sql = "SELECT category, COUNT(*) AS total 
            FROM experience 
            WHERE status='Approved'
            GROUP BY category";
$cat_result = $conn->query($cat_sql);

$cat_labels = [];
$cat_values = [];

while ($row = $cat_result->fetch_assoc()) {
    $cat_labels[] = $row['category'];
    $cat_values[] = (int)$row['total'];
}

/* --------------------------------------------------
   4. RECENT ACTIVITY LOGS
-------------------------------------------------- */
// Fetch 8 latest admin logs
$logQuery = $conn->prepare("
    SELECT action, log_time 
    FROM admin_logs 
    ORDER BY log_time DESC 
    LIMIT 8
");
$logQuery->execute();
$logs = $logQuery->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - LokalKita</title>
  <link rel="stylesheet" href="dash.css">
      <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />

  <!-- Fonts & Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="logo">LokalKita</div>

    <ul class="nav-links">
        <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
        <li><a href="approval.php"><i class="fas fa-box"></i> <span>Approval</span></a></li>
        <li><a href="user_list.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
        <li><a href="host_list.php"><i class="fas fa-user-tie"></i> <span>Hosts</span></a></li>
    </ul>

    <a href="admin_login.html" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>

<!-- Toggle Button -->
<button id="toggle-btn" class="toggle-btn"><i class="fas fa-bars"></i></button>

<!-- Main Content -->
<main class="main">
    <!-- Top header -->
    <header class="topbar">
        <div>
            <h2>Hi, Admin <?php echo htmlspecialchars($admin_name); ?> </h2>
            <p class="subtitle">Here’s a quick overview of what’s happening on LokalKita today.</p>
        </div>
    </header>

    <!-- Stats Cards -->
    <section class="cards cards-grid">
        <div class="card stat-card">
          <div class="card-icon"><i class="fas fa-user-tie"></i></div>
          <div>
            <h4>Total Hosts</h4>
            <p><?php echo $hostCount; ?></p>
          </div>
        </div>

        <div class="card stat-card">
          <div class="card-icon"><i class="fas fa-users"></i></div>
          <div>
            <h4>Total Users</h4>
            <p><?php echo $userCount; ?></p>
          </div>
        </div>

        <div class="card stat-card highlight">
          <div class="card-icon"><i class="fas fa-box-open"></i></div>
          <div>
            <h4>Pending Experiences</h4>
            <p><?php echo $pendingCount; ?></p>
          </div>
        </div>

        <div class="card stat-card">
          <div class="card-icon"><i class="fas fa-star"></i></div>
          <div>
            <h4>Avg Rating</h4>
            <p><?php echo $avgRating; ?></p>
          </div>
        </div>
    </section>

    <!-- Middle Grid: Chart + System Health -->
    <section class="middle-grid">
        <!-- Chart Section -->
        <section class="analytics">
          <div class="section-header">
            <h3>Experiences by Type</h3>
            <span class="badge-small"><?php echo $totalExp; ?> total experiences</span>
          </div>
          <div class="chart-wrapper">
            <canvas id="trendChart"></canvas>
          </div>
        </section>

        <!-- System Health -->
        <section class="system-health">
            <h3>System Health</h3>

            <div class="health-grid">
                <div class="health-card">
                    <i class="fa-solid fa-database"></i>
                    <div>
                        <h4>Database</h4>
                        <p>Operational • <?php echo $totalExp; ?> experiences</p>
                    </div>
                </div>

                <div class="health-card">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div>
                        <h4>Rejected Content</h4>
                        <p><?php echo $rejectedExp; ?> experiences need attention</p>
                    </div>
                </div>

                <div class="health-card">
                    <i class="fa-solid fa-shield-halved"></i>
                    <div>
                        <h4>Platform Rating</h4>
                        <p>Average rating: <?php echo $avgRating; ?></p>
                    </div>
                </div>
            </div>
        </section>
    </section>

    <!-- Activity Logs -->
    <section class="activity-log">
        <h3>Recent Admin Activity</h3>
        <ul class="log-list">
            <?php if ($logs->num_rows === 0): ?>
                <li><span class="log-text">No recent admin actions logged yet.</span></li>
            <?php endif; ?>

            <?php while ($log = $logs->fetch_assoc()): ?>
                <li>
                    <i class="fa-solid fa-clock"></i>
                    <span class="log-text"><?php echo htmlspecialchars($log['action']); ?></span>
                    <span class="log-time"><?php echo $log['log_time']; ?></span>
                </li>
            <?php endwhile; ?>
        </ul>
    </section>

</main>

<script>
// Sidebar toggle
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('toggle-btn');

toggleBtn.addEventListener('click', () => {
  sidebar.classList.toggle('collapsed');
});

// Chart
const ctx = document.getElementById('trendChart').getContext('2d');

new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($cat_labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    datasets: [{
      label: 'Experiences',
      data: <?php echo json_encode($cat_values); ?>,
      backgroundColor: '#57bfffff',
    
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: { 
        beginAtZero: true,
        ticks: { precision: 0 }
      }
    },
    plugins: {
      legend: { display: false }
    }
  }
});
</script>

</body>
</html>
