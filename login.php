<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: index.php');
        exit;
    } else {
        $login_error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - Mood Tracker</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        margin: 0;
        padding: 0;
        height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(180deg, #0a0130, #1c0068);
        display: flex;
        justify-content: center;
        align-items: center;
        color: #fff;
    }

    .login-card {
        background-color: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(12px);
        border-radius: 15px;
        padding: 40px 30px;
        width: 100%;
        max-width: 400px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        text-align: center;
    }

    .login-card h2 {
        margin-bottom: 30px;
        font-weight: 700;
        color: #fff;
        text-shadow: 1px 1px 5px rgba(0,0,0,0.3);
    }

    .form-control {
        border-radius: 50px;
        padding: 12px 20px;
        border: none;
        outline: none;
        background-color: rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.7);
    }

    .btn-login {
        background: #6f42c1;
        border: none;
        border-radius: 50px;
        padding: 12px 0;
        width: 100%;
        font-weight: bold;
        color: #fff;
        transition: all 0.3s ease;
    }

    .btn-login:hover {
        background: #5437a0;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .alert-login {
        margin-bottom: 20px;
        padding: 10px 15px;
        border-radius: 10px;
        background-color: rgba(255, 0, 0, 0.2);
        color: #fff;
    }

    a {
        color: #fff;
        text-decoration: underline;
    }

</style>
</head>
<body>

<div class="login-card">
    <h2>Mood Tracker Login</h2>

   

    <form action="login.php" method="POST">
        <div class="mb-3">
            <input type="text" name="username" class="form-control" placeholder="Username" required>
        </div>
        <div class="mb-4">
            <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>
        <button type="submit" class="btn btn-login">Login</button>
    </form>

    <p class="mt-3">Don't have an account? <a href="create.php">Create Account</a></p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
