<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['password1'];
    $confirm_password = $_POST['password2'];
    if ($new_password !== $confirm_password) {
        $message = 'Şifreler uyuşmuyor!';
    } elseif (strlen($new_password) < 6) {
        $message = 'Şifre en az 6 karakter olmalı!';
    } else {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?');
        $stmt->execute([$hash, $_SESSION['user_id']]);
        $_SESSION['must_change_password'] = 0;
        if ($_SESSION['is_admin']) {
            header('Location: admin.php');
        } else {
            header('Location: dashboard.php');
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Değiştir</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
    <form method="post">
        <h2>Şifre Değiştir</h2>
        <?php if ($message) echo '<p>'.$message.'</p>'; ?>
        <label>Yeni Şifre: <input type="password" name="password1" required></label>
        <label>Şifre Tekrar: <input type="password" name="password2" required></label>
        <button type="submit">Şifreyi Değiştir</button>
    </form>
</body>
</html> 