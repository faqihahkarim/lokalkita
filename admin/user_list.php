<?php
session_start();
require "script/db.php";

// Ensure admin logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.html");
    exit();
}

$search = $_GET['search'] ?? "";

$where = "WHERE 1=1";
if (!empty($search)) {
    $safe = "%" . $conn->real_escape_string($search) . "%";
    $where .= " AND (u.username LIKE '$safe' OR u.reg_email LIKE '$safe' OR u.state LIKE '$safe')";
}

/*pagination*/
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count total users (for pagination)
$countSQL = $conn->query("
    SELECT COUNT(*) AS total 
    FROM user_acc u
    $where
");

$totalUsers = $countSQL->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);

$sql = "
    SELECT 
        u.user_id,
        u.username,
        u.reg_email,
        u.phone_num,
        u.state,
        u.min_budget,
        u.max_budget,
        u.profile_pic,
        u.status,
        u.last_login,
        u.created_at
    FROM user_acc u
    $where
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);

$users = [];
while ($row = $result->fetch_assoc()) {

    // Calculate activity status
    $stored_status = $row['status'];
    $created_at = $row['created_at'];
    $last_login = $row['last_login'];

    $isNew = false;
    $isInactive = false;

    // New user (< 7 days)
    if ($created_at) {
        $daysSinceJoin = (strtotime("today") - strtotime($created_at)) / 86400;
        if ($daysSinceJoin <= 7) {
            $isNew = true;
        }
    }

    // Inactive (> 30 days no login)
    if ($last_login) {
        $daysSinceLogin = (strtotime("today") - strtotime($last_login)) / 86400;
        if ($daysSinceLogin > 30) {
            $isInactive = true;
        }
    }

    if ($stored_status === "Suspended") {
        $finalStatus = "Suspended";
    } elseif ($isNew) {
        $finalStatus = "New User";
    } elseif ($isInactive) {
        $finalStatus = "Inactive";
    } else {
        $finalStatus = "Active";
    }

    // Save to array
    $row["final_status"] = $finalStatus;
    $users[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User List - LokalKita</title>
    <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />

  <link rel="stylesheet" href="dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />

  <style>
    .status { padding: 5px 10px; border-radius: 6px; }
    .active { background:#28a745; color:white; }
    .inactive { background:#6c757d; color:white; }
    .new { background:#0d6efd; color:white; }
    .suspended { background:#dc3545; color:white; }
  </style>
</head>
<body>

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
    <header>
        <h2>User List</h2>

        <form method="GET" action="" style="display:flex; gap:10px;">
            <input type="text" name="search" placeholder="Search user..." class="search"
                   value="<?php echo htmlspecialchars($search ?? '') ?>">
            <button class="btn-details" style="height:40px;">Search</button>
        </form>
    </header>

    <section class="table-section">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>State</th>
            <th>Date Joined</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>

        <tbody>
        <?php if (empty($users)): ?>
            <tr><td colspan="8" style="text-align:center;">No users found.</td></tr>
        <?php else: ?>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?php echo $u['user_id']; ?></td>
                <td><?php echo htmlspecialchars($u['username']); ?></td>
                <td><?php echo htmlspecialchars($u['reg_email']); ?></td>
                <td><?php echo htmlspecialchars($u['state'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($u['created_at']); ?></td>

                <td>
                    <?php
                        $class = strtolower(str_replace(" ", "", $u['final_status']));
                    ?>
                    <span class="status <?php echo $class; ?>">
                        <?php echo $u['final_status']; ?>
                    </span>
                </td>

                <td>
                    <a href="user_profile.php?id=<?php echo $u['user_id']; ?>" 
                       class="btn-details" style="padding:6px 12px;">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>

      <!--pagnination-->
      <div style="margin-top:20px; text-align:center;">
        <?php if ($page > 1): ?>
            <a class="btn-details" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
              ← Prev
            </a>
        <?php endif; ?>

        <span style="margin: 0 15px; font-weight:600;">
            Page <?php echo $page; ?> of <?php echo $totalPages; ?>
        </span>

        <?php if ($page < $totalPages): ?>
            <a class="btn-details" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
              Next →
            </a>
        <?php endif; ?>
    </div>

    </section>
</main>

<script>
const toggleBtn = document.getElementById("toggle-btn");
const sidebar = document.getElementById("sidebar");
toggleBtn.addEventListener("click", () => sidebar.classList.toggle("collapsed"));
</script>

</body>
</html>
