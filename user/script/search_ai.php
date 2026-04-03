<?php
require 'db.php';

$query = $_GET['q'] ?? '';
if (!$query) {
    echo json_encode([]);
    exit;
}

/* 1️⃣ Call Python API using cURL */
$apiUrl = "https://layshuen-lokalkita-ai.hf.space/search?query=" . urlencode($query);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

// These 2 lines are crucial for free hosting SSL issues
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
    // Log the error to see what's happening
    error_log("cURL Error: " . $error_msg);
}
curl_close($ch);

$modelResults = json_decode($response, true);

/* 2️⃣ MAP EXxxx → exp_id */
$expIds = [];

foreach ($modelResults as $row) {
    // MAPPING DI SINI
    $expIds[] = intval(str_replace("EX", "", $row['item_id']));
}

if (empty($expIds)) {
    echo json_encode([]);
    exit;
}

/* 3️⃣ Query database ikut ranking model */
$idList = implode(',', $expIds);

$sql = "
SELECT e.exp_id, e.exp_title, e.state, e.category,
       e.min_price, e.max_price, COALESCE(e.rating,0) AS rating
FROM experience e
WHERE e.exp_id IN ($idList)
ORDER BY FIELD(e.exp_id, $idList)
";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
