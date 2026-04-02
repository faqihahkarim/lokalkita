<?php
session_start();
require 'script/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$bucket_id = $_GET['bucket_id'] ?? null;

if (!$bucket_id) {
    die("Invalid access");
}

/* =========================
   LOAD EXPERIENCE (FALLBACK TITLE)
========================= */
$expStmt = $conn->prepare("
    SELECT e.exp_title
    FROM bucketlist b
    JOIN experience e ON e.exp_id = b.exp_id
    WHERE b.bucket_id = ? AND b.user_id = ?
");
$expStmt->bind_param("ii", $bucket_id, $user_id);
$expStmt->execute();
$expData = $expStmt->get_result()->fetch_assoc();

if (!$expData) {
    die("Experience not found.");
}

/* =========================
   LOAD JOURNAL (IF EXISTS)
========================= */
$journalStmt = $conn->prepare("
    SELECT *
    FROM journal
    WHERE bucket_id = ? AND user_id = ?
");
$journalStmt->bind_param("ii", $bucket_id, $user_id);
$journalStmt->execute();
$journal = $journalStmt->get_result()->fetch_assoc();

$journal_id = $journal['journal_id'] ?? null;

/* =========================
   LOAD JOURNAL ENTRIES
========================= */
$entries = [];
if ($journal_id) {
    $entryStmt = $conn->prepare("
        SELECT *
        FROM journal_entry
        WHERE journal_id = ?
        ORDER BY seq_num ASC
    ");
    $entryStmt->bind_param("i", $journal_id);
    $entryStmt->execute();
    $entries = $entryStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Memory Lane - LokalKita</title>

<link rel="stylesheet" href="memory-lane.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>

<nav class="top-nav glass-header">
    <span class="logo">LokalKita</span>
    <a href="memory-home.php" class="back-link">Back</a>
</nav>

<!-- ================= FORM WRAPS EVERYTHING ================= -->
<form action="script/save-journal.php"
      method="POST"
      enctype="multipart/form-data"
      class="memory-form">

<input type="hidden" name="bucket_id" value="<?php echo $bucket_id; ?>">
<input type="hidden" name="journal_id" value="<?php echo $journal_id ?? ''; ?>">
<input type="hidden" name="title"
       value="<?php echo htmlspecialchars($journal['title'] ?? $expData['exp_title']); ?>">

<div class="memory-container">

    <!-- LEFT: IMAGES -->
    <section class="memory-left">

        <h2 class="title">
            <?php echo htmlspecialchars($journal['title'] ?? $expData['exp_title']); ?>
        </h2>

        <div class="image-grid" id="imageGrid">
            <?php for ($i = 1; $i <= 8; $i++):
                $entry = $entries[$i-1] ?? null;
            ?>
            <div class="img-box polaroid">

                <div class="image-area">
                    <input
                        type="file"
                        accept="image/*"
                        class="img-input"
                        name="images[]"
                        data-slot="<?php echo $i; ?>"
                    >

                    <img class="preview-img"
                         src="<?php echo $entry ? htmlspecialchars($entry['img_path']) : ''; ?>"
                         style="<?php echo $entry ? '' : 'display:none'; ?>">

                    <span class="placeholder"
                          style="<?php echo $entry ? 'display:none' : ''; ?>">
                        Click to add photo
                    </span>
                </div>

                <div class="polaroid-caption">
                    <input
                        type="text"
                        class="caption-input"
                        name="captions[]"
                        data-slot="<?php echo $i; ?>"
                        placeholder="Write a caption..."
                        value="<?php echo htmlspecialchars($entry['caption'] ?? ''); ?>"
                    >
                </div>

            </div>
            <?php endfor; ?>
        </div>

    </section>

    <!-- RIGHT: DETAILS -->
    <section class="memory-right">

        <label class="form-label">When was this?</label>
        <div class="date-box">
            <i class="fa-regular fa-calendar"></i>
            <input
                type="date"
                name="date"
                class="date-input"
                value="<?php echo $journal['date_travel'] ?? ''; ?>"
            >
        </div>

        <label class="form-label">Your Emotion Scale</label>
        <div class="emotion-row">
            <?php for ($i = 1; $i <= 4; $i++): ?>
            <img
                src="pic/emo<?php echo $i; ?>.png"
                class="emotion-img <?php echo (($journal['emotion'] ?? null) == $i) ? 'selected' : ''; ?>"
                data-value="<?php echo $i; ?>"
            >
            <?php endfor; ?>
        </div>

        <input
            type="hidden"
            name="emotion"
            id="emotionInput"
            value="<?php echo $journal['emotion'] ?? ''; ?>"
        >

        <label class="form-label">Write your thought</label>
        <div class="thought-wrapper">
            <textarea
                class="thought-box"
                name="thought"
                id="thoughtBox"
                placeholder="Write your memory here..."
            ><?php echo htmlspecialchars($journal['thoughts'] ?? ''); ?></textarea>

            <div class="sticker">
                <img src="../pic/boy.png" alt="Sticker">
            </div>
        </div>

        <button class="save-btn">Save</button>

    </section>

</div>
</form>

<script src="memory-lane.js"></script>
</body>
</html>
