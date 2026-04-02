<?php
session_start();
require 'script/db.php';

// Check if host is logged in
if (!isset($_SESSION['host_id'])) {
    header("Location: host_login.html");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid experience ID.");
}

$exp_id = intval($_GET['id']);
$host_id = $_SESSION['host_id'];

/* -------------------------------
   FETCH EXPERIENCE DETAILS
-----------------------------------*/
$stmt = $conn->prepare("
    SELECT * FROM experience 
    WHERE exp_id=? AND host_id=?
");
$stmt->bind_param("ii", $exp_id, $host_id);
$stmt->execute();
$experience = $stmt->get_result()->fetch_assoc();

if (!$experience) {
    die("Experience not found or access denied.");
}

/* -------------------------------
   FETCH EXPERIENCE IMAGES
-----------------------------------*/
$imgQuery = $conn->prepare("
    SELECT * FROM experience_images 
    WHERE exp_id=? 
    ORDER BY seq_no ASC
");
$imgQuery->bind_param("i", $exp_id);
$imgQuery->execute();
$images = $imgQuery->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Content - LokalKita</title>

<link rel="stylesheet" href="dashboard.css">
    <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>
.edit-image-box {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}
.edit-image-box img {
    width: 140px;
    height: 110px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #ddd;
}
.delete-btn {
    background: #ff4d4d;
    padding: 5px 10px;
    color: white;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    margin-top: 5px;
}
</style>

</head>

<body>

<!-- Sidebar omitted for clarity -->

<main class="main">
<section class="content-form">

<h2>Edit Content</h2>
<p>You can update your experience details and images.</p>

<form action="script/save_content.php" method="POST" enctype="multipart/form-data">

<input type="hidden" name="exp_id" value="<?php echo $exp_id; ?>">

<!-- STEP 1: BASIC INFO -->
<div class="section-card">
<h3 class="section-title">Basic Information</h3>

<label>Title</label>
<input type="text" name="title" value="<?php echo htmlspecialchars($experience['exp_title']); ?>" required>

<label>Description</label>
<textarea name="desc" rows="3" required><?php echo htmlspecialchars($experience['exp_desc']); ?></textarea>

<label>Tags</label>
<input type="text" name="tags" value="<?php echo htmlspecialchars($experience['tags']); ?>">

<label>Category</label>
<select name="category" required>
    <?php 
    $categories = [
        "Culture & Heritage","Festival & Performance",
        "Food & Culinary","Arts & Crafts",
        "Nature & Eco","Homestay"
    ];
    foreach ($categories as $c) {
        $selected = ($experience['category'] == $c) ? "selected" : "";
        echo "<option value='$c' $selected>$c</option>";
    }
    ?>
</select>

<label>State</label>
<select name="state" required>
<?php
$states = [
"Johor","Kedah","Kelantan","Melaka","Negeri Sembilan","Pahang",
"Perak","Perlis","Penang","Sabah","Sarawak","Selangor",
"Terengganu","WP Kuala Lumpur","WP Labuan","WP Putrajaya"
];

foreach ($states as $s) {
    $selected = ($experience['state'] == $s) ? "selected" : "";
    echo "<option value='$s' $selected>$s</option>";
}
?>
</select>

<label>Location</label>
<input type="text" name="location" value="<?php echo htmlspecialchars($experience['location']); ?>">

</div>

<!-- STEP 2: PRICE -->
<div class="section-card">
<h3 class="section-title">Price & Schedule</h3>

<label>Min Price</label>
<input type="number" step="0.01" name="min_price" value="<?php echo $experience['min_price']; ?>">

<label>Max Price</label>
<input type="number" step="0.01" name="max_price" value="<?php echo $experience['max_price']; ?>">

<label>Operating Days</label>
<input type="text" name="open_day" value="<?php echo htmlspecialchars($experience['open_day']); ?>">

<label>Closing Days</label>
<input type="text" name="close_day" value="<?php echo htmlspecialchars($experience['close_day']); ?>">

<label>Operating Hours</label>
<input type="text" name="operating_hours" value="<?php echo htmlspecialchars($experience['operating_hours']); ?>">

<label>Contact Info</label>
<input type="text" name="contact_info" value="<?php echo htmlspecialchars($experience['contact_info']); ?>">

<label>Website / Link</label>
<input type="url" name="web_link" value="<?php echo htmlspecialchars($experience['web_link']??''); ?>">

</div>

<!-- STEP 3: IMAGES -->
<div class="section-card">
<h3 class="section-title">Existing Images</h3>

<!-- Flex container for images -->
<div style="
    display: flex;
    flex-wrap: wrap;
    gap: 25px;
    padding: 15px 0;
">

<?php foreach ($images as $img): ?>

    <!-- Each image + checkbox box -->
    <div style="
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 150px;
        text-align: center;
    ">
        <!-- Image -->
        <img src="uploads/experience/<?php echo $img['img_path']; ?>" 
             style="
                width:150px; 
                height:150px; 
                object-fit:cover; 
                border-radius:10px; 
                border:1px solid #ddd;
             ">

        <!-- Delete checkbox -->
        <label style="margin-top: 10px; font-size: 14px;">
            <input type="checkbox" name="delete_images[]" value="<?php echo $img['img_id']; ?>">
            Delete this image
        </label>
    </div>

<?php endforeach; ?>

</div>


<br>

<h3>Upload New Images (optional)</h3>
<input type="file" name="images[]" multiple accept="image/*">

</div>

<a href="list.php" style="text-decoration: none;">
    <button type="button" class="btn-submit" style="background-color: #fff065; color: #000;">Cancel</button>
</a>

<button type="submit" class="btn-submit">Update Content</button>


</form>

</section>
</main>

</body>
</html>
