<?php
require 'db.php';

$query = $_GET['q'] ?? '';
if (!$query) {
    echo json_encode([]);
    exit;
}

/* 1️⃣ Call Python API using Heavy Duty cURL */
$apiUrl = "https://layshuen-lokalkita-ai.hf.space/search?query=" . urlencode($query);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Give it more time to "think"

// Headers to make it look like a real browser request
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
    "Accept: application/json",
    "Referer: https://layshuen-lokalkita-ai.hf.space/",
    "Expect:" // Fixes "Empty reply from server" issues on some hosts
]);

// Bypass SSL issues common on free hosting
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// DEBUG: If you are still stuck, uncomment the line below to see the error in the console
// die(json_encode(["debug_code" => $httpCode, "debug_resp" => $response]));

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
