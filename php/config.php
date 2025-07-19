<?php
define('DB_HOST', 'localhost'); // Veya Hostinger'in size özel verdiği sunucu adresi
define('DB_USER', 'u307440943_admincakra'); // Veritabanı kullanıcı adı
define('DB_PASS', 'Cakrautu123!'); // Kullanıcı şifresi (size verilen)
define('DB_NAME', 'u307440943_cakra_db'); // Veritabanı adı

// PDO bağlantısı oluşturma
try {
    $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Oturum başlatma
session_start();

// Temel fonksiyonlar
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>