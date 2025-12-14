<?php
session_start();
require 'vendor/autoload.php';
include 'config.php';

use Phpml\Classification\NaiveBayes;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WhitespaceTokenizer;

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];


// Get mood ID
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

// Fetch existing mood
$stmt = $pdo->prepare("SELECT * FROM moods WHERE id=? AND user_id=?");
$stmt->execute([$id, $user_id]);
$mood = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mood) {
    header('Location: index.php');
    exit;
}

/* ===== ML TRAINING ===== */
$samples = [
    'i am happy'=>'Positive','i feel great'=>'Positive','i am joyful'=>'Positive','i am excited'=>'Positive','today is a good day'=>'Positive','i feel wonderful'=>'Positive',
    'i am sad'=>'Negative','i feel upset'=>'Negative','i am frustrated'=>'Negative','i feel bad'=>'Negative','i am angry'=>'Negative','i feel terrible'=>'Negative',
    'i feel okay'=>'Neutral','i am fine'=>'Neutral','i feel average'=>'Neutral','i am so-so'=>'Neutral','nothing special today'=>'Neutral',
    'i am nervous'=>'Anxious','i feel worried'=>'Anxious','i am stressed'=>'Anxious',
    'i am thrilled'=>'Excited','i am pumped'=>'Excited','i am looking forward'=>'Excited',
    'i am unsure'=>'Confused','i feel lost'=>'Confused','i am confused'=>'Confused'
];

$data = array_keys($samples);
$labels = array_values($samples);

$vectorizer = new TokenCountVectorizer(new WhitespaceTokenizer());
$vectorizer->fit($data);
$vectorizer->transform($data);

$classifier = new NaiveBayes();
$classifier->train($data, $labels);

// Handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $entry = strtolower(trim($_POST['entry']));
    $entry = preg_replace("/[^\w\s]/", "", $entry);

    $reason = strtolower(trim($_POST['reason'] ?? ''));
    $category = $_POST['category'] ?? null;

    /* === Predict sentiment using UPDATED text === */
    $combinedText = trim($entry . ' ' . $reason);
    $userData = [$combinedText];
    $vectorizer->transform($userData);
    $sentiment = $classifier->predict($userData[0]);

    /* === Generate explanation based on sentiment === */
    $explanations = [
        'Positive' => 'It has a positive impact on your mood.',
        'Negative' => 'It has a negative impact on your mood.',
        'Neutral'  => 'It has a neutral impact on your mood.',
        'Anxious'  => 'It may indicate stress or worry.',
        'Excited'  => 'It shows excitement or high energy.',
        'Confused' => 'It shows uncertainty or confusion.'
    ];

    $explanation = $explanations[$sentiment] ?? '';

    /* === Update mood WITH explanation === */
    $stmt = $pdo->prepare("
        UPDATE moods 
        SET entry=?, reason=?, sentiment=?, explanation=?, category=? 
        WHERE id=? AND user_id=?
    ");

    $stmt->execute([
        $entry,
        $reason,
        $sentiment,
        $explanation,
        $category,
        $id,
        $user_id
    ]);

    header('Location: index.php?success=1');
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Mood Entry</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background: linear-gradient(180deg, #0b0033, #1b005c);
    color: #fff;
    font-family: 'Segoe UI', Tahoma, sans-serif;
}

.card{
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(12px);
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,0.15);
    box-shadow: 0 20px 40px rgba(0,0,0,0.4);
}

.form-control, .form-select{
    background: rgba(0,0,0,0.4) !important;
    border: 1px solid rgba(255,255,255,0.25);
    color: #fff !important;
    border-radius: 12px;
}

label{
    color: #ddd !important;
}

.btn-primary{
    background: linear-gradient(135deg, #5b3bff, #7b5cff);
    border: none;
    border-radius: 12px;
    padding: 12px;
}
</style>
</head>

<body>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>✏️ Edit Mood Entry</h2>
        <a href="index.php" class="btn btn-secondary">Back</a>
    </div>

    <div class="card p-4">
        <form method="POST">

            <div class="form-floating mb-3">
                <textarea name="entry" class="form-control" style="height:90px" required><?= htmlspecialchars($mood['entry']) ?></textarea>
                <label>Mood Entry</label>
            </div>

            <div class="form-floating mb-3">
                <textarea name="reason" class="form-control" style="height:70px"><?= htmlspecialchars($mood['reason']) ?></textarea>
                <label>Reason / Context</label>
            </div>

            <div class="form-floating mb-4">
                <select name="category" class="form-select">
                    <option value="">Select Category</option>
                    <option value="Work" <?= $mood['category']=='Work'?'selected':'' ?>>Work</option>
                    <option value="Personal" <?= $mood['category']=='Personal'?'selected':'' ?>>Personal</option>
                    <option value="Health" <?= $mood['category']=='Health'?'selected':'' ?>>Health</option>
                    <option value="Social" <?= $mood['category']=='Social'?'selected':'' ?>>Social</option>
                </select>
                <label>Category</label>
            </div>

            <button class="btn btn-primary w-100">Update Mood</button>

        </form>
    </div>

</div>

</body>
</html>
