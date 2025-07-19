<?php
require_once 'config.php';

// Giriş kontrolü
function checkLogin() {
    if(!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}

// Giriş yapma
if(isset($_POST['login'])) {
    $username = clean_input($_POST['username']);
    $password = clean_input($_POST['password']);

    try {
        $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if($user && password_verify($password, $user['sifre'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['ad_soyad'];
            $_SESSION['user_role'] = $user['yetki_seviyesi'];
            
            // Son giriş tarihini güncelle
            $update = $db->prepare("UPDATE kullanicilar SET son_giris = NOW() WHERE id = ?");
            $update->execute([$user['id']]);
            
            header("Location: admin-panel.php");
            exit();
        } else {
            header("Location: index.php?error=1");
            exit();
        }
    } catch(PDOException $e) {
        die("Giriş işlemi sırasında hata: " . $e->getMessage());
    }
}

// Çıkış yapma
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>