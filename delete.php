<?php
session_start();
include 'config.php';
if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];


$id = $_GET['id'] ?? null;
if($id){
    $stmt = $pdo->prepare("DELETE FROM moods WHERE id=? AND user_id=?");
    $stmt->execute([$id, $user_id]);
}

header('Location: index.php?success=1');
exit;
