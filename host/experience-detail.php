<?php
session_start();
require "script/db.php";

// Validate experience ID
$exp_id = $_GET['id'] ?? 0;
if ($exp_id == 0) {
    die("Invalid experience ID.");
}

$exp_id = intval($_GET['id']);

/* --------------------------------------------------
   1. GET EXPERIENCE + HOST DATA
-------------------------------------------------- */
$sql = "SELECT e.*, h.username AS host_username, h.biz_name, h.phone_num
        FROM experience e
        JOIN host_acc h ON e.host_id = h.host_id
        WHERE e.exp_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exp_id);
$stmt->execute();
$experience = $stmt->get_result()->fetch_assoc();

if (!$experience) {
    die("Invalid Experience ID.");
}

/* --------------------------------------------------
   2. GET IMAGES (ALL, SEQ ORDER)
-------------------------------------------------- */
$img_sql = "SELECT img_path 
            FROM experience_images 
            WHERE exp_id=? 
            ORDER BY seq_no ASC";
$img_stmt = $conn->prepare($img_sql);
$img_stmt->bind_param("i", $exp_id);
$img_stmt->execute();
$img_res = $img_stmt->get_result();

$images = [];
while ($row = $img_res->fetch_assoc()) {
    // img_path in DB looks like: 350/1764702149_1.png
    $images[] = $row['img_path'];
}

// First image for background + main preview
$first_img = !empty($images)
    ? "uploads/experience/" . $images[0]
    : "img/default.png";

/* --------------------------------------------------
   3. GET REVIEWS + USERNAME
-------------------------------------------------- */
$review_sql = "SELECT r.review_id, r.review, r.rating, r.date_create, u.username 
               FROM review r
               JOIN user_acc u ON r.user_id = u.user_id
               WHERE r.exp_id = ?
               ORDER BY r.date_create DESC";
$rev_stmt = $conn->prepare($review_sql);
$rev_stmt->bind_param("i", $exp_id);
$rev_stmt->execute();
$reviews = $rev_stmt->get_result();

/* --------------------------------------------------
   4. AVG RATING + COUNT (from review table)
-------------------------------------------------- */
$avg_sql = "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews
            FROM review
            WHERE exp_id = ?";
$avg_stmt = $conn->prepare($avg_sql);
$avg_stmt->bind_param("i", $exp_id);
$avg_stmt->execute();
$avg_data = $avg_stmt->get_result()->fetch_assoc();

$avg_rating    = $avg_data['avg_rating'] ?? 0;
$total_reviews = $avg_data['total_reviews'] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($experience["exp_title"]); ?></title>
        <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />
    <link rel="stylesheet" href="experience-detail.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* ====== Dynamic blurred background using first image ====== */
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-image: url('<?php echo htmlspecialchars($first_img); ?>');
            background-size: cover;
            background-position: center;
            position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image: inherit;
            background-size: cover;
            background-position: center;
            filter: blur(6px);
            z-index: -1;
        }

        /* Keep main content readable on top of blur */
        .detail-container {
            max-width: 1200px;
            margin: 100px auto 60px;
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);
            gap: 40px;
            background: rgba(255,255,255,0.92);
            border-radius: 24px;
            padding: 30px 40px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.25);
        }

        @media (max-width: 900px) {
            .detail-container {
                margin-top: 80px;
                grid-template-columns: 1fr;
            }
        }

        /* NAV bar overlay */
        .detail-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.75), transparent);
            color: #fff;
            z-index: 50;
        }

        .detail-nav .logo {
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .detail-nav a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
        }

        /* Image section */
        .image-section {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .main-image {
            border-radius: 20px;
            overflow: hidden;
        }

        .main-image img {
            width: 100%;
            display: block;
            border-radius: 20px;
            cursor: zoom-in;
        }

        .thumbnail-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .thumbnail-row img.thumb {
            width: 23%;
            border-radius: 14px;
            cursor: pointer;
            object-fit: cover;
            aspect-ratio: 4 / 3;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .thumbnail-row img.thumb:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.25);
        }

        /* Info section tweaks */
        .info-section h1 {
            margin-top: 0;
            margin-bottom: 6px;
        }

        .price-rating {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 10px 0 16px;
        }

        .price {
            font-weight: 700;
            color: #ff652a;
        }

        .stars {
            color: #ffc107;
        }

        .review-count {
            font-size: 0.85rem;
            color: #666;
        }

        /* Reviews */
        .reviews-section {
            max-width: 1200px;
            margin: 0 auto 60px;
            padding: 24px 40px 32px;
            background: rgba(255,255,255,0.92);
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .review-list {
            display: grid;
            grid-template-columns: repeat(auto-fit,minmax(260px,1fr));
            gap: 18px;
            margin-top: 16px;
        }

        .review-card {
            padding: 14px 16px;
            border-radius: 16px;
            background: #f8fafc;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }

        .review-stars {
            color: #ffc107;
            font-size: 0.9rem;
        }

        .review-text {
            font-size: 0.9rem;
            margin: 6px 0;
        }

        .review-date {
            font-size: 0.8rem;
            color: #777;
        }

        /* Fullscreen modal */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.85);
            justify-content: center;
            align-items: center;
            z-index: 999;
        }

        .modal img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.8);
        }
    </style>
