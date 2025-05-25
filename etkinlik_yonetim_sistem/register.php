<?php
require_once 'includes/db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $ad = trim($_POST['name']);
    $interests = trim($_POST['interests'] ?? '');

    // E-posta zaten kayıtlı mı kontrol et
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $message = 'Bu e-posta ile zaten kayıt olunmuş.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (email, password, ad, interests) VALUES (?, ?, ?, ?)');
        if ($stmt->execute([$email, $password, $ad, $interests])) {
            $message = 'Kayıt başarılı! Yönetici onayını bekleyiniz.';
        } else {
            $message = 'Kayıt sırasında hata oluştu.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
    <?php if ($message) echo '<p>'.$message.'</p>'; ?>
    <form method="post">
        <h2>Kayıt Ol</h2>
        <label>İsim: <input type="text" name="name" required></label>
        <label>E-posta: <input type="email" name="email" required></label>
        <label>Şifre: <input type="password" name="password" required></label>
        <label>İlgi Alanları: <input type="text" name="interests" placeholder="Müzik, Spor, ..."></label>
        <button type="submit">Kayıt Ol</button>
        <p><a href="index.php">Giriş Yap</a></p>
    </form>
</body>
</html> 