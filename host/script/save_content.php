<?php
session_start();
require 'db.php';

if (!isset($_SESSION['host_id'])) {
    die("Access denied.");
}

$host_id = $_SESSION['host_id'];

/* ----------------------------------------------------
   1. COLLECT FORM DATA
----------------------------------------------------- */

$exp_id          = $_POST['exp_id']           ?? null;   // <-- IMPORTANT for UPDATE
$exp_title       = $_POST['title']            ?? null;
$exp_desc        = $_POST['desc']             ?? null;
$tags            = $_POST['tags']             ?? null;
$category        = $_POST['category']         ?? null;
$state           = $_POST['state']            ?? null;
$location        = $_POST['location']         ?? null;
$min_price       = $_POST['min_price']        ?? null;
$max_price       = $_POST['max_price']        ?? null;
$open_day        = $_POST['open_day']   ?? null;
$close_day       = $_POST['close_day']     ?? null;
$operating_hours = $_POST['operating_hours']  ?? null;
$contact_info    = $_POST['contact_info']     ?? null;
$web_link        = $_POST['web_link']        ?? null;

$save_type       = $_POST['save_type']        ?? "submit"; // submit or draft
$status          = ($save_type === "draft") ? "Draft" : "Pending";


/* ----------------------------------------------------
   2A. UPDATE EXISTING EXPERIENCE 
----------------------------------------------------- */
if (!empty($exp_id)) {

    $sql = "UPDATE experience SET 
            exp_title=?, exp_desc=?, tags=?, category=?, state=?, location=?, 
            min_price=?, max_price=?, open_day=?, close_day=?, operating_hours=?, 
            contact_info=?, web_link=?, status=?
            WHERE exp_id=? AND host_id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssssddssssssi",
        $exp_title,
        $exp_desc,
        $tags,
        $category,
        $state,
        $location,
        $min_price,
        $max_price,
        $open_day,
        $close_day,
        $operating_hours,
        $contact_info,
        $web_link,
        $status,
        $exp_id,
        $host_id
    );

    if (!$stmt->execute()) {
        die("Error updating experience: " . $stmt->error);
    }

    /* -------- HANDLE IMAGE DELETION -------- */
    if (!empty($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $delete_id) {

            // get image path
            $getImg = $conn->prepare("SELECT img_path FROM experience_images WHERE img_id=? AND exp_id=?");
            $getImg->bind_param("ii", $delete_id, $exp_id);
            $getImg->execute();
            $res = $getImg->get_result()->fetch_assoc();

            if ($res) {
                $filePath = "../uploads/experience/" . $res['img_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $del = $conn->prepare("DELETE FROM experience_images WHERE img_id=? AND exp_id=?");
            $del->bind_param("ii", $delete_id, $exp_id);
            $del->execute();
        }
    }

    /* -------- HANDLE NEW IMAGE UPLOAD -------- */
    if (!empty($_FILES['images']['name'][0])) {

        $base_dir = "../uploads/experience/";
        $exp_dir  = $base_dir . $exp_id . "/";

        if (!is_dir($exp_dir)) {
            mkdir($exp_dir, 0777, true);
        }

        // get next seq_no
        $result = $conn->query("SELECT MAX(seq_no) AS max_seq FROM experience_images WHERE exp_id=$exp_id");
        $nextSeq = ($result->fetch_assoc()['max_seq'] ?? 0) + 1;

        foreach ($_FILES['images']['name'] as $i => $name) {
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $tmp = $_FILES['images']['tmp_name'][$i];
            $cleanName = preg_replace("/[^A-Za-z0-9_.-]/", "_", basename($name));
            $filename  = time() . "_" . $cleanName;
            $filepath  = $exp_dir . $filename;

            if (move_uploaded_file($tmp, $filepath)) {
                $img_path = $exp_id . "/" . $filename;

                $img_sql = "INSERT INTO experience_images (exp_id, img_path, seq_no)
                            VALUES (?, ?, ?)";
                $img_stmt = $conn->prepare($img_sql);
                $img_stmt->bind_param("isi", $exp_id, $img_path, $nextSeq);
                $img_stmt->execute();

                $nextSeq++;
            }
        }
    }

    /* -------- REDIRECT -------- */
    echo "<script>
            alert('Experience updated successfully!');
            window.location.href = '../list.php';
          </script>";
    exit();
}


/* ----------------------------------------------------
   2B. INSERT NEW EXPERIENCE (Original Logic)
----------------------------------------------------- */

$sql = "INSERT INTO experience 
        (host_id, exp_title, exp_desc, tags, category, state, location,
         min_price, max_price, open_day, close_day, operating_hours,
         contact_info, web_link, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "issssssddssssss",
    $host_id,
    $exp_title,
    $exp_desc,
    $tags,
    $category,
    $state,
    $location,
    $min_price,
    $max_price,
    $open_day,
    $close_day,
    $operating_hours,
    $contact_info,
    $web_link,
    $status
);

if (!$stmt->execute()) {
    die("Error inserting experience: " . $stmt->error);
}

$exp_id = $stmt->insert_id;


/* Insert images (new experience) */
if (!empty($_FILES['images']['name'][0])) {

    $base_dir = "../uploads/experience/";
    $exp_dir  = $base_dir . $exp_id . "/";

    if (!is_dir($exp_dir)) {
        mkdir($exp_dir, 0777, true);
    }

    foreach ($_FILES['images']['name'] as $i => $name) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;

        $tmp = $_FILES['images']['tmp_name'][$i];
        $cleanName = preg_replace("/[^A-Za-z0-9_.-]/", "_", basename($name));

        $filename = time() . "_" . $cleanName;
        $filepath = $exp_dir . $filename;

        if (move_uploaded_file($tmp, $filepath)) {
            $img_path = $exp_id . "/" . $filename;
            $seq_no   = $i + 1;

            $img_sql = "INSERT INTO experience_images (exp_id, img_path, seq_no)
                        VALUES (?, ?, ?)";
            $img_stmt = $conn->prepare($img_sql);
            $img_stmt->bind_param("isi", $exp_id, $img_path, $seq_no);
            $img_stmt->execute();
        }
    }
}


/* Redirect */
if ($status === "Draft") {
    echo "<script>
            alert('Draft saved successfully!');
            window.location.href = '../list.php';
          </script>";
    exit();
}

echo "<script>
        alert('Experience submitted! Pending admin approval.');
        window.location.href = '../list.php';
      </script>";
exit();

?>
