<?php
session_start();
include 'config.php';

// --- Session Check ---
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}
$user_id = $_SESSION['user_id'];

// --- PDO Connection ---
 // config.php already contains try-catch for PDO, so no extra catch needed

// --- Filters ---
$filter_category = $_GET['category'] ?? null;
$filter_sentiment = $_GET['sentiment'] ?? null;

// --- Dynamic Query ---
$query = "SELECT created_at, entry, reason, sentiment, category 
          FROM moods 
          WHERE user_id = ?";
$params = [$user_id];

if (!empty($filter_category)) {
    $query .= " AND category = ?";
    $params[] = $filter_category;
}

if (!empty($filter_sentiment)) {
    $query .= " AND sentiment = ?";
    $params[] = $filter_sentiment;
}

$query .= " ORDER BY created_at ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$moods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Clear output buffer ---
if (ob_get_length()) ob_end_clean();

// --- CSV Headers ---
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="mood_history.csv"');

$output = fopen('php://output', 'w');

// CSV Header Row
fputcsv($output, ['Date','Mood Entry','Reason','Sentiment','Category']);

// Data Rows
foreach ($moods as $m) {
    fputcsv($output, [
        $m['created_at'],
        $m['entry'],
        $m['reason'],
        $m['sentiment'],
        $m['category']
    ]);
}

fclose($output);
exit;
