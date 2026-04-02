<?php
session_start();
require 'script/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.html");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// ========================================
// Fetch all bucket list experiences for this user
// ========================================

$sql = "
    SELECT 
    b.bucket_id,
    e.exp_id, 
    e.exp_title,
    COALESCE(e.rating, 0) AS rating,
    e.category,
    e.state,
    e.min_price,
    (
        SELECT img_path FROM experience_images ei 
        WHERE ei.exp_id = e.exp_id 
        ORDER BY seq_no ASC LIMIT 1
    ) AS img_path
FROM bucketlist b
JOIN experience e ON e.exp_id = b.exp_id
WHERE b.user_id = ?
ORDER BY b.bucket_id DESC

";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$results = $stmt->get_result();

$savedExperiences = [];
while ($row = $results->fetch_assoc()) {
    $row['img'] = $row['img_path']
        ? "../host/uploads/experience/" . $row['img_path']
        : "../pic/default.png";
    $savedExperiences[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  
  <title>LokalKita - Travel Journal</title>
  <link rel="stylesheet" href="save-like.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

</head>

<body>

<header>
  <h1 class="logo">LokalKita</h1>

  <div class="menu-toggle" id="menu-toggle">
    <i class="fas fa-grip-horizontal"></i>
  </div>

  <nav id="navbar">
    <ul>
      <li><a href="user_dashboard.php">Back</a></li>
    </ul>
  </nav>
</header>

<section id="popular">
  <h3>Travel Journal</h3>
  <p style="color:#666; font-size:1rem; padding:10px 0; margin-top:-20px; margin-bottom:20px;"> Make memories of your bucket list experiences!</p>

  <div class="grid">

  <?php if (empty($savedExperiences)): ?>
      <p style="color:#666; font-size:1rem; padding:20px;">
          You have no bucket list experiences yet.
      </p>

  <?php else: ?>
      
      <?php foreach ($savedExperiences as $exp): ?>
        
        <a href="memory-lane.php?bucket_id=<?php echo $exp['bucket_id']; ?>" 
          class="card-link">

          <article class="card">

            <div class="card-media">
              <img src="<?= htmlspecialchars($exp['img']) ?>" 
                  onerror="this.src='../pic/default.png'">
              <span class="badge"><?= htmlspecialchars($exp['category']) ?></span>
            </div>

            <div class="content">
              <h4><?= htmlspecialchars($exp['exp_title']) ?></h4>

              <p class="meta">
                <i class="fa-solid fa-location-dot" style="color:#e74c3c;"></i>
                <?= htmlspecialchars($exp['state']) ?> • RM<?= (int)$exp['min_price'] ?>
              </p>

              <p class="rating">
                ⭐ <?= number_format($exp['rating'], 1) ?>
              </p>
            </div>

          </article>

        </a>

      <?php endforeach; ?>


  <?php endif; ?>

  </div>
</section>


<script>
// =============== NAVBAR TOGGLE ===============
document.getElementById("menu-toggle").addEventListener("click", () => {
  document.getElementById("navbar").classList.toggle("active");
});
</script>

</body>
</html>