</head>

<body>

<!-- NAV -->
<nav class="detail-nav">
    <span class="logo">LokalKita</span>
    <div class="nav-links">
        <a href="myprofile.php">Back</a>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="detail-container">

    <!-- IMAGE COLUMN -->
    <div class="image-section">

        <div class="main-image">
            <img id="mainPreview"
                 src="<?php echo htmlspecialchars($first_img); ?>"
                 alt="Main Experience Image">
        </div>

        <?php if (!empty($images)): ?>
            <div class="thumbnail-row">
                <?php foreach ($images as $img_path): 
                    $src = "uploads/experience/" . $img_path;
                ?>
                    <img class="thumb"
                         src="<?php echo htmlspecialchars($src); ?>"
                         alt="Thumbnail"
                         onclick="changeImage(this.src)">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- RIGHT INFO PANEL -->
    <div class="info-section">

        <h1><?php echo htmlspecialchars($experience["exp_title"]); ?></h1>

        <div class="category"><?php echo htmlspecialchars($experience["category"]); ?></div>

        <div class="price-rating">
            <span class="price">
                RM <?php echo number_format($experience["min_price"], 2); ?>
                – RM <?php echo number_format($experience["max_price"], 2); ?>
            </span>

            <span class="stars">
                <?php
                $rounded = round($avg_rating);
                for ($i = 0; $i < $rounded; $i++): ?>
                    <i class="fa-solid fa-star"></i>
                <?php endfor; ?>
            </span>

            <span class="review-count">
                (<?php echo $total_reviews; ?> review<?php echo $total_reviews == 1 ? '' : 's'; ?>,
                avg <?php echo $avg_rating ? number_format($avg_rating,1) : '–'; ?>)
            </span>
        </div>

        <p class="description">
            <?php echo nl2br(htmlspecialchars($experience["exp_desc"])); ?>
        </p>

        <ul class="meta-list">
            <li><strong>Location:</strong>
                <?php echo htmlspecialchars($experience["location"]); ?>,
                <?php echo htmlspecialchars($experience["state"]); ?>
            </li>
            <li><strong>Tags:</strong> <?php echo htmlspecialchars($experience["tags"]); ?></li>
            <li><strong>Operating Hours:</strong> <?php echo htmlspecialchars($experience["operating_hours"]); ?></li>
            <li><strong>Open:</strong> <?php echo htmlspecialchars($experience["open_day"]); ?></li>
            <li><strong>Close:</strong> <?php echo htmlspecialchars($experience["close_day"]); ?></li>
        </ul>

        <h3 class="host-title">Hosted by</h3>
        <div class="host-box">
            <p><strong><?php echo htmlspecialchars($experience["biz_name"]); ?></strong></p>
            <p>👤 <?php echo htmlspecialchars($experience["host_username"]); ?></p>
            <p>📞 <?php echo htmlspecialchars($experience["phone_num"]); ?></p>
            <?php if (!empty($experience["web_link"])): ?>
                <a href="<?php echo htmlspecialchars($experience["web_link"]); ?>"
                   target="_blank"
                   style="color:#ff652a; text-decoration:none;">
                    More Info
                </a>
            <?php endif; ?>
        </div>

    </div><!-- /info-section -->

</div><!-- /detail-container -->

<!-- REVIEWS (READ-ONLY FOR HOST) -->
<section class="reviews-section">
    <h2 class="review-title">What Travelers Say</h2>

    <div class="review-list">
        <?php if ($reviews->num_rows === 0): ?>
            <p style="text-align:center; opacity:0.7; grid-column:1/-1;">
                No reviews yet.
            </p>
        <?php endif; ?>

        <?php while($r = $reviews->fetch_assoc()): ?>
            <div class="review-card">
                <div class="review-header">
                    <strong><?php echo htmlspecialchars($r["username"]); ?></strong>
                    <span class="review-stars">
                        <?php for($i=0; $i < $r["rating"]; $i++): ?>
                            <i class="fa-solid fa-star"></i>
                        <?php endfor; ?>
                    </span>
                </div>

                <p class="review-text">
                    <?php echo htmlspecialchars($r["review"]); ?>
                </p>

                <p class="review-date">
                    <?php echo htmlspecialchars($r["date_create"]); ?>
                </p>
            </div>
        <?php endwhile; ?>
    </div>
</section>

<!-- FULLSCREEN IMAGE MODAL -->
<div id="imageModal" class="modal" onclick="closeModal()">
    <img id="modalImage" alt="Fullscreen Image">
</div>

<script>
    function changeImage(src) {
        document.getElementById("mainPreview").src = src;
    }

    // Open fullscreen on main image click
    const mainPreview = document.getElementById("mainPreview");
    const modal       = document.getElementById("imageModal");
    const modalImage  = document.getElementById("modalImage");

    if (mainPreview) {
        mainPreview.addEventListener("click", function () {
            modalImage.src = this.src;
            modal.style.display = "flex";
        });
    }

    function closeModal() {
        modal.style.display = "none";
    }
</script>

</body>
</html>
