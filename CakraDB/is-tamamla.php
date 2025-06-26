<?php
require_once 'php/config.php';
require_once 'php/auth.php';
checkLogin();

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['is_id'])) {
    $is_id = intval($_POST['is_id']);
    
    try {
        $stmt = $db->prepare("UPDATE isler SET durum = 'tamamlandi', tamamlanma_tarihi = NOW() WHERE id = ?");
        $stmt->execute([$is_id]);
        
        echo json_encode(['success' => true, 'message' => 'İş tamamlandı olarak işaretlendi']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
}
?>