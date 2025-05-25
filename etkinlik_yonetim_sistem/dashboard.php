<?php
require_once 'includes/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Kullanıcı bilgisi
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Duyuruları çek
$announcements = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC')->fetchAll();

// Etkinlikleri çek
$events = $pdo->query('SELECT * FROM events ORDER BY event_date ASC')->fetchAll();
$user_interest = isset($user['interests']) ? strtolower($user['interests']) : '';
$recommended = [];
// Eşanlamlı/ilişkili kelime haritası
$interest_map = [
    'müzik' => ['müzik', 'konser', 'festival', 'şenlik', 'şarkı', 'orchestra', 'band'],
    'spor' => ['spor', 'futbol', 'basketbol', 'voleybol', 'tenis', 'koşu', 'turnuva'],
    'tiyatro' => ['tiyatro', 'drama', 'oyun', 'sahne', 'gösteri'],
    'çocuk' => ['çocuk', 'aile', 'çizgi', 'animasyon'],
    'stand-up' => ['stand-up', 'komedi', 'mizah'],
    'sinema' => ['sinema', 'film', 'vizyon', 'gösterim'],
    // İstediğiniz kadar ekleyebilirsiniz
];
if ($user_interest) {
    $interest_arr = array_map('trim', explode(',', $user_interest));
foreach ($events as $e) {
        $event_type = strtolower($e['event_type']);
        foreach ($interest_arr as $interest) {
            $interest = trim($interest);
            $keywords = isset($interest_map[$interest]) ? $interest_map[$interest] : [$interest];
            foreach ($keywords as $keyword) {
                if ($keyword && strpos($event_type, $keyword) !== false) {
        $recommended[] = $e;
                    break 2;
                }
            }
        }
    }
}

