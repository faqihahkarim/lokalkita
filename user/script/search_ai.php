<?php
// user/search_ai.php
header('Content-Type: application/json');
require 'db.php';

$query = $_GET['q'] ?? '';
if (!$query) { echo json_encode([]); exit; }

/* 1️⃣ Call Python API (The Tunnel) */
$apiUrl = "https://layshuen-lokalkita-ai.hf.space/search?query=" . urlencode($query);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fixes SSL blocks
$response = curl_exec($ch);
curl_close($ch);

$modelResults = json_decode($response, true);

/* 2️⃣ Map results to Database IDs */
$expIds = [];
if ($modelResults) {
    foreach ($modelResults as $row) {
        $expIds[] = intval(str_replace("EX", "", $row['item_id']));
    }
}

if (empty($expIds)) { echo json_encode([]); exit; }

/* 3️⃣ Query your MySQL Database */
$idList = implode(',', $expIds);
$sql = "SELECT exp_id, exp_title, state, category, min_price, max_price, rating 
        FROM experience WHERE exp_id IN ($idList) 
        ORDER BY FIELD(exp_id, $idList)";

$result = $conn->query($sql);
$data = [];
while ($row = $result->fetch_assoc()) { $data[] = $row; }

echo json_encode($data);