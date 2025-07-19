<?php
require_once 'php/config.php';
require_once 'php/auth.php';

header('Content-Type: application/json');

// Giriş bilgilerini al
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

try {
    $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if($user && password_verify($password, $user['sifre'])) {
        // Giriş başarılı
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['ad_soyad'];
        $_SESSION['user_role'] = $user['yetki_seviyesi'];
        
        // Son giriş tarihini güncelle
        $update = $db->prepare("UPDATE kullanicilar SET son_giris = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        
        echo json_encode([
            'success' => true,
            'redirect' => 'admin-panel.php',
            'message' => 'Giriş başarılı'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Geçersiz kullanıcı adı veya şifre'
        ]);
    }
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>