<?php
session_start();
include 'config.php';
require 'vendor/autoload.php';

use Phpml\Classification\NaiveBayes;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WhitespaceTokenizer;

if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];


// Training dataset
$samples = [
    // Positive
    'i am happy' => 'Positive',
    'i feel great' => 'Positive',
    'i am joyful' => 'Positive',
    'i am excited' => 'Positive',
    'i feel wonderful' => 'Positive',

    // Negative
    'i am sad' => 'Negative',
    'i feel upset' => 'Negative',
    'i am frustrated' => 'Negative',
    'i feel bad' => 'Negative',
    'i am angry' => 'Negative',

    // Neutral
    'i feel okay' => 'Neutral',
    'i am fine' => 'Neutral',

    // Anxious
    'i am nervous' => 'Anxious',
    'i feel worried' => 'Anxious',

    // Excited
    'i am thrilled' => 'Excited',
    'i am pumped' => 'Excited',

    // Confused
    'i am unsure' => 'Confused',
    'i feel lost' => 'Confused',
];

// Prepare classifier
$data = array_keys($samples);
$labels = array_values($samples);

$vectorizer = new TokenCountVectorizer(new WhitespaceTokenizer());
$vectorizer->fit($data);
$vectorizer->transform($data);

$classifier = new NaiveBayes();
$classifier->train($data, $labels);

// Get user input
$entry = strtolower(trim($_POST['entry']));
$entry = preg_replace("/[^\w\s]/", "", $entry);
$reason = strtolower(trim($_POST['reason'] ?? ''));
$category = $_POST['category'] ?? '';

// Predict sentiment
$combinedText = trim($entry . ' ' . $reason);
$userData = [$combinedText];
$vectorizer->transform($userData);
$sentiment = $classifier->predict($userData[0]);

// Default explanation based on sentiment
$explanations = [
    'Positive' => 'It has a positive impact on your mood.',
    'Negative' => 'It has a negative impact on your mood.',
    'Neutral' => 'It has a neutral impact on your mood.',
    'Anxious' => 'It may indicate stress or worry.',
    'Excited' => 'It shows excitement or high energy.',
    'Confused' => 'It shows uncertainty or confusion.'
];

$explanation = $explanations[$sentiment] ?? '';

// Save to database
$stmt = $pdo->prepare("
    INSERT INTO moods 
        (user_id, entry, reason, sentiment, explanation, category) 
    VALUES 
        (?,?,?,?,?,?)
");
$stmt->execute([$user_id, $entry, $reason, $sentiment, $explanation, $category]);

// Redirect back to dashboard
header('Location: index.php?success=1&sentiment='.urlencode($sentiment).'&explanation='.urlencode($explanation));
exit;