// Sepetteki ürün sayısı
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Paneli</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f7fa;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            max-width: 100vw;
            overflow-x: hidden;
        }
        
        .dashboard-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: 100%;
        }

        /* Header styles */
        .dashboard-header {
            background-color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .user-welcome {
            display: flex;
            align-items: center;
        }
        
        .user-welcome h2 {
            margin: 0;
            color: #333;
            font-size: 18px;
            font-weight: 600;
        }
        
        .user-welcome .user-avatar {
            width: 36px;
            height: 36px;
            background-color: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 12px;
        }
        
        .header-actions {
            display: flex;
            gap: 0;
            align-items: center;
        }
        .header-btn {
            background: none;
            color: #388E3C;
            border: none;
            border-radius: 0;
            padding: 10px 22px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: color 0.3s, background 0.3s, transform 0.2s;
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 8px;
            position: relative;
            box-shadow: none;
            white-space: nowrap;
        }
        .header-btn:hover {
            color: #fff;
            background: linear-gradient(90deg, #4CAF50 60%, #8BC34A 100%);
            transform: translateY(-2px) scale(1.04);
        }
        .header-divider {
            width: 2px;
            height: 32px;
            background: #d0d0d0;
            margin: 0 6px;
            border-radius: 2px;
            transition: box-shadow 0.3s, transform 0.2s;
        }
        .header-btn:hover + .header-divider,
        .header-divider:hover {
            box-shadow: 0 0 8px 2px #8BC34A44;
            transform: scaleY(1.12);
        }
        
        .weather-widget {
            background: none;
            border-radius: 0;
            padding: 0;
            box-shadow: none;
            display: inline-flex;
            align-items: center;
            gap: 14px;
            margin: 20px 30px 10px;
            width: fit-content;
            position: relative;
            overflow: hidden;
            border-left: none;
            transition: all 0.3s ease;
        }
        
        .weather-widget:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(76, 175, 80, 0.12);
        }
        
        .weather-widget .weather-icon {
            font-size: 26px;
            background: rgba(76, 175, 80, 0.08);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
        }
        
        .weather-widget .weather-info {
            font-weight: 500;
            color: #333;
            font-size: 15px;
        }
        
        .btn {
            padding: 10px 16px;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }
        
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #388E3C;
            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.2);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: #f44336;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #d32f2f;
            box-shadow: 0 4px 8px rgba(244, 67, 54, 0.2);
            transform: translateY(-2px);
        }

        /* Main content area */
        .dashboard-content {
            display: flex;
            flex-direction: column;
            padding: 25px 40px;
            gap: 30px;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
            max-width: 1200px;
            overflow-x: auto;
        }
        
        /* Events section */
        .events-container {
            flex: 1;
            min-width: 300px;
            max-width: 1600px;
            width: 100%;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .content-header {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
            width: 100%;
            position: relative;
        }
        
        .content-header h3 {
            margin: 0;
            color: #333;
            font-weight: 600;
            font-size: 20px;
            position: relative;
            padding-bottom: 10px;
            text-align: center;
        }
        
        .content-header h3:after {
            content: '';
            position: absolute;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 3px;
        }
        
        .events-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 18px;
            width: 100%;
            max-width: 1200px;
            justify-content: center;
        }
        
        .event-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
            padding: 0;
            position: relative;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            height: 265px;
            min-width: 0;
            max-width: 520px;
            width: 100%;
            transition: all 0.25s cubic-bezier(0.165, 0.84, 0.44, 1);
            border: none;
            margin-bottom: 0;
            font-size: 1.08em;
        }
        
        .event-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            border-radius: 0;
        }
        
        .event-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 18px rgba(76, 175, 80, 0.12);
        }
        
        .event-card .event-header {
            background: linear-gradient(145deg, rgba(220, 237, 220, 0.3) 0%, rgba(255, 255, 255, 1) 100%);
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 14px 14px 10px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .event-card .event-icon {
            font-size: 18px;
            color: #4CAF50;
            background: rgba(76, 175, 80, 0.08);
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s ease;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(76, 175, 80, 0.06);
        }
        
        .event-card:hover .event-icon {
            transform: scale(1.06);
            box-shadow: 0 3px 8px rgba(76, 175, 80, 0.12);
        }
        
        .event-card .event-title {
            font-weight: 600;
            font-size: 18px;
            color: #2E7D32;
            margin: 0 0 7px 0;
            line-height: 1.28;
        }
        
        .event-card .event-type {
            background: rgba(139, 195, 74, 0.12);
            color: #388E3C;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            display: inline-block;
            letter-spacing: 0.18px;
        }
        
        .event-card .event-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 12px 14px;
            flex-grow: 1;
        }
        
        .event-card .event-detail {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 15px;
            color: #555;
            font-weight: 400;
        }
        
        .event-card .event-detail svg {
            color: #7cb342;
            background: rgba(139, 195, 74, 0.08);
            padding: 3px;
            border-radius: 6px;
            width: 13px;
            height: 13px;
            flex-shrink: 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }
        
        .event-card .event-price-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            border-top: 1px solid rgba(0, 0, 0, 0.03);
            background: rgba(250, 250, 250, 0.7);
        }
        
        .event-card .event-price {
            font-weight: 600;
            color: #388E3C;
            font-size: 17px;
        }
        
        .event-card .event-remaining {
            font-size: 12px;
            color: #757575;
            background: rgba(76, 175, 80, 0.08);
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .weather-warning {
            padding: 7px 14px;
            border-radius: 0;
            font-size: 10px;
            margin: 0;
            background: rgba(250, 250, 250, 0.7);
            line-height: 1.2;
            border-top: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .weather-warning .good-weather {
            color: #388E3C;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .weather-warning .good-weather:before {
            content: '✓';
            background: rgba(76, 175, 80, 0.1);
            color: #388E3C;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 12px;
        }
        
        .weather-warning .moderate-weather {
            color: #FF9800;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .weather-warning .moderate-weather:before {
            content: '!';
            background: rgba(255, 152, 0, 0.1);
            color: #FF9800;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 12px;
            font-weight: bold;
        }
        
        .weather-warning .bad-weather {
            color: #d32f2f;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .weather-warning .bad-weather:before {
            content: '⚠';
            background: rgba(211, 47, 47, 0.1);
            color: #d32f2f;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 12px;
        }
        
        .weather-warning .no-weather {
            color: #888;
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .weather-warning .no-weather:before {
            content: 'i';
            background: rgba(0, 0, 0, 0.05);
            color: #888;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 12px;
            font-style: normal;
            font-weight: bold;
        }
        
        .add-to-cart-form {
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            bottom: 7px;
            right: 7px;
            margin: 0;
            padding: 0;
            min-width: 0;
            width: auto;
            background: none;
            box-shadow: none;
        }
        .btn-add-to-cart {
            position: static;
            background: linear-gradient(135deg, #4CAF50, #8BC34A);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 3px 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            font-size: 10px;
            letter-spacing: 0.1px;
            box-shadow: 0 1px 4px rgba(76, 175, 80, 0.10);
            z-index: 5;
            outline: none !important;
            border-style: none !important;
        }
        .btn-add-to-cart:focus {
            outline: none !important;
            border: none !important;
        }
        
        .btn-add-to-cart:hover {
            background: linear-gradient(135deg, #388E3C, #7CB342);
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(76, 175, 80, 0.15);
        }

        /* Highlighted interest event card */
        #highlighted-interest {
            margin-bottom: 30px;
            background: linear-gradient(135deg, rgba(245, 253, 242, 0.9), rgba(232, 245, 233, 0.9));
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 12px 28px rgba(76, 175, 80, 0.12);
            border: 1px solid rgba(139, 195, 74, 0.15);
            position: relative;
            overflow: hidden;
        }
        
        #highlighted-interest:before,
        #highlighted-interest:after {
            display: none !important;
        }
        
        #highlighted-interest h3 {
            color: #2E7D32;
            margin-top: 0;
            margin-bottom: 24px;
            font-size: 20px;
            position: relative;
            display: inline-block;
            padding-bottom: 10px;
            font-weight: 600;
        }
        
        #highlighted-interest h3:after {
            content: '';
            position: absolute;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            bottom: 0;
            left: 0;
            border-radius: 3px;
        }
        
        #highlighted-event {
            border: none;
            background: white;
            margin-bottom: 0;
            box-shadow: 0 14px 30px rgba(76, 175, 80, 0.15);
            z-index: 1;
            position: relative;
            padding: 0;
        }
        
        #highlighted-event .event-header {
            background: linear-gradient(145deg, rgba(255, 248, 225, 0.4) 0%, rgba(255, 255, 255, 1) 100%);
        }
        
        #highlighted-event .event-icon {
            color: #FF9800;
            background: rgba(255, 152, 0, 0.08);
            font-size: 22px;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            box-shadow: 0 5px 12px rgba(255, 152, 0, 0.12);
        }
        
        #highlighted-event:hover .event-icon {
            transform: scale(1.08) rotate(5deg);
        }

        /* Updated Recommendations List */
        .recommendations {
            background: white;
            border-radius: 16px;
            padding: 24px 28px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
            margin-bottom: 30px;
        }

        .recommendations h3 {
            color: #2E7D32;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
            position: relative;
            padding-bottom: 10px;
            display: inline-block;
        }
        
        .recommendations h3:after {
            content: '';
            position: absolute;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            bottom: 0;
            left: 0;
            border-radius: 3px;
        }

        .recommendations ul {
            padding-left: 15px;
            margin: 0;
        }

        .recommendations li {
            padding: 10px 5px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            color: #555;
            font-size: 15px;
            display: flex;
            align-items: center;
        }

        .recommendations li:last-child {
            border-bottom: none;
        }

        .recommendations li:before {
            content: '•';
            color: #4CAF50;
            font-weight: bold;
            margin-right: 10px;
            font-size: 18px;
        }
        
        /* Announcements section */
        .announcements-container {
            width: 100%;
            max-width: 100%;
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .announcements-wrapper {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            width: 100%;
            max-width: 1100px;
            justify-items: center;
        }
        
        .announcement-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            padding: 16px;
            border-left: 4px solid #8BC34A;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .announcement-card:hover {
            transform: translateX(4px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
        }
        
        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .announcement-title {
            font-weight: 600;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .announcement-date {
            color: #999;
            font-size: 12px;
        }
        
        .announcement-content {
            color: #555;
            font-size: 14px;
        }

        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .events-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .events-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }

        .site-footer {
            width: 100%;
            background: #222;
            color: #eee;
            padding: 22px 0 18px 0;
            text-align: center;
            font-size: 15px;
            margin-top: 40px;
            letter-spacing: 0.1px;
        }
        .site-footer .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 12px;
        }
        .site-footer a {
            color: #8fd855;
            text-decoration: none;
            transition: color 0.2s;
        }
        .site-footer a:hover {
            color: #b6ff8a;
        }
        .footer-sep {
            color: #555;
            margin: 0 6px;
        }
        .footer-socials .footer-icon {
            font-size: 18px;
            margin: 0 2px;
            vertical-align: middle;
            opacity: 0.85;
            transition: opacity 0.2s;
        }
        .footer-socials .footer-icon:hover {
            opacity: 1;
        }

        .cart-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: linear-gradient(135deg, #ff5f6d 0%, #ffc371 100%);
            color: #fff;
            border-radius: 50%;
            padding: 4px 10px;
            font-size: 15px;
            font-weight: 700;
            box-shadow: 0 4px 16px rgba(255,95,109,0.18);
            z-index: 20;
            border: 2px solid #fff;
            transition: transform 0.2s cubic-bezier(.4,2,.6,1), background 0.2s;
            animation: badge-pop 0.3s cubic-bezier(.4,2,.6,1);
        }
        @keyframes badge-pop {
            0% { transform: scale(0.5); }
            80% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }
        .cart-badge:hover {
            background: linear-gradient(135deg, #ffc371 0%, #ff5f6d 100%);
            transform: scale(1.1) rotate(-5deg);
        }

        .highlighted-carousel-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            position: relative;
            min-height: 260px;
            max-width: 1200px;
            padding: 0 20px;
        }
        .highlighted-carousel {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            max-width: 700px;
            position: relative;
            height: 260px;
            margin: 0 auto;
        }
        .highlighted-carousel-wrapper h3, .highlighted-carousel h3 {
            text-align: center;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }
        .highlighted-carousel-slide {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%) scale(0.7);
            opacity: 0.5;
            z-index: 1;
            transition: all 0.5s cubic-bezier(.4,2,.6,1);
            width: 320px;
            pointer-events: none;
            box-shadow: 0 2px 8px rgba(76,175,80,0.10);
        }
        .highlighted-carousel-slide.active {
            transform: translate(-50%, -50%) scale(1.08);
            opacity: 1;
            z-index: 3;
            pointer-events: auto;
            box-shadow: 0 8px 24px rgba(76,175,80,0.18);
        }
        .highlighted-carousel-slide.left {
            transform: translate(-120%, -50%) scale(0.8) rotate(-5deg);
            opacity: 0.7;
            z-index: 2;
        }
        .highlighted-carousel-slide.right {
            transform: translate(20%, -50%) scale(0.8) rotate(5deg);
            opacity: 0.7;
            z-index: 2;
        }
        @media (max-width: 600px) {
            .highlighted-carousel-slide, .highlighted-carousel-slide.active {
                width: 95vw;
                min-width: 0;
            }
            .highlighted-carousel {
                max-width: 98vw;
            }
        }

        /* Sadece Sepete Ekle butonu olan kartlarda üstteki çizgiyi kaldır */
        .event-card:has(.btn-add-to-cart)::before {
            display: none !important;
        }

        .header-btn.logout {
            color: #d32f2f;
            background: none;
        }
        .header-btn.logout:hover {
            color: #d32f2f;
            background: none;
            transform: translateY(-2px) scale(1.04);
        }
    </style>
</head>

<body>
<?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
    <div style="width:100%;background:#e8f5e9;padding:12px 0 8px 0;text-align:center;">
        <a href="admin.php" style="display:inline-block;background:#4CAF50;color:#fff;padding:8px 22px;border-radius:8px;font-weight:600;font-size:15px;text-decoration:none;box-shadow:0 2px 8px rgba(76,175,80,0.13);transition:background 0.2s;">⬅ Yönetici Paneline Dön</a>
    </div>
<?php endif; ?>
    <div class="dashboard-container">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="user-welcome">
                <div class="user-avatar">
                    <?php echo substr(htmlspecialchars($user['name'] ?? $user['email']), 0, 1); ?>
                </div>
                <h2>Hoş Geldiniz, <?php echo htmlspecialchars($user['name'] ?? $user['email']); ?>!</h2>
            </div>
            <div class="header-actions">
                <button onclick="window.location.href='cart.php'" class="header-btn" style="position:relative;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                    </svg>
                    Sepetim
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </button>
                <div class="header-divider"></div>
                <button onclick="window.location.href='logout.php'" class="header-btn" style="position:relative;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                        <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                    </svg>
                    Çıkış Yap
                </button>
            </div>
        </header>
        
        <!-- Weather Widget -->
        <div class="weather-widget">
            <div class="weather-icon">🌡️</div>
            <div class="weather-info" id="weather">Hava Durumu: Yükleniyor...</div>
        </div>
        
        <!-- Main Content -->
        <div class="dashboard-container">
            <?php if (isset($recommended) && count($recommended) > 0): ?>
            <!-- Önerilen Etkinlikler -->
            <div id="highlighted-interest">
                <h3>Sizin İçin Seçtiklerimiz</h3>
                <div class="highlighted-carousel">
                    <div class="highlighted-carousel-wrapper">
                        <div class="highlighted-carousel">
                            <?php foreach (array_slice($recommended, 0, 3) as $i => $highlight): ?>
                                <div class="event-card highlighted-carousel-slide" id="highlighted-event-<?php echo $i; ?>" data-event-date="<?php echo htmlspecialchars($highlight['event_date']); ?>">
                        <div class="event-header">
                            <span class="event-icon">⭐</span>
                            <div>
                                <h4 class="event-title"><?php echo htmlspecialchars($highlight['title']); ?></h4>
                                <span class="event-type"><?php echo htmlspecialchars($highlight['event_type']); ?></span>
                            </div>
                        </div>
                        <div class="event-details">
                                        <div class="event-detail">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                                            </svg>
                                            <?php echo htmlspecialchars($highlight['event_date']); ?>
                                        </div>
                                        <div class="event-detail">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                                            </svg>
                                            <?php echo htmlspecialchars($highlight['location']); ?>
                                        </div>
                        </div>
                        <div class="event-price-info">
                            <span class="event-price"><?php echo htmlspecialchars($highlight['price']); ?> TL</span>
                            <span class="event-remaining">Kalan: <?php echo htmlspecialchars($highlight['remaining']); ?></span>
                        </div>
                                    <div class="weather-warning"></div>
                                    <form method="post" action="cart.php" class="add-to-cart-form">
                            <input type="hidden" name="event_id" value="<?php echo $highlight['id']; ?>">
                            <button type="submit" class="btn-add-to-cart">Sepete Ekle</button>
                        </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <!-- Etkinlikler -->
            <div class="events-container">
                <div class="content-header">
                    <h3>Etkinlikler</h3>
                </div>
                <div class="events-grid">
                    <?php foreach ($events as $e): 
                                $emoji = '🎫'; // Varsayılan
                                $type = strtolower($e['event_type']);
                                if (strpos($type, 'konser') !== false) $emoji = '🎸';
                                else if (strpos($type, 'tiyatro') !== false) $emoji = '🎭';
                                else if (strpos($type, 'stand-up') !== false) $emoji = '🎤';
                                else if (strpos($type, 'festival') !== false) $emoji = '🎉';
                                else if (strpos($type, 'spor') !== false) $emoji = '🏟️';
                                else if (strpos($type, 'çocuk') !== false) $emoji = '🧒';
                                else if (strpos($type, 'outdor') !== false) $emoji = '🏟️';
                            ?>
                    <div class="event-card" data-event-date="<?php echo htmlspecialchars($e['event_date']); ?>">
                        <div class="event-header">
                            <span class="event-icon"><?php echo $emoji; ?></span>
                            <div>
                                <h4 class="event-title"><?php echo htmlspecialchars($e['title']); ?></h4>
                                <span class="event-type"><?php echo htmlspecialchars($e['event_type']); ?></span>
                            </div>
                        </div>
                        <div class="event-details">
                            <div class="event-detail">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                                </svg>
                                <?php echo htmlspecialchars($e['event_date']); ?>
                            </div>
                            <div class="event-detail">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                                </svg>
                                <?php echo htmlspecialchars($e['location']); ?>
                            </div>
                        </div>
                        <div class="event-price-info">
                            <span class="event-price"><?php echo htmlspecialchars($e['price']); ?> TL</span>
                            <span class="event-remaining">Kalan: <?php echo htmlspecialchars($e['remaining']); ?></span>
                        </div>
                        <div class="weather-warning"></div>
                        <form method="post" action="cart.php" class="add-to-cart-form">
                                <input type="hidden" name="event_id" value="<?php echo $e['id']; ?>">
                            <button type="submit" class="btn-add-to-cart">Sepete Ekle</button>
                            </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Announcements Section -->
            <div class="announcements-container">
                <div class="content-header">
                    <h3>Duyurular</h3>
                </div>
                
                <?php foreach ($announcements as $a):
                // Başlık ikonunu başlığa göre seç
                $title = mb_strtolower($a['title']);
                if (strpos($title, 'duyuru') !== false) $icon = '📢';
                else if (strpos($title, 'etkinlik') !== false) $icon = '🎉';
                else if (strpos($title, 'önemli') !== false) $icon = '⚠️';
                else if (strpos($title, 'bilgi') !== false) $icon = 'ℹ️';
                else $icon = '📰';
                // Tarih
                $date = isset($a['created_at']) ? date('d.m.Y', strtotime($a['created_at'])) : '';
            ?>
                <div class="announcement-card">
                    <div class="announcement-header">
                        <h4 class="announcement-title">
                            <span><?php echo $icon; ?></span>
                            <?php echo htmlspecialchars($a['title']); ?>
                        </h4>
                        <span class="announcement-date"><?php echo $date; ?></span>
                    </div>
                    <div class="announcement-content">
                    <?php echo htmlspecialchars($a['content']); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
        </div>
    </div>

    <footer class="site-footer">
        <div class="footer-content">
            <span>© 2024 Etkinlik Yönetim Sistemi | Tüm hakları saklıdır.</span>
            <span class="footer-sep">|</span>
            <span>İletişim: <a href="mailto:info@etkinliksistemi.com">info@etkinliksistemi.com</a></span>
            <span class="footer-sep">|</span>
            <span class="footer-socials">
                <a href="#" title="Instagram" class="footer-icon">📸</a>
                <a href="#" title="Twitter" class="footer-icon">🐦</a>
                <a href="#" title="Facebook" class="footer-icon">��</a>
            </span>
        </div>
    </footer>

    <script>
    // Hava durumu API'den çek
    fetch('weather_api.php')
        .then(res => res.json())
        .then(data => {
            // Etkinlik kartlarına hava durumu uyarısı ekle
            document.querySelectorAll('.event-card').forEach(function(card) {
                var eventDate = card.getAttribute('data-event-date');
                if (!eventDate) return;
                // Tahmin verisinde etkinlik tarihi var mı?
                var warningDiv = card.querySelector('.weather-warning');
                if (!warningDiv) return;
                var found = false;
                if (data.forecast) {
                    data.forecast.forEach(function(forecast) {
                        if (forecast.date === eventDate) {
                            found = true;
                            if (forecast.conditions === 'bad') {
                                warningDiv.innerHTML = '<div class="bad-weather">Uyarı: Hava koşulları etkinliğe katılım için uygun olmayabilir! (' + forecast.status + ', ' + forecast.temp + '°C)</div>';
                            } else if (forecast.conditions === 'moderate') {
                                warningDiv.innerHTML = '<div class="moderate-weather">Dikkat: Hava koşulları orta seviyede. (' + forecast.status + ', ' + forecast.temp + '°C)</div>';
                            } else {
                                warningDiv.innerHTML = '<div class="good-weather">Hava etkinlik için uygun. (' + forecast.status + ', ' + forecast.temp + '°C)</div>';
                            }
                        }
                    });
                }
                if (!found) {
                    warningDiv.innerHTML = '<div class="no-weather">Etkinlik günü için hava durumu tahmini yok.</div>';
                }
            });
            
            // Hava durumu widget'ını güncelle
            var weatherDiv = document.getElementById('weather');
            if (weatherDiv && data.current) {
                var currentWeather = data.current;
                var icon = '🌡️';
                
                // Hava durum ikonunu belirle
                if (currentWeather.status.toLowerCase().includes('açık')) icon = '☀️';
                else if (currentWeather.status.toLowerCase().includes('bulut')) icon = '☁️';
                else if (currentWeather.status.toLowerCase().includes('yağmur')) icon = '🌧️';
                else if (currentWeather.status.toLowerCase().includes('kar')) icon = '❄️';
                
                weatherDiv.innerHTML = 
                    '<div style="display:flex; align-items:center; gap:8px;">' +
                    '<span style="font-size:22px">' + icon + '</span>' +
                    '<div>' +
                    '<div style="font-weight:600; color:#2E7D32; font-size:16px">' + currentWeather.status + '</div>' +
                    '<div style="color:#666; font-size:14px">' + currentWeather.temp + '°C, ' + currentWeather.humidity + '% Nem</div>' +
                    '</div></div>';
            }
        })
        .catch(err => {
            document.querySelectorAll('.weather-warning').forEach(function(div) {
                div.innerHTML = '<div class="no-weather">Hava durumu bilgisi alınamadı.</div>';
            });
            
            var weatherDiv = document.getElementById('weather');
            if (weatherDiv) {
                weatherDiv.innerHTML = 'Hava durumu bilgisi alınamadı.';
            }
        });

    // Sepete ekle butonları için: Yönlendirmeyi engelle, AJAX ile ekle
    function updateCartBadge(count) {
        let badge = document.querySelector('.cart-badge');
        if (badge) {
            badge.textContent = count;
            badge.classList.remove('cart-badge-animate');
            void badge.offsetWidth; // reflow
            badge.classList.add('cart-badge-animate');
        }
    }
    document.querySelectorAll('.add-to-cart-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(form);
            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(() => {
                // Sepet sayısını 1 artır (veya sunucudan çekmek isterseniz AJAX ile döndürebilirsiniz)
                let badge = document.querySelector('.cart-badge');
                let count = parseInt(badge ? badge.textContent : '0') + 1;
                if (badge) {
                    updateCartBadge(count);
                } else {
                    // Badge yoksa oluştur
                    let btn = document.querySelector('.btn.btn-primary');
                    if (btn) {
                        let span = document.createElement('span');
                        span.className = 'cart-badge';
                        span.textContent = count;
                        btn.appendChild(span);
                    }
                }
            });
        });
    });

    // Carousel fonksiyonu
    let currentHighlight = 0;
    const totalHighlights = <?php echo min(3, count($recommended)); ?>;
    function updateCarousel() {
        const slides = document.querySelectorAll('.highlighted-carousel-slide');
        slides.forEach((slide, i) => {
            slide.classList.remove('active', 'left', 'right');
            slide.style.zIndex = 1;
            if (i === currentHighlight) {
                slide.classList.add('active');
                slide.style.zIndex = 3;
            } else if (i === (currentHighlight + 1) % totalHighlights) {
                slide.classList.add('right');
                slide.style.zIndex = 2;
            } else if (i === (currentHighlight + totalHighlights - 1) % totalHighlights) {
                slide.classList.add('left');
                slide.style.zIndex = 2;
            } else {
                slide.style.zIndex = 1;
            }
        });
    }
    function autoSlideCarousel() {
        currentHighlight = (currentHighlight + 1) % totalHighlights;
        updateCarousel();
    }
    document.addEventListener('DOMContentLoaded', function() {
        updateCarousel();
        setInterval(autoSlideCarousel, 3500);
    });
    </script>
</body>
</html> 