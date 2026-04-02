<?php
session_start();
require 'script/db.php';

// Validate experience ID
$exp_id = $_GET['id'] ?? 0;
if ($exp_id == 0) {
    die("Invalid experience ID.");
}

// ---------------------------------------------------------------------
// 1. FETCH EXPERIENCE DATA
// ---------------------------------------------------------------------
$sql = "SELECT * FROM experience WHERE exp_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exp_id);
$stmt->execute();
$result = $stmt->get_result();
$exp = $result->fetch_assoc();

if (!$exp) {
    die("Experience not found.");
}

// ---------------------------------------------------------------------
// 2. FETCH ALL IMAGES (ORDER BY seq_no)
// ---------------------------------------------------------------------
$sql2 = "SELECT * FROM experience_images WHERE exp_id = ? ORDER BY seq_no ASC";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $exp_id);
$stmt2->execute();
$images = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Experience Details - LokalKita</title>
      <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />

  <link rel="stylesheet" href="dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<aside class="sidebar" id="sidebar">
    <div class="logo">LokalKita</div>

    <ul class="nav-links">
        <li><a href="host_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>

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

        <li><a href="list.php"><i class="fas fa-list"></i> My Contents</a></li>
        <li><a href="add_content.php"><i class="fas fa-plus-circle"></i> New Content</a></li>
       
    </ul>

    <a href="host_login.html" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>


<button id="toggle-btn" class="toggle-btn">☰</button>


<main class="main">
    <header>
      <h2>Experience Details</h2>
    </header>

    <div class="approval-detail">

        <!-- IMAGES -->
        <div class="carousel-container">

          <!-- Main Image -->
          <div class="carousel-main">
              <button class="carousel-btn left" onclick="prevImage()">&#10094;</button>

              <img id="carouselMainImg" 
                  src="uploads/experience/<?php echo $images[0]['img_path'] ?? ''; ?>">

              <button class="carousel-btn right" onclick="nextImage()">&#10095;</button>
          </div>

          <!-- Thumbnails -->
          <div class="carousel-thumbs">
              <?php foreach ($images as $index => $img): ?>
                  <img class="thumb <?php echo $index === 0 ? 'active' : ''; ?>"
                      src="uploads/experience/<?php echo $img['img_path']; ?>"
                      onclick="showImage(<?php echo $index; ?>)">
              <?php endforeach; ?>
          </div>
      </div>


        <!-- DETAILS -->
        <h3><?php echo $exp['exp_title']; ?></h3>

        <p><?php echo $exp['exp_desc']; ?></p>

        <p><strong>Tags:</strong> <?php echo $exp['tags']; ?></p>
        <p><strong>Category:</strong> <?php echo $exp['category']; ?></p>
        <p><strong>State:</strong> <?php echo $exp['state']; ?></p>
        <p><strong>Location:</strong> <?php echo $exp['location']; ?></p>

        <p><strong>Price:</strong> RM <?php echo number_format($exp['min_price'], 2); ?> – RM <?php echo number_format($exp['max_price'], 2); ?></p>

        <p><strong>Operating Days:</strong> <?php echo $exp['open_day']; ?></p>
        <p><strong>Closing Days:</strong> <?php echo $exp['close_day']; ?></p>
        <p><strong>Hours:</strong> <?php echo $exp['operating_hours']; ?></p>

        <p><strong>Contact:</strong> <?php echo $exp['contact_info']; ?></p>
        <p><strong>Website Link:</strong>
            <?php if ($exp['web_link']): ?>
                <a href="<?php echo $exp['web_link']; ?>" target="_blank"><?php echo $exp['web_link']; ?></a>
            <?php else: ?>
                -
            <?php endif; ?>
        </p>

        <p><strong>Status:</strong> <?php echo $exp['status']; ?></p>

        <br>

        <h4>Admin Evaluation</h4>
        <div class="admin-box 
            <?php 
                if ($exp['status'] == 'Approved') echo 'approved'; 
                elseif ($exp['status'] == 'Rejected') echo 'rejected'; 
                else echo 'pending';
            ?>">

            <h3><i class="fa-solid fa-user-shield"></i> Admin Evaluation</h3>

            <p class="admin-status">
                <strong>Status:</strong> <?php echo $exp['status']; ?>
            </p>

            <p class="admin-comment">
                <?php echo $exp['admin_comment'] ? $exp['admin_comment'] : "No comments yet."; ?>
            </p>
        </div>


    </div>

    <a href="list.php" class="btn-back">← Back to list</a>

</main>


<script>
document.getElementById("toggle-btn").addEventListener("click", () => {
    document.getElementById("sidebar").classList.toggle("collapsed");
});
</script>

<script>
document.querySelectorAll(".submenu-toggle").forEach(btn => {
    btn.addEventListener("click", function(e) {
        e.preventDefault();
        const parent = this.parentElement;
        parent.classList.toggle("open");

        const submenu = parent.querySelector(".submenu");
        submenu.style.display = submenu.style.display === "block" ? "none" : "block";
    });
});


// CAROUSEL FUNCTIONALITY
let currentIndex = 0;
let images = [
    <?php foreach ($images as $img) {
        echo "'uploads/experience/{$img['img_path']}',";
    } ?>
];

function showImage(i) {
    currentIndex = i;
    document.getElementById("carouselMainImg").src = images[i];

    // highlight selected thumbnail
    document.querySelectorAll(".thumb").forEach((t, idx) => {
        t.classList.toggle("active", idx === i);
    });
}

function nextImage() {
    currentIndex = (currentIndex + 1) % images.length;
    showImage(currentIndex);
}

function prevImage() {
    currentIndex = (currentIndex - 1 + images.length) % images.length;
    showImage(currentIndex);
}

</script>

</body>
</html>
