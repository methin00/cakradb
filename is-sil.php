<?php
require_once 'php/config.php';
require_once 'php/auth.php';
checkLogin();

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['is_id'])) {
    $is_id = intval($_POST['is_id']);
    
    try {
        // Önce günlük üretim kayıtlarını sil
        $stmt = $db->prepare("DELETE FROM gunluk_uretim WHERE is_id = ?");
        $stmt->execute([$is_id]);
        
        // Sonra işi sil
        $stmt = $db->prepare("DELETE FROM isler WHERE id = ?");
        $stmt->execute([$is_id]);
        
        echo json_encode(['success' => true, 'message' => 'İş başarıyla silindi']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
}
?>