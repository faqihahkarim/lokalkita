<?php
session_start();
require 'script/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.html");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// ---------- FETCH USER DATA ----------
$uStmt = $conn->prepare("
    SELECT reg_email, username, phone_num, state,
            profile_pic
    FROM user_acc
    WHERE user_id = ?
");
$uStmt->bind_param("i", $user_id);
$uStmt->execute();
$user = $uStmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// profile image path (adjust if your folder different)
$profilePic = (!empty($user['profile_pic']))
    ? "user/uploads/profile/" . $user['profile_pic']
    : "pic/default-user.png";


// ---------- FETCH ALL INTERESTS ----------
$allInterests = $conn->query("SELECT interest_id, interest_name FROM interest ORDER BY interest_name ASC");

// ---------- FETCH USER SELECTED INTERESTS ----------
$selStmt = $conn->prepare("SELECT interest_id FROM user_interest WHERE user_id = ?");
$selStmt->bind_param("i", $user_id);
$selStmt->execute();
$selRes = $selStmt->get_result();

$userInterests = [];
while ($row = $selRes->fetch_assoc()) {
    $userInterests[] = (int)$row['interest_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile Edit | PakejKita</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="profile-edit.css" />
  <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />
  <style>
    .tag {
      display:inline-flex;
      align-items:center;
      padding:6px 12px;
      border-radius:999px;
      border:1px solid #ddd;
      cursor:pointer;
      margin:4px;
      font-size:0.9rem;
      background:#f8f8f8;
      transition:0.2s;
    }
    .tag input { display:none; }
    .tag span { pointer-events:none; }

    .tag.active {
      background:#043873;
      color:#fff;
      border-color:#043873;
    }
  </style>
</head>
<body>

<header>
  <div class="logo"><h2>LokalKita</h2></div>
  <nav><ul><li><a href="user_dashboard.php">Back</a></li></ul></nav>
</header>

<main class="profile-edit-container">
  <h3>Account Settings</h3>

  <form action="script/update-profile.php" method="POST" enctype="multipart/form-data">

    <!-- PROFILE COMPLETION PROGRESS BAR -->
    <div class="progress-wrapper">
      <p>Profile Completion</p>
      <div class="progress-bar">
        <div class="progress-fill" id="progressFill"></div>
      </div>
      <span id="progressPercent">0%</span>
    </div>

    <!-- BASIC INFO -->
    <section class="section">
      <h4>Basic Info</h4>

      <div class="profile-pic-container">
      <img id="previewImg" src="<?= htmlspecialchars($profilePic); ?>" alt="Profile Preview">

        <label class="upload-btn">
          <input type="file" name="profile_picture" id="profile-picture" accept="image/*">
          Change Picture
        </label>
      </div>

      <div class="input-group">
        <label>Full Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($user['username']); ?>" required>
      </div>

      <div class="input-group">
        <label>Email (cannot be changed)</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['reg_email']); ?>" readonly>
      </div>

      <div class="input-group">
        <label>Phone Number</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone_num']??''); ?>" placeholder="e.g. 012-3456789">
      </div>

      <div class="input-group">
        <label>Location / State</label>
        <select name="state" required>
          <option value="">Select your state</option>
          <?php
          $states = ["Johor","Kedah","Kelantan","Kuala Lumpur","Malacca","Negeri Sembilan","Pahang","Penang","Perak","Perlis","Sabah","Sarawak","Selangor","Terengganu"];
          foreach ($states as $st):
          ?>
            <option value="<?= $st; ?>" <?= ($user['state'] === $st ? 'selected' : ''); ?>>
              <?= $st; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </section>

    <!-- ACCOUNT INFO -->
    <section class="section">
      <h4>Account Info</h4>

      <div class="input-group">
        <label>Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>
      </div>

      <div class="input-group">
        <label>Change Password</label>
        <input type="password" name="password" placeholder="Enter new password (leave blank to keep current)">
      </div>
    </section>

    <!-- INTEREST TAGS -->
    <section class="section">
      <h4>Your Interests</h4>
      <p>Select all that apply</p>

      <div class="tags-container">
        <?php while ($i = $allInterests->fetch_assoc()):
              $id = (int)$i['interest_id'];
              $name = $i['interest_name'];
              $checked = in_array($id, $userInterests);
        ?>
          <label class="tag <?= $checked ? 'active' : ''; ?>">
            <input type="checkbox" name="interests[]" value="<?= $id; ?>" <?= $checked ? 'checked' : ''; ?>>
            <span><?= htmlspecialchars($name); ?></span>
          </label>
        <?php endwhile; ?>
      </div>
    </section>

    <button type="submit" class="submit-btn">Save Changes</button>

  </form>
</main>


<script>
// ==== LIVE IMAGE PREVIEW ====
document.getElementById("profile-picture").addEventListener("change", function(e){
    const file = e.target.files[0];
    if (file) {
        document.getElementById("previewImg").src = URL.createObjectURL(file);
    }
});


// ==== PROFILE COMPLETION PROGRESS BAR ====
function updateProgress() {
  let total = 0;
  let filled = 0;

  const fields = document.querySelectorAll("input, select");
  fields.forEach(field => {
    if (field.type !== "file" && field.type !== "password" && !field.readOnly) {
      total++;
      if (field.value.trim() !== "") filled++;
    }
  });

  // count selected interests
  const interests = document.querySelectorAll(".tag input:checked").length;
  total += 1;
  if (interests > 0) filled++;

  const percent = Math.round((filled / total) * 100);
  document.getElementById("progressFill").style.width = percent + "%";
  document.getElementById("progressPercent").innerText = percent + "%";
}

document.addEventListener("input", updateProgress);
document.addEventListener("change", updateProgress);
window.addEventListener("load", updateProgress);

// ==== TAG ACTIVE TOGGLE ====
document.querySelectorAll(".tag input[type='checkbox']").forEach(cb => {
  cb.addEventListener("change", () => {
    cb.parentElement.classList.toggle("active", cb.checked);
    updateProgress();
  });
});

// ==== OPTIONAL: SIMPLE LOADING OVERLAY ON SUBMIT (no preventDefault) ====
const form = document.querySelector("form");
form.addEventListener("submit", function() {
  const overlay = document.getElementById("loadingOverlay");
  if (overlay) overlay.style.display = "flex";
});
</script>

<!-- ===== LOADING OVERLAY (kept, but now used without blocking submit) ===== -->
<div id="loadingOverlay" style="display:none;">
  <div class="spinner"></div>
  <p>Saving your profile...</p>
</div>


<script>
document.getElementById("profile-picture").addEventListener("change", function(e) {
    const file = e.target.files[0];

    if (file) {
        const imgURL = URL.createObjectURL(file);
        document.getElementById("previewImg").src = imgURL;
    }
});
</script>


</body>
</html>
