<?php
session_start();
require 'script/db.php';

if (!isset($_SESSION['host_id'])) {
    header("Location: host_login.html");
    exit();
}

$host_id = $_SESSION['host_id'];

/* ----------------------------------------------
   FETCH EXPERIENCES FOR THIS HOST
----------------------------------------------- */
$sql = "SELECT e.*, 
       (SELECT img_path FROM experience_images 
        WHERE exp_id = e.exp_id ORDER BY seq_no ASC LIMIT 1) AS thumbnail
       FROM experience e
       WHERE host_id = ?
       ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $host_id);
$stmt->execute();
$result = $stmt->get_result();

$experiences = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Experiences - LokalKita</title>
  <link rel="stylesheet" href="dashboard.css">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />

  <style>
    .status.pending { background: #ffeb99; color: #6e5700; padding:5px 10px; border-radius:6px; }
    .status.approved { background: #a8f5c6; color:#005f36; padding:5px 10px; border-radius:6px; }
    .status.rejected { background: #ffb3b3; color:#8c0000; padding:5px 10px; border-radius:6px; }
    .status.draft { background: #dcdcdc; color:#555; padding:5px 10px; border-radius:6px; }

    .approval-card {
        display: flex;
        align-items: center;
        background: #fff;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .approval-card img {
        width: 120px;
        height: 90px;
        object-fit: cover;
        border-radius: 10px;
        margin-right: 15px;
    }

    .approval-info {
        flex: 1;
    }

    .approval-info h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .approval-info p {
        margin: 6px 0;
        font-size: 14px;
        color: #555;
    }

    .approval-info small {
        color: #888;
        font-size: 13px;
    }

    .price {
        font-weight: 600;
        display: block;
        margin-top: 6px;
    }

    .btn-details, .btn-edit, .btn-delete {
        margin-left: 10px;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 14px;
        text-decoration: none;
        color: #fff;
    }

    .btn-details { background: #3498db; }
    .btn-edit { background: #f1c40f; color:#000; }
    .btn-delete { background: #e74c3c; }
  </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="logo">LokalKita</div>

    <ul class="nav-links">

        <li>
          <a href="host_dashboard.php">
            <i class="fas fa-home"></i> <span>Dashboard</span>
          </a>
        </li>

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

        <li>
          <a href="list.php" class="active">
            <i class="fas fa-list"></i> <span>My Contents</span>
          </a>
        </li>

        <li>
          <a href="add_content.php">
            <i class="fas fa-plus-circle"></i> <span>New Content</span>
          </a>
        </li>

    </ul>

    <a href="host_login.html" class="logout">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</aside>

<button id="toggle-btn" class="toggle-btn">☰</button>

<main class="main">
    <header>
        <h2>My Experiences</h2>
        
    </header>

    <div class="content-wrap">
        <section class="approval-list">

        <?php if (empty($experiences)): ?>
            <p>No experiences submitted yet.</p>

        <?php else: ?>
            <?php foreach ($experiences as $exp): ?>
                <div class="approval-card">

                    <!-- Thumbnail -->
                    <img src="<?php 
                          if ($exp['thumbnail']) {
                              echo 'uploads/experience/' . $exp['thumbnail'];
                          } else {
                              echo 'img/default_exp.png'; 
                          }
                      ?>" 
                      class="thumb">


                    <div class="approval-info">
                        <h3><?php echo $exp['exp_title']; ?></h3>
                        <p><?php echo $exp['exp_desc']; ?></p>
                        <small><?php echo $exp['category']; ?> · <?php echo $exp['state']; ?> (<?php echo $exp['created_at']; ?>)</small>
                        

                        <span class="price">
                            RM <?php echo number_format($exp['min_price'], 2); ?>
                            -
                            RM <?php echo number_format($exp['max_price'], 2); ?>
                        </span>

                        <div class="status <?php echo strtolower($exp['status']); ?>">
                            <?php echo ucfirst($exp['status']); ?>
                        </div>
                    </div>

                    <a href="detail.php?id=<?php echo $exp['exp_id']; ?>" class="btn-details">Details</a>
                    <a href="edit_content.php?id=<?php echo $exp['exp_id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                    <a href="script/delete_content.php?id=<?php echo $exp['exp_id']; ?>"
                      onclick="return confirm('Are you sure you want to delete this experience? This action cannot be undone.');"
                      class="btn-delete">
                      <i class="fas fa-trash-alt"></i>
                    </a>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        </section>
    </div>
</main>

<script>
document.getElementById("toggle-btn").addEventListener("click", () => {
  document.getElementById("sidebar").classList.toggle("collapsed");
});

document.querySelectorAll(".submenu-toggle").forEach(btn => {
  btn.addEventListener("click", function(e) {
    e.preventDefault();
    this.parentElement.classList.toggle("open");

    const submenu = this.parentElement.querySelector(".submenu");
    submenu.style.display = submenu.style.display === "block" ? "none" : "block";
  });
});
</script>

</body>
</html>
