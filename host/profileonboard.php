<?php
session_start();
require 'script/db.php';

// Prevent access if not logged in
if (!isset($_SESSION['host_id'])) {
    header("Location: host_login.html");
    exit();
}

$host_id = $_SESSION['host_id'];

// Fetch host data
$stmt = $conn->prepare("SELECT username, biz_name, biz_email, state, phone_num, 
                        focus_area, about, profile_pic 
                        FROM host_acc WHERE host_id = ?");
$stmt->bind_param("i", $host_id);
$stmt->execute();
$host = $stmt->get_result()->fetch_assoc();

// Profile photo
$profile_pic = !empty($host['profile_pic']) 
    ? "uploads/profile/" . $host['profile_pic'] 
    : "pic/default_profile.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Complete Your Profile - LokalKita</title>
        <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />

  <link rel="stylesheet" href="profileonboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<div class="onboard-container">

    <h2>Complete Your Host Profile</h2>
    <p>Please complete your information. You can edit anytime later.</p>

    <!-- FORM START -->
    <form action="script/save_profile.php" 
          method="POST" 
          enctype="multipart/form-data" 
          class="onboard-grid"
          target="hiddenFrame">

        <!-- LEFT SECTION -->
        <div class="left-side">

            <!-- Profile Photo -->
            <div class="photo-block">
                <div class="profile-photo-wrapper">
                    <img id="previewImg" 
                        src="<?php echo $profile_pic; ?>" 
                        class="profile-photo"
                        onerror="this.src='pic/default_profile.png';">
                </div>

                <label class="upload-btn">
                    <i class="fa-solid fa-camera"></i> Change Photo
                    <input type="file" name="profile_img" accept="image/*" onchange="previewImage(event)">
                </label>
            </div>

            <!-- Owner Name -->
            <div class="input-full">
                <label>Owner Name (Your Personal Name)</label>
                <input type="text" name="username" 
                       value="<?php echo htmlspecialchars($host['username'] ?? ''); ?>" required>
            </div>

            <!-- Business Name -->
            <div class="input-full">
                <label>Business/Brand Name</label>
                <input type="text" name="biz_name"
                       value="<?php echo htmlspecialchars($host['biz_name'] ?? ''); ?>">
            </div>

            <!-- Business Email -->
            <div class="input-full">
                <label>Business Email (Public Contact)</label>
                <input type="email" name="biz_email"
                       value="<?php echo htmlspecialchars($host['biz_email'] ?? ''); ?>">
            </div>

            <!-- Phone Number -->
            <div class="input-full">
                <label>Phone Number</label>
                <input type="text" name="phone_num"
                       value="<?php echo htmlspecialchars($host['phone_num'] ?? ''); ?>">
            </div>

        </div>

        <!-- RIGHT SECTION -->
        <div class="right-side">

            <label>Focus Area</label>
            <select name="category">
                <option value="">--Select--</option>
                <?php
                $focusOptions = [
                    "Culture & Heritage",
                    "Food & Culinary",
                    "Arts & Crafts",
                    "Nature & Eco",
                    "Festivals & Performances"
                ];

                foreach ($focusOptions as $opt) {
                    $selected = ($host['focus_area'] == $opt) ? "selected" : "";
                    echo "<option value='$opt' $selected>$opt</option>";
                }
                ?>
            </select>

            <label>State</label>
            <select name="state">
                <option value="">-- Select State --</option>
                <?php 
                $states = [
                    "Johor","Kedah","Kelantan","Melaka","Negeri Sembilan","Pahang",
                    "Penang","Perak","Perlis","Sabah","Sarawak","Selangor",
                    "Terengganu","Wilayah Persekutuan"
                ];

                foreach ($states as $s) {
                    $selected = ($host['state'] == $s) ? "selected" : "";
                    echo "<option value='$s' $selected>$s</option>";
                }
                ?>
            </select>

            <!-- About Section -->
            <div class="about-section">
                <h3 class="section-title">About You</h3>
                <textarea name="about" rows="4"><?php echo htmlspecialchars($host['about'] ?? ''); ?></textarea>
            </div>

        </div>

        <!-- BUTTONS -->
        <div class="form-actions">
            <button type="submit" class="btn-save">Save Profile</button>
            <a href="host_dashboard.php" class="btn-skip">Skip for now</a>
        </div>

    </form>

   

</div>

<!-- JS -->
<script>
function previewImage(event) {
    document.getElementById("previewImg").src =
      URL.createObjectURL(event.target.files[0]);
}
</script>



</body>
</html>
