<?php
session_start();
require 'script/db.php';

// =======================================
// Ensure admin logged in
// =======================================
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.html");
    exit();
}

// =======================================
// Fetch ONLY experiences WITHOUT images
// =======================================
$expList = $conn->query("
    SELECT e.exp_id, e.exp_title
    FROM experience e
    WHERE NOT EXISTS (
        SELECT 1
        FROM experience_images ei
        WHERE ei.exp_id = e.exp_id
    )
    ORDER BY e.exp_id ASC
");

// =======================================
// Handle upload
// =======================================
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $exp_id = (int)($_POST['exp_id'] ?? 0);

    if ($exp_id <= 0 || empty($_FILES['images']['name'][0])) {
        $msg = "❌ Please select an experience and at least one image.";
    } else {

        // Folder: host/uploads/experience/{exp_id}
        $folder = "../host/uploads/experience/$exp_id/";

        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        // Get current max seq_no
        $seqRes = $conn->query("
            SELECT MAX(seq_no) AS max_seq
            FROM experience_images
            WHERE exp_id = $exp_id
        ");
        $seq = ($seqRes->fetch_assoc()['max_seq'] ?? 0) + 1;

        foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {

            if ($tmp === "") continue;

            $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
            $filename = uniqid("img_") . "." . $ext;

            move_uploaded_file($tmp, $folder . $filename);

            // Store relative path (exp_id/filename)
            $img_path = "$exp_id/$filename";
            $current_seq = $seq;

            $stmt = $conn->prepare("
                INSERT INTO experience_images (exp_id, img_path, seq_no)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("isi", $exp_id, $img_path, $current_seq);
            $stmt->execute();

            $seq++;
        }

        $msg = "✅ Images uploaded successfully.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Upload Experience Images</title>

<link rel="stylesheet" href="dashboard.css">
    <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />

<style>
.upload-box {
    max-width: 600px;
    margin: 40px auto;
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.08);
}

label {
    font-weight: 600;
    margin-top: 15px;
    display: block;
}

select,
input[type=file],
button {
    width: 100%;
    margin-top: 8px;
    padding: 10px;
}

button {
    background: #28a745;
    color: #fff;
    border: none;
    cursor: pointer;
    border-radius: 6px;
    margin-top: 20px;
    font-weight: 600;
}

button:hover {
    background: #218838;
}

.msg {
    margin-bottom: 15px;
    font-weight: 600;
}
</style>
</head>

<body>

<div class="upload-box">
    <h2>Upload Experience Images (Admin)</h2>

    <?php if ($msg): ?>
        <div class="msg"><?= htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <?php if ($expList->num_rows === 0): ?>
        <p style="color:#666;">
            All experiences already have images.
        </p>
    <?php else: ?>

    <form method="POST" enctype="multipart/form-data">

        <label>Select Experience</label>
        <select name="exp_id" required>
            <option value="">-- Choose Experience --</option>
            <?php while ($e = $expList->fetch_assoc()): ?>
                <option value="<?= $e['exp_id']; ?>">
                    <?= $e['exp_id'] . " - " . htmlspecialchars($e['exp_title']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Select Images (Multiple)</label>
        <input type="file" name="images[]" multiple accept="image/*" required>

        <button type="submit">Upload Images</button>
    </form>

    <?php endif; ?>
</div>

</body>
</html>
