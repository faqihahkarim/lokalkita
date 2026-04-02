
<?php
session_start();
require "script/db.php";

// Ensure admin logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.html");
    exit();
}

/* --------------------------------------------------
   FILTER HANDLING
-------------------------------------------------- */

$search = $_GET['search'] ?? "";
$status = $_GET['status'] ?? "Pending";
$sort   = $_GET['sort']   ?? "newest";

$where = "WHERE e.status != 'Draft'"; // Always hide drafts

if (!empty($search)) {
    $safe = "%" . $conn->real_escape_string($search) . "%";
    $where .= " AND (e.exp_title LIKE '$safe' 
                OR e.category LIKE '$safe' 
                OR e.state LIKE '$safe')";
}

if ($status !== "All") {
    $safeStatus = $conn->real_escape_string($status);
    $where .= " AND e.status = '$safeStatus'";
}

/* --------------------------------------------------
   SORT HANDLING
-------------------------------------------------- */
$orderSQL = "ORDER BY e.created_at DESC"; // default newest

if ($sort === "oldest") {
    $orderSQL = "ORDER BY e.created_at ASC";
} elseif ($sort === "price_high") {
    $orderSQL = "ORDER BY e.max_price DESC";
} elseif ($sort === "price_low") {
    $orderSQL = "ORDER BY e.min_price ASC";
}

/* paginatioon*/

$itemsPerPage = 6; // Show 6 experience cards per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $itemsPerPage;

/* Count total results for pagination */
$countSQL = "
    SELECT COUNT(*) AS total
    FROM experience e
    JOIN host_acc h ON e.host_id = h.host_id
    $where
";
$totalResult = $conn->query($countSQL)->fetch_assoc()['total'];
$totalPages  = max(1, ceil($totalResult / $itemsPerPage));

/* --------------------------------------------------
   FETCH EXPERIENCES (WITH LIMIT/OFFSET)
-------------------------------------------------- */

$sql = "
    SELECT 
        e.exp_id,
        e.exp_title,
        e.exp_desc,
        e.category,
        e.state,
        e.min_price,
        e.max_price,
        e.status,
        e.created_at,
        h.biz_name AS host_name,
        (
            SELECT img_path 
            FROM experience_images 
            WHERE exp_id = e.exp_id 
            ORDER BY seq_no ASC 
            LIMIT 1
        ) AS thumbnail
    FROM experience e
    JOIN host_acc h ON e.host_id = h.host_id
    $where
    $orderSQL
    LIMIT $itemsPerPage OFFSET $offset
";

$result = $conn->query($sql);

$experiences = [];
while ($row = $result->fetch_assoc()) {
    $experiences[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Experience Approval - LokalKita</title>
    <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
    <style>
        .status.pending { color: #ffda46ff; border-radius:6px; }
        .status.approved { color: #488760ff; border-radius:6px; }
        .status.rejected { color: #eb1414ff;   border-radius:6px; }
    </style>
</head>

<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="logo">LokalKita</div>

    <ul class="nav-links">
        <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
        <li><a href="approval.php" class="active"><i class="fas fa-box"></i> <span>Approval</span></a></li>
        <li><a href="user_list.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
        <li><a href="host_list.php"><i class="fas fa-user-tie"></i> <span>Hosts</span></a></li>
    </ul>

    <a href="admin_login.html" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>

<!-- Toggle Button -->
<button id="toggle-btn" class="toggle-btn">☰</button>

<!-- MAIN CONTENT -->
<main class="main">
    <header>
        <h2>Experience Approval</h2>

        <!-- FILTER FORM -->
        <form method="GET" action="" style="display:flex; gap:10px; flex-wrap:wrap;">
            <!-- Search -->
            <input 
                type="text" 
                name="search" 
                placeholder="Search title, category, state..."
                class="search"
                value="<?php echo htmlspecialchars($search); ?>">

            <!-- Status Filter -->
            <select name="status" class="search" style="width:180px;">
                <option value="All"      <?php if($status=="All") echo "selected"; ?>>All</option>
                <option value="Pending"  <?php if($status=="Pending") echo "selected"; ?>>Pending</option>
                <option value="Approved" <?php if($status=="Approved") echo "selected"; ?>>Approved</option>
                <option value="Rejected" <?php if($status=="Rejected") echo "selected"; ?>>Rejected</option>
            </select>

            <!-- Sort Filter -->
            <select name="sort" class="search" style="width:180px;">
                <option value="newest"     <?php if($sort=="newest") echo "selected"; ?>>Newest first</option>
                <option value="oldest"     <?php if($sort=="oldest") echo "selected"; ?>>Oldest first</option>
                <option value="price_low"  <?php if($sort=="price_low") echo "selected"; ?>>Price: Low to High</option>
                <option value="price_high" <?php if($sort=="price_high") echo "selected"; ?>>Price: High to Low</option>
            </select>

            <button class="btn-details" style="height:40px;">Filter</button>
        </form>
    </header>

    <div class="content-wrap">
        <section class="approval-list">

            <?php if (empty($experiences)): ?>
                <p style="opacity:0.6; text-align:center; width:100%;">No experiences found.</p>

            <?php else: ?>
                <?php foreach ($experiences as $exp): ?>
                    
                    <div class="approval-card">

                        <!-- Thumbnail -->
                        <img src="<?php 
                            echo $exp['thumbnail'] 
                                ? '../host/uploads/experience/' . $exp['thumbnail']
                                : '../img/default_exp.png';
                        ?>" alt="Thumbnail">

                        <div class="approval-info">
                            <h3><?php echo htmlspecialchars($exp['exp_title']); ?></h3>
                            <h4>by <?php echo htmlspecialchars($exp['host_name']); ?></h4>
                            <p><?php echo htmlspecialchars($exp['exp_desc']); ?></p>

                            <small>
                                <?php echo htmlspecialchars($exp['category']); ?> · 
                                <?php echo htmlspecialchars($exp['state']); ?>  
                                (<?php echo $exp['created_at']; ?>)
                            </small>

                            <span class="price">
                                RM <?php echo number_format($exp['min_price'],2); ?> – 
                                RM <?php echo number_format($exp['max_price'],2); ?>
                            </span>

                            <!-- STATUS BADGE -->
                            <div class="status <?php echo strtolower($exp['status']); ?>">
                                <?php echo ucfirst($exp['status']); ?>
                            </div>
                        </div>

                        <a href="approval_detail.php?id=<?php echo $exp['exp_id']; ?>" class="btn-details">Details</a>

                    </div>

                <?php endforeach; ?>
            <?php endif; ?>

        </section>
    </div>

    <br>
    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <!-- Prev -->
        <?php if ($page > 1): ?>
            <a class="page-btn" 
               href="?search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page - 1; ?>">
                &laquo; Prev
            </a>
        <?php endif; ?>

        <!-- Page Numbers -->
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="page-number <?php if ($i == $page) echo 'active'; ?>"
               href="?search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&sort=<?php echo $sort; ?>&page=<?php echo $i; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <!-- Next -->
        <?php if ($page < $totalPages): ?>
            <a class="page-btn" 
               href="?search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page + 1; ?>">
                Next &raquo;
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</main>

<script>
const toggleBtn = document.getElementById("toggle-btn");
const sidebar   = document.getElementById("sidebar");

toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("collapsed");
});
</script>

</body>
</html>
