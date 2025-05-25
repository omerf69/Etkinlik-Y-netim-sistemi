<?php
require_once 'includes/db.php';
session_start();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        if (!$user['is_approved']) {
            $message = 'Hesabınız henüz yönetici tarafından onaylanmadı.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['must_change_password'] = $user['must_change_password'];
            if ($user['must_change_password']) {
                header('Location: password_change.php');
                exit;
            } else if ($user['is_admin']) {
                header('Location: admin.php');
                exit;
            } else {
                header('Location: dashboard.php');
                exit;
            }
        }
    } else {
        $message = 'Geçersiz e-posta veya şifre.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
    <?php if ($message) echo '<p>'.$message.'</p>'; ?>
    <form method="post">
        <h2>Giriş Yap</h2>
        <label>E-posta: <input type="email" name="email" required></label>
        <label>Şifre: <input type="password" name="password" required></label>
        <button type="submit">Giriş Yap</button>
        <p><a href="register.php">Kayıt Ol</a></p>
    </form>
</body>
</html> 