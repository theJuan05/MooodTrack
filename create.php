<?php
session_start();
include 'config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $error = "Username already exists!";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hash]);
            $success = "Account created successfully! You can now <a href='login.php'>login</a>.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Account - Mood Tracker</title>
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

    .card {
        background-color: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 40px 30px;
        width: 100%;
        max-width: 400px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        text-align: center;
    }

    .card h3 {
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

    .btn-register {
        background: #6f42c1;
        border: none;
        border-radius: 50px;
        padding: 12px 0;
        width: 100%;
        font-weight: bold;
        color: #fff;
        transition: all 0.3s ease;
    }

    .btn-register:hover {
        background: #5437a0;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .alert-register {
        margin-bottom: 20px;
        padding: 10px 15px;
        border-radius: 10px;
        color: #fff;
    }

    .alert-danger {
        background-color: rgba(255, 0, 0, 0.3);
    }

    .alert-success {
        background-color: rgba(0, 255, 0, 0.3);
    }

    a {
        color: #fff;
        text-decoration: underline;
    }

</style>
</head>
<body>

<div class="card">
    <h3>Create Account</h3>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger alert-register"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if(isset($success)): ?>
        <div class="alert alert-success alert-register"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-floating mb-3">
            <input type="text" name="username" class="form-control" id="username" placeholder="Username" required>
            <label for="username">Username</label>
        </div>

        <div class="form-floating mb-3">
            <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
            <label for="password">Password</label>
        </div>

        <div class="form-floating mb-3">
            <input type="password" name="confirm_password" class="form-control" id="confirm_password" placeholder="Confirm Password" required>
            <label for="confirm_password">Confirm Password</label>
        </div>

        <button type="submit" class="btn btn-register mb-2">Create Account</button>

        <div class="text-center">
            <a href="login.php">Already have an account? Login</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
