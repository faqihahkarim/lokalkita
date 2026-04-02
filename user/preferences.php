<?php
session_start();
require 'script/db.php';

// Get main categories
$main = $conn->query("SELECT * FROM interest WHERE interest_type='main'");

// Get additional interests
$additional = $conn->query("SELECT * FROM interest WHERE interest_type='additional'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>LokalKita Preferences</title>

<link rel="stylesheet" href="preferences.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500;700&display=swap" rel="stylesheet" />
<link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />

</head>

<body>

<div class="container">

    <h1 class="title">Tell us what you enjoy</h1>
    <p class="subtitle">We’ll personalise your CBT experience journey.</p>

    <!-- MAIN CATEGORIES -->
    <h2 class="section-title">Main Categories</h2>
    <div class="pref-grid">
        <?php while ($row = $main->fetch_assoc()) { ?>
            <div class="pref-card" data-id="<?= $row['interest_id']; ?>">
                <?= htmlspecialchars($row['interest_name']); ?>
            </div>
        <?php } ?>
    </div>

    <!-- ADDITIONAL INTERESTS -->
    <h2 class="section-title">What else excites you?</h2>
    <div class="pref-grid">
        <?php while ($row = $additional->fetch_assoc()) { ?>
            <div class="pref-card" data-id="<?= $row['interest_id']; ?>">
                <?= htmlspecialchars($row['interest_name']); ?>
            </div>
        <?php } ?>
    </div>

    <button id="submitBtn">Save My Preferences</button>

</div>

<!-- JS FILE (CORRECT PATH) -->
<script src="script/preferences.js"></script>

<!-- LOADING OVERLAY (FIXED display:none & removed duplicate display:flex) -->
<div id="loadingOverlay" style="
    display:none;
    position: fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background: rgba(0,0,0,0.4);
    backdrop-filter: blur(2px);
    align-items:center;
    justify-content:center;
    z-index:9999;
">
    <div class="spinner"></div>
</div>

<!-- SUCCESS POPUP -->
<div id="successPopup" style="
    display:none;
    position:fixed;
    top:50%;
    left:50%;
    transform:translate(-50%, -50%);
    background:white;
    padding:25px 35px;
    border-radius:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.2);
    font-size:18px;
    font-weight:600;
    z-index:10000;
">
    Preferences saved! 🎉
</div>

</body>
</html>
