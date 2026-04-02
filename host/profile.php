<?php
session_start();
require 'script/db.php';

// Ensure user is logged in
if (!isset($_SESSION['host_id'])) {
    header("Location: host_login.html");
    exit();
}

$host_id = $_SESSION['host_id'];

// Fetch host data
$stmt = $conn->prepare("
    SELECT username, biz_name, biz_email, phone_num, state, focus_area, about, profile_pic 
    FROM host_acc 
    WHERE host_id = ?
");
$stmt->bind_param("i", $host_id);
$stmt->execute();
$host = $stmt->get_result()->fetch_assoc();

// Determine profile picture
$profile_pic = !empty($host['profile_pic'])
    ? "uploads/profile/" . $host['profile_pic']
    : "pic/default_profile.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profile - LokalKita</title>
    <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />

  <link rel="stylesheet" href="dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<!-- SIDEBAR -->
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
                <li><a class="active" href="profile.php"><i class="fas fa-edit"></i> Edit Profile</a></li>
            </ul>
        </li>

        <li><a href="list.php"><i class="fas fa-list"></i> My Contents</a></li>
        <li><a href="add_content.php"><i class="fas fa-plus-circle"></i> New Content</a></li>
       
    </ul>

    <a href="host_login.html" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>

<button id="toggle-btn" class="toggle-btn"><i class="fas fa-bars"></i></button>

<!-- MAIN -->
<main class="main">
    <header>
      <h2>Edit Profile</h2>
    </header>

    <section class="profile-form updated-form">

      <!-- SUCCESS OR ERROR MESSAGE -->
      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
      <?php endif; ?>

      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
      <?php endif; ?>

      <!-- FORM START -->
      <form action="script/save_profile.php" method="POST" enctype="multipart/form-data" class="onboard-grid">

        <!-- LEFT SIDE -->
        <div class="left-side">

            <!-- Profile Image -->
            <div class="photo-block">
                <div class="profile-photo-wrapper">
                    <img id="previewImg" 
                      src="<?php echo $profile_pic; ?>" 
                      onerror="this.src='pic/default_profile.png';"
                      class="profile-photo">

                </div>

                <label class="upload-btn">
                    <i class="fa-solid fa-camera"></i> Change Photo
                    <input type="file" name="profile_img" accept="image/*" onchange="previewImage(event)">
                </label>
            </div>

            <!-- Personal & Business Info -->
            <div class="input-full">
                <label>Owner Name</label>
                <input type="text" name="username" value="<?= htmlspecialchars($host['username']); ?>" required>
            </div>

            <div class="input-full">
                <label>Business Name</label>
                <input type="text" name="biz_name" value="<?= htmlspecialchars($host['biz_name']); ?>">
            </div>

            <div class="input-full">
                <label>Business Email</label>
                <input type="email" name="biz_email" value="<?= htmlspecialchars($host['biz_email']); ?>">
            </div>

            <div class="input-full">
                <label>Phone Number</label>
                <input type="text" name="phone_num" value="<?= htmlspecialchars($host['phone_num']); ?>">
            </div>

        </div>

        <!-- RIGHT SIDE -->
        <div class="right-side">

            <label>State</label>
            <select name="state">
                <option value="">-- Select State --</option>
                <?php
                $states = ["Johor","Kedah","Kelantan","Melaka","Negeri Sembilan","Pahang","Penang",
                          "Perak","Perlis","Sabah","Sarawak","Selangor","Terengganu","Wilayah Persekutuan"];

                foreach ($states as $st):
                ?>
                    <option value="<?= $st ?>" <?= ($host['state'] == $st) ? "selected" : "" ?>>
                        <?= $st ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Focus Area</label>
            <select name="category">
                <option value="">-- Select --</option>
                <?php
                $focusAreas = [
                    "Culture & Heritage",
                    "Food & Culinary",
                    "Arts & Crafts",
                    "Nature & Eco",
                    "Festivals & Performances"
                ];

                foreach ($focusAreas as $fa):
                ?>
                    <option value="<?= $fa ?>" <?= ($host['focus_area'] == $fa) ? "selected" : "" ?>>
                        <?= $fa ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- ABOUT -->
        <div class="about-section">
            <h3 class="section-title">About You</h3>
            <textarea name="about" rows="4"><?= htmlspecialchars($host['about']); ?></textarea>
        </div>

        <!-- SUBMIT -->
        <div class="form-actions">
            <button type="submit" class="btn-save">Update Profile</button>
        </div>

      </form>

    </section>
</main>

<script>
function previewImage(event) {
    document.getElementById("previewImg").src =
        URL.createObjectURL(event.target.files[0]);
}
</script>

<script src="dashboard.js"></script>
</body>
</html>
