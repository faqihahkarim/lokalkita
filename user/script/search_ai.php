<?php
require 'db.php';

$query = $_GET['q'] ?? '';
if (!$query) {
    echo json_encode([]);
    exit;
}

/* 1️⃣ Call Python API */
$apiUrl = "https://layshuen-lokalkita-ai.hf.space/search?query=" . urlencode($query);

// Use this to bypass the "403 Forbidden" or "Blocked" error
$options = [
    "http" => [
        "header" => "User-Agent: PHP\r\n",
        "ignore_errors" => true // Let us see the error instead of crashing
    ]
];
$context = stream_context_create($options);

$response = file_get_contents($apiUrl, false, $context);

if ($response === false) {
    // This will help us see if the server failed to connect
    echo json_encode(["error" => "Failed to connect to AI server"]);
    exit;
}

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
