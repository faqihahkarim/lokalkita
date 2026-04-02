<?php
session_start();
require "script/db.php";

// Ensure admin logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.html");
    exit();
}

// Optional search
$search = $_GET['search'] ?? "";

// Build WHERE
$where = "WHERE 1=1";
if (!empty($search)) {
    $safe = "%" . $conn->real_escape_string($search) . "%";
    $where .= " AND (h.biz_name LIKE '$safe' OR h.username LIKE '$safe' OR h.state LIKE '$safe')";
}


/*pagination*/
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count total users (for pagination)
$countSQL = $conn->query("
    SELECT COUNT(*) AS total 
    FROM host_acc h
    $where
");

$totalUsers = $countSQL->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);


/* ===============================
   FETCH HOSTS + BASIC COUNTS
   =============================== */
$sql = "
    SELECT 
        h.host_id,
        h.username,
        h.biz_name,
        h.state,
        h.phone_num,
        h.biz_email,
        h.status,
        h.last_login,
        COALESCE((SELECT COUNT(*) FROM experience e WHERE e.host_id = h.host_id), 0) AS total_exp,
        COALESCE((SELECT COUNT(*) FROM experience e WHERE e.host_id = h.host_id AND e.status = 'Approved'), 0) AS approved_exp,
        COALESCE((SELECT COUNT(*) FROM experience e WHERE e.host_id = h.host_id AND e.status = 'Pending'), 0) AS pending_exp
    FROM host_acc h
    $where
    ORDER BY h.biz_name ASC
    LIMIT $limit OFFSET $offset
";
$result = $conn->query($sql);

$hosts = [];
while ($row = $result->fetch_assoc()) {

    /* ===============================
       DETERMINE ACTIVITY STATUS
       =============================== */

    $host_id = $row['host_id'];
    $last_login = $row['last_login'];
    $stored_status = $row['status']; // can be "Suspended"

    /* --- DAYS SINCE LAST LOGIN --- */
    $days_since_login = null;
    if (!empty($last_login)) {
        $days_since_login = (strtotime(date("Y-m-d")) - strtotime($last_login)) / 86400;
    }

    /* --- CHECK REJECTED >14 DAYS --- */
    $sqlReject = "
        SELECT COUNT(*) AS rejectCount
        FROM experience
        WHERE host_id = $host_id
        AND status = 'Rejected'
        AND DATE(created_at) <= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    ";
    $rejectRes = $conn->query($sqlReject);
    $rejectData = $rejectRes->fetch_assoc();
    $rejectCount = $rejectData['rejectCount'];

    /* --- DETERMINE STATUS LOGIC --- */
    if ($stored_status === "Suspended") {
        $calculatedStatus = "Suspended";

    } elseif ($days_since_login !== null && $days_since_login > 60) {
        $calculatedStatus = "Inactive";

    } elseif ($rejectCount > 0) {
        $calculatedStatus = "Unresponsive";

    } else {
        $calculatedStatus = "Active";
    }

    // Save status into array
    $row["calculated_status"] = $calculatedStatus;

    // (Optional) Auto update DB so profile shows same status
    $conn->query("UPDATE host_acc SET status='$calculatedStatus' WHERE host_id=$host_id");

    $hosts[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Host List - LokalKita</title>
    <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />
  <link rel="stylesheet" href="dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />

  <style>
    .status.active { background:#28a745; color:white; padding:5px 10px; border-radius:8px; }
    .status.inactive { background:#6c757d; color:white; padding:5px 10px; border-radius:8px; }
    .status.unresponsive { background:#ffc107; color:black; padding:5px 10px; border-radius:8px; }
    .status.suspended { background:#dc3545; color:white; padding:5px 10px; border-radius:8px; }
  </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="logo">LokalKita</div>
    <ul class="nav-links">
        <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
        <li><a href="approval.php"><i class="fas fa-box"></i> <span>Approval</span></a></li>
        <li><a href="user_list.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
        <li><a href="host_list.php" class="active"><i class="fas fa-user-tie"></i> <span>Hosts</span></a></li>
    </ul>
    <a href="admin_login.html" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>

<button id="toggle-btn" class="toggle-btn">☰</button>

<main class="main">
    <header>
        <h2>Host List</h2>

        <form method="GET" action="" style="display:flex; gap:10px;">
            <input type="text" name="search" placeholder="Search host, business, state..."
                   class="search" value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn-details" style="height:40px;">Search</button>
        </form>
    </header>

    <section class="table-section">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Business Name</th>
                    <th>Username</th>
                    <th>State</th>
                    <th>Phone</th>
                    <th>Business Email</th>
                    <th>Experiences</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php if (empty($hosts)): ?>
                <tr><td colspan="9" style="text-align:center; opacity:0.7;">No hosts found.</td></tr>
            <?php else: $i=1; ?>
                <?php foreach ($hosts as $h): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($h['biz_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($h['username']?? ''); ?></td>
                    <td><?php echo htmlspecialchars($h['state'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($h['phone_num']?? ''); ?></td>
                    <td><?php echo htmlspecialchars($h['biz_email'] ?? ''); ?></td>

                    <td>
                        Total: <?php echo $h['total_exp']; ?><br>
                        <small>
                            Approved: <?php echo $h['approved_exp']; ?> · 
                            Pending: <?php echo $h['pending_exp']; ?>
                        </small>
                    </td>

                    <td>
                        <?php 
                            $statusClass = strtolower($h["calculated_status"]);
                        ?>
                        <span class="status <?php echo $statusClass; ?>">
                            <?php echo $h["calculated_status"]; ?>
                        </span>
                    </td>

                    <td>
                        <a href="host_profile.php?id=<?php echo $h['host_id']; ?>" 
                           class="btn-details" style="padding:6px 12px;">
                           View
                        </a>
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
