<?php
require 'script/db.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
$query = trim($query);

if (strlen($query) < 3) {
    echo json_encode([]);
    exit;
}

/* 1️⃣ Call Python API */
$apiUrl = "http://127.0.0.1:8000/search?query=" . urlencode($query);
$response = @file_get_contents($apiUrl);

if ($response === FALSE) {
    echo json_encode([]);
    exit;
}

$modelResults = json_decode($response, true);

/* 2️⃣ Map EXxxx → exp_id */
$expIds = [];

foreach ($modelResults as $row) {
    if (!isset($row['item_id'])) continue;

    $expIds[] = intval(str_replace("EX", "", $row['item_id']));
}

if (empty($expIds)) {
    echo json_encode([]);
    exit;
}

/* 3️⃣ Query DB ikut ranking model */
$idList = implode(',', $expIds);

$sql = "
SELECT 
    e.exp_id, e.exp_title, e.state, e.category,
    e.min_price, e.max_price,
    COALESCE(e.rating,0) AS rating,
    (
      SELECT img_path 
      FROM experience_images 
      WHERE exp_id = e.exp_id 
      ORDER BY seq_no ASC 
      LIMIT 1
    ) AS img
FROM experience e
WHERE e.exp_id IN ($idList)
ORDER BY FIELD(e.exp_id, $idList)

";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    if ($row['img']) {
        $row['img'] = "../host/uploads/experience/" . $row['img'];
    } else {
        $row['img'] = "pic/default_exp.png";
    }
    $data[] = $row;
}


echo json_encode($data);
