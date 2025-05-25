<?php
require_once 'includes/db.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin']) {
    header('Location: index.php');
    exit;
}

// Sepete ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);
    // Sepet session'a ekle
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    if (!in_array($event_id, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $event_id;
    }
}

// Sepetten çıkarma
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    if (($key = array_search($remove_id, $_SESSION['cart'])) !== false) {
        unset($_SESSION['cart'][$key]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
    header('Location: cart.php');
    exit;
}

// Sepetteki etkinlikler
$cart_events = [];
if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
    $in  = str_repeat('?,', count($_SESSION['cart']) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id IN ($in)");
    $stmt->execute($_SESSION['cart']);
    $cart_events = $stmt->fetchAll();
}

// Bilet alma işlemi
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_ticket'])) {
    $ticket_types = $_POST['ticket_type'];
    $payment = $_POST['payment'];
    foreach ($cart_events as $event) {
        $type = $ticket_types[$event['id']];
        $price = $event['price'];
        if ($type == 'Öğrenci') $price *= 0.7;
        if ($event['remaining'] > 0) {
            // Bilet kaydı
            $stmt = $pdo->prepare('INSERT INTO tickets (user_id, event_id, ticket_type, price) VALUES (?, ?, ?, ?)');
            $stmt->execute([$_SESSION['user_id'], $event['id'], $type, $price]);
            // Kontenjan azalt
            $pdo->prepare('UPDATE events SET remaining = remaining - 1 WHERE id = ?')->execute([$event['id']]);
        }
    }
    unset($_SESSION['cart']);
    $message = 'Bilet(ler) başarıyla alındı!';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sepet</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4efe9 100%);
            margin: 0;
            padding: 0;
        }
        
        .page-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            width: 100%;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .page-title {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .page-nav {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .nav-link {
            color: #4CAF50;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: #388E3C;
            transform: translateY(-2px);
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #388E3C;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .success-message-icon {
            font-size: 24px;
        }
        
        .empty-cart {
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            margin-top: 20px;
            width: 100%;
            max-width: 500px;
        }
        
        .empty-cart-icon {
            font-size: 60px;
            margin-bottom: 20px;
            color: #bdbdbd;
        }
        
        .empty-cart-message {
            color: #555;
            font-size: 18px;
            margin-bottom: 25px;
        }
        
        .empty-cart-link {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .empty-cart-link:hover {
            color: #388E3C;
            text-decoration: underline;
        }
        
        .cart-container {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            width: 100%;
            max-width: 800px;
            overflow: hidden;
        }
        
        .cart-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .cart-table thead {
            background-color: #f5f5f5;
        }
        
        .cart-table th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            color: #444;
            border-bottom: 1px solid #eee;
        }
        
        .cart-table td {
            padding: 16px 20px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
        }
        
        .cart-table tr:last-child td {
            border-bottom: none;
        }
        
        .event-title {
            font-weight: 500;
            color: #333;
        }
        
        .event-date {
            color: #666;
            font-size: 14px;
        }
        
        .ticket-type-select {
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            width: 100%;
            background-color: #fff;
            cursor: pointer;
        }
        
        .ticket-type-select:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }
        
        .event-price {
            font-weight: 600;
            color: #333;
        }
        
        .remove-btn {
            background-color: #ffebee;
            color: #f44336;
            border: none;
            border-radius: 6px;
            padding: 8px 14px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .remove-btn:hover {
            background-color: #f44336;
            color: white;
            transform: translateY(-2px);
        }
        
        .cart-footer {
            background-color: #f9f9f9;
            padding: 25px;
            border-top: 1px solid #eee;
        }
        
        .payment-section {
            margin-bottom: 25px;
        }
        
        .payment-label {
            display: block;
            margin-bottom: 10px;
            color: #555;
            font-weight: 500;
        }
        
        .payment-select {
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            width: 100%;
            max-width: 300px;
            cursor: pointer;
        }
        
        .payment-select:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }
        
        .checkout-btn {
            background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 14px 28px;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
        }
        
        .checkout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(76, 175, 80, 0.3);
        }
        
        .payment-modal {
            position: fixed;
            z-index: 9999;
            left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.35);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .payment-modal-content {
            background: #fff;
            border-radius: 14px;
            padding: 36px 32px 28px 32px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.18);
            min-width: 320px;
            max-width: 95vw;
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .payment-modal-content h2 {
            margin-top: 0;
            color: #388E3C;
            font-size: 22px;
            text-align: center;
        }
        .payment-modal-content label {
            display: block;
            margin-bottom: 12px;
            color: #333;
            font-weight: 500;
            font-size: 15px;
        }
        .payment-modal-content input {
            width: 100%;
            padding: 10px 12px;
            border-radius: 7px;
            border: 1px solid #bbb;
            margin-top: 5px;
            font-size: 15px;
            margin-bottom: 8px;
            font-family: 'Poppins', sans-serif;
        }
        .payment-modal-content input:focus {
            border-color: #4CAF50;
            outline: none;
        }
        .close-modal {
            position: absolute;
            top: 12px;
            right: 18px;
            font-size: 28px;
            color: #888;
            cursor: pointer;
            transition: color 0.2s;
        }
        .close-modal:hover {
            color: #f44336;
        }
        #confirm-payment {
            width: 100%;
            margin-top: 10px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <header class="page-header">
            <h1 class="page-title">Sepetim</h1>
            <div class="page-nav">
                <a href="dashboard.php" class="nav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                    </svg>
                    Panele Dön
                </a>
            </div>
        </header>

        <?php if ($message): ?>
        <div class="success-message">
            <span class="success-message-icon">✅</span>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if ($cart_events): ?>
        <div class="cart-container">
            <form method="post">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Etkinlik</th>
                            <th>Tarih</th>
                            <th>Bilet Türü</th>
                            <th>Fiyat</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_events as $e): ?>
                        <tr>
                            <td>
                                <div class="event-title"><?php echo htmlspecialchars($e['title']); ?></div>
                            </td>
                            <td>
                                <div class="event-date"><?php echo $e['event_date']; ?></div>
                            </td>
                            <td>
                                <select name="ticket_type[<?php echo $e['id']; ?>]" class="ticket-type-select">
                                    <option value="Tam">Tam</option>
                                    <option value="Öğrenci">Öğrenci (%30 indirim)</option>
                                </select>
                            </td>
                            <td>
                                <div class="event-price"><?php echo $e['price']; ?> TL</div>
                            </td>
                            <td>
                                <a href="cart.php?remove=<?php echo $e['id']; ?>" class="remove-btn">
                                    Kaldır
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="cart-summary" style="padding: 18px 25px 0 25px; display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                    <div><strong>Toplam Tutar:</strong> <span id="total-price">0</span> TL</div>
                    <div style="color:#388E3C"><strong>Öğrenci İndirimiyle Toplam:</strong> <span id="student-total-price">0</span> TL</div>
                </div>
                <div class="cart-footer">
                    <div class="payment-section">
                        <label class="payment-label">Ödeme Yöntemi:</label>
                        <select name="payment" class="payment-select">
                            <option>Kredi Kartı</option>
                            <option>Banka Kartı</option>
                            <option>Nakit</option>
                        </select>
                    </div>
                    <button type="submit" name="buy_ticket" class="checkout-btn" id="open-payment-modal" type="button">
                        Biletleri Al
                    </button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="empty-cart">
            <div class="empty-cart-icon">🛒</div>
            <p class="empty-cart-message">Sepetinizde hiç etkinlik yok.</p>
            <a href="dashboard.php" class="empty-cart-link">Etkinliklere Göz At</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Ödeme Modalı -->
    <div id="payment-modal" class="payment-modal" style="display:none;">
        <div class="payment-modal-content">
            <span class="close-modal" id="close-payment-modal">&times;</span>
            <h2>Ödeme Bilgileri</h2>
            <form id="payment-form" autocomplete="off" onsubmit="return false;">
                <label>Kart Üzerindeki İsim
                    <input type="text" name="card_name" required placeholder="Ad Soyad">
                </label>
                <label>Kart Numarası
                    <input type="text" name="card_number" maxlength="19" required placeholder="1234 5678 9012 3456">
                </label>
                <label>Son Kullanma Tarihi
                    <input type="text" name="card_expiry" maxlength="5" required placeholder="AA/YY">
                </label>
                <label>CVC
                    <input type="text" name="card_cvc" maxlength="4" required placeholder="123">
                </label>
                <button type="submit" class="checkout-btn" id="confirm-payment">Ödemeyi Onayla</button>
            </form>
            <div id="payment-success" style="display:none; color:#388E3C; font-weight:600; margin-top:18px; font-size:18px; text-align:center;">Ödeme başarılı! Bilet(ler) satın alındı.</div>
        </div>
    </div>

    <script>
    // Modal aç/kapat
    const openModalBtn = document.getElementById('open-payment-modal');
    const modal = document.getElementById('payment-modal');
    const closeModalBtn = document.getElementById('close-payment-modal');
    const paymentForm = document.getElementById('payment-form');
    const paymentSuccess = document.getElementById('payment-success');
    if(openModalBtn && modal && closeModalBtn) {
        openModalBtn.addEventListener('click', function(e) {
            e.preventDefault();
            modal.style.display = 'flex';
        });
        closeModalBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    }
    if(paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            paymentForm.style.display = 'none';
            paymentSuccess.style.display = 'block';
            setTimeout(function(){
                modal.style.display = 'none';
                window.location.href = 'cart.php?clear=1';
            }, 1800);
        });
    }
    // Toplam fiyat ve öğrenci indirimi hesaplama
    function calculateTotals() {
        let rows = document.querySelectorAll('.cart-table tbody tr');
        let total = 0;
        let studentTotal = 0;
        rows.forEach(function(row) {
            let priceText = row.querySelector('.event-price').textContent;
            let price = parseFloat(priceText.replace('TL','').replace(',','.'));
            let select = row.querySelector('.ticket-type-select');
            if (select.value === 'Öğrenci') {
                studentTotal += price * 0.7;
                total += price;
            } else {
                studentTotal += price;
                total += price;
            }
        });
        document.getElementById('total-price').textContent = total.toFixed(2);
        document.getElementById('student-total-price').textContent = studentTotal.toFixed(2);
    }
    document.querySelectorAll('.ticket-type-select').forEach(function(sel) {
        sel.addEventListener('change', calculateTotals);
    });
    window.addEventListener('DOMContentLoaded', calculateTotals);
    </script>

    <?php
    // Sepeti temizle (ödeme sonrası)
    if (isset($_GET['clear']) && $_GET['clear'] == 1) {
        unset($_SESSION['cart']);
    }
    ?>
</body>
</html> 