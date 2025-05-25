<?php
require_once 'includes/db.php';
session_start();
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: index.php');
    exit;
}

// Kullanıcı onaylama
if (isset($_GET['approve'])) {
    $uid = intval($_GET['approve']);
    $pdo->prepare('UPDATE users SET is_approved = 1 WHERE id = ?')->execute([$uid]);
    header('Location: admin.php');
    exit;
}
// Kullanıcı silme
if (isset($_GET['delete_user'])) {
    $uid = intval($_GET['delete_user']);
    // Önce kullanıcının biletlerini sil
    $pdo->prepare('DELETE FROM tickets WHERE user_id = ?')->execute([$uid]);
    // Sonra kullanıcıyı sil
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
    header('Location: admin.php');
    exit;
}
// Etkinlik ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $stmt = $pdo->prepare('INSERT INTO events (title, description, event_type, event_date, location, capacity, remaining, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $_POST['title'], $_POST['description'], $_POST['event_type'], $_POST['event_date'],
        $_POST['location'], $_POST['capacity'], $_POST['capacity'], $_POST['price']
    ]);
}
// Etkinlik silme
if (isset($_GET['delete_event'])) {
    $eid = intval($_GET['delete_event']);
    $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$eid]);
    header('Location: admin.php');
    exit;
}
// Duyuru ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $stmt = $pdo->prepare('INSERT INTO announcements (title, content) VALUES (?, ?)');
    $stmt->execute([$_POST['a_title'], $_POST['a_content']]);
}
// Duyuru silme
if (isset($_GET['delete_announcement'])) {
    $aid = intval($_GET['delete_announcement']);
    $pdo->prepare('DELETE FROM announcements WHERE id = ?')->execute([$aid]);
    header('Location: admin.php');
    exit;
}
// Kullanıcılar, etkinlikler, duyurular listesi
$users = $pdo->query('SELECT * FROM users WHERE is_admin = 0 ORDER BY created_at DESC')->fetchAll();
$events = $pdo->query('SELECT * FROM events ORDER BY event_date ASC')->fetchAll();
$announcements = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC')->fetchAll();
+$user_count = count($users);
+$event_count = count($events);
+$announcement_count = count($announcements);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Paneli</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4efe9 100%);
            color: #333;
            min-height: 100vh;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .admin-sidebar {
            width: 250px;
            background-color: #fff;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            padding: 30px 0;
            position: fixed;
            height: 100%;
            z-index: 10;
        }
        
        .admin-logo {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 20px;
        }
        
        .admin-logo h2 {
            color: #4CAF50;
            font-size: 22px;
            margin: 0;
            font-weight: 600;
        }
        
        .admin-nav {
            margin-top: 20px;
        }
        
        .nav-item {
            padding: 12px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #555;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .nav-item.active {
            background-color: #e8f5e9;
            color: #4CAF50;
            border-left: 4px solid #4CAF50;
        }
        
        .nav-item:hover {
            background-color: #f1f8e9;
            color: #4CAF50;
        }
        
        .nav-item-icon {
            font-size: 18px;
        }
        
        .admin-actions {
            padding: 0 25px;
            margin-top: 100px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .btn-action {
            background-color: #f2f2f2;
            color: #777;
            border: none;
            border-radius: 8px;
            padding: 14px;
            width: 100%;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
        }
        
        .btn-view-user {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-view-user:hover {
            background-color: #388E3C;
            transform: translateY(-2px);
        }
        
        .btn-logout {
            background-color: #f2f2f2;
            color: #777;
        }
        
        .btn-logout:hover {
            background-color: #f44336;
            color: white;
        }
        
        /* Main Content */
        .admin-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }
        
        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            color: #333;
            font-weight: 600;
            font-size: 24px;
            margin: 0;
        }
        
        .content-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .card-title {
            color: #333;
            font-weight: 600;
            font-size: 18px;
            margin: 0;
        }
        
        /* Users List */
        .users-list {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-list th, .users-list td {
            padding: 12px 16px;
            text-align: left;
        }
        
        .users-list th {
            background-color: #f5f5f5;
            font-weight: 600;
            color: #555;
        }
        
        .users-list tbody tr {
            border-bottom: 1px solid #eee;
        }
        
        .users-list tbody tr:hover {
            background-color: #f9f9f9;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: #e8f5e9;
            color: #388E3C;
        }
        
        .badge-danger {
            background-color: #ffebee;
            color: #d32f2f;
        }
        
        .action-btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            margin-right: 4px;
        }
        
        .btn-approve {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-approve:hover {
            background-color: #388E3C;
        }
        
        .btn-delete {
            background-color: #f44336;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #d32f2f;
        }
        
        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .form-field {
            margin-bottom: 16px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }
        
        .form-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .form-button:hover {
            background-color: #388E3C;
            transform: translateY(-2px);
        }
        
        /* Events and Announcements Lists */
        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .list-item:last-child {
            border-bottom: none;
        }
        
        .list-item-content {
            flex: 1;
        }
        
        .list-item-title {
            font-weight: 500;
            color: #333;
            margin-bottom: 4px;
        }
        
        .list-item-details {
            color: #777;
            font-size: 13px;
        }

        /* Tabs */
        .tab-container {
            margin-bottom: 20px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
        }
        
        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            color: #777;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .tab.active {
            color: #4CAF50;
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 3px;
            background-color: #4CAF50;
        }
        
        .tab-content {
            display: none;
            padding: 20px 0;
        }
        
        .tab-content.active {
            display: block;
        }

        .admin-nav-badge {
            background: #4CAF50;
            color: #fff;
            border-radius: 12px;
            padding: 2px 9px;
            font-size: 13px;
            font-weight: 600;
            margin-left: 7px;
            display: inline-block;
            min-width: 22px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(76,175,80,0.13);
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <h2>Etkinlik Admin</h2>
            </div>
            <nav class="admin-nav">
                <a href="#users" class="nav-item active" data-tab="users">
                    <span class="nav-item-icon">👤</span>
                    <span>Kullanıcılar</span>
                    <span class="admin-nav-badge"><?php echo $user_count; ?></span>
                </a>
                <a href="#events" class="nav-item" data-tab="events">
                    <span class="nav-item-icon">📅</span>
                    <span>Etkinlikler</span>
                    <span class="admin-nav-badge"><?php echo $event_count; ?></span>
                </a>
                <a href="#announcements" class="nav-item" data-tab="announcements">
                    <span class="nav-item-icon">📢</span>
                    <span>Duyurular</span>
                    <span class="admin-nav-badge"><?php echo $announcement_count; ?></span>
                </a>
            </nav>
            <div class="admin-actions">
                <a href="dashboard.php" class="btn-action btn-view-user">
                    <span>👤</span>
                    <span>Kullanıcı Sayfası</span>
                </a>
                <button onclick="window.location.href='logout.php'" class="btn-action btn-logout">
                    <span>📤</span>
                    <span>Çıkış Yap</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="page-header">
                <h1 class="page-title">Yönetici Paneli</h1>
            </div>

            <div class="tab-container">
                <div class="tabs">
                    <button class="tab active" data-tab="users">Kullanıcılar</button>
                    <button class="tab" data-tab="events">Etkinlikler</button>
                    <button class="tab" data-tab="announcements">Duyurular</button>
                </div>

                <!-- Users Tab -->
                <div id="users" class="tab-content active">
                    <div class="content-card">
                        <div class="card-header">
                            <h2 class="card-title">Kullanıcı Yönetimi</h2>
                        </div>
                        <table class="users-list">
                            <thead>
                                <tr>
                                    <th>E-posta</th>
                                    <th>İsim</th>
                                    <th>Durum</th>
                                    <th>İlgi Alanları</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
        <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><?php echo htmlspecialchars($u['name'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($u['is_approved']): ?>
                                            <span class="badge badge-success">Onaylı</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Onay Bekliyor</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['interests'] ?? '-'); ?></td>
                                    <td>
                <?php if (!$u['is_approved']): ?>
                                            <a href="admin.php?approve=<?php echo $u['id']; ?>" class="action-btn btn-approve">Onayla</a>
                <?php endif; ?>
                                        <a href="admin.php?delete_user=<?php echo $u['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Kullanıcı silinsin mi?')">Sil</a>
                                    </td>
                                </tr>
        <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Events Tab -->
                <div id="events" class="tab-content">
                    <div class="content-card">
                        <div class="card-header">
                            <h2 class="card-title">Etkinlik Ekle</h2>
                        </div>
    <form method="post">
        <input type="hidden" name="add_event" value="1">
                            <div class="form-grid">
                                <div class="form-field">
                                    <label class="form-label">Başlık</label>
                                    <input type="text" name="title" class="form-input" placeholder="Etkinlik başlığı" required>
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Tür</label>
                                    <input type="text" name="event_type" class="form-input" placeholder="Konser, Spor, ..." required>
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Yer</label>
                                    <input type="text" name="location" class="form-input" placeholder="Etkinlik yeri" required>
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Tarih</label>
                                    <input type="date" name="event_date" class="form-input" required>
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Kapasite</label>
                                    <input type="number" name="capacity" class="form-input" value="100" required>
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Fiyat (TL)</label>
                                    <input type="number" step="0.01" name="price" class="form-input" value="0" required>
                                </div>
                            </div>
                            <div class="form-field">
                                <label class="form-label">Açıklama</label>
                                <textarea name="description" class="form-input" rows="3" placeholder="Etkinlik açıklaması"></textarea>
                            </div>
                            <button type="submit" class="form-button">Etkinlik Ekle</button>
    </form>
                    </div>

                    <div class="content-card">
                        <div class="card-header">
                            <h2 class="card-title">Etkinlik Listesi</h2>
                        </div>
        <?php foreach ($events as $e): ?>
                            <div class="list-item">
                                <div class="list-item-content">
                                    <div class="list-item-title"><?php echo htmlspecialchars($e['title']); ?></div>
                                    <div class="list-item-details">
                                        <?php echo htmlspecialchars($e['event_type']); ?> | 
                                        <?php echo htmlspecialchars($e['event_date']); ?> | 
                                        <?php echo htmlspecialchars($e['location']); ?> |
                                        Fiyat: <?php echo htmlspecialchars($e['price']); ?> TL |
                                        Kapasite: <?php echo htmlspecialchars($e['remaining']); ?>/<?php echo htmlspecialchars($e['capacity']); ?>
                                    </div>
                                </div>
                                <a href="admin.php?delete_event=<?php echo $e['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Etkinlik silinsin mi?')">Sil</a>
                            </div>
        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Announcements Tab -->
                <div id="announcements" class="tab-content">
                    <div class="content-card">
                        <div class="card-header">
                            <h2 class="card-title">Duyuru Ekle</h2>
                        </div>
    <form method="post">
        <input type="hidden" name="add_announcement" value="1">
                            <div class="form-field">
                                <label class="form-label">Başlık</label>
                                <input type="text" name="a_title" class="form-input" placeholder="Duyuru başlığı" required>
                            </div>
                            <div class="form-field">
                                <label class="form-label">İçerik</label>
                                <textarea name="a_content" class="form-input" rows="3" placeholder="Duyuru içeriği" required></textarea>
                            </div>
                            <button type="submit" class="form-button">Duyuru Ekle</button>
    </form>
                    </div>

                    <div class="content-card">
                        <div class="card-header">
                            <h2 class="card-title">Duyuru Listesi</h2>
                        </div>
        <?php foreach ($announcements as $a): ?>
                            <div class="list-item">
                                <div class="list-item-content">
                                    <div class="list-item-title"><?php echo htmlspecialchars($a['title']); ?></div>
                                    <div class="list-item-details"><?php echo htmlspecialchars($a['content']); ?></div>
                                </div>
                                <a href="admin.php?delete_announcement=<?php echo $a['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Duyuru silinsin mi?')">Sil</a>
                            </div>
        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Tab switching functionality
        document.querySelectorAll('.tab, .nav-item').forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                const tabId = this.getAttribute('data-tab');
                
                // Update tabs
                document.querySelectorAll('.tab').forEach(t => {
                    t.classList.remove('active');
                    if (t.getAttribute('data-tab') === tabId) {
                        t.classList.add('active');
                    }
                });
                
                // Update nav items
                document.querySelectorAll('.nav-item').forEach(item => {
                    item.classList.remove('active');
                    if (item.getAttribute('data-tab') === tabId) {
                        item.classList.add('active');
                    }
                });
                
                // Update content
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                    if (content.id === tabId) {
                        content.classList.add('active');
                    }
                });
            });
        });
    </script>
</body>
</html> 