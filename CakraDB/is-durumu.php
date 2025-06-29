<?php
require_once '../php/config.php';  // config.php yolunu kendi yapına göre ayarla

header("Access-Control-Allow-Origin: https://cakrautu.com");
header("Content-Type: application/json; charset=UTF-8");

// İstekten iş kodunu al
if (!isset($_GET['kod'])) {
    echo json_encode(['error' => 'İş kodu belirtilmedi']);
    exit;
}

$kod = $_GET['kod'];

try {
    $stmt = $db->prepare("
        SELECT i.id, i.is_kodu, f.firma_adi, i.toplam_parca,
               (SELECT COALESCE(SUM(utu_parca),0) FROM gunluk_uretim WHERE is_id = i.id) AS toplam_utu,
               (SELECT COALESCE(SUM(paket_parca),0) FROM gunluk_uretim WHERE is_id = i.id) AS toplam_paket,
               i.durum,
               i.kayit_tarihi,
               i.teslim_tarihi,
               i.tamamlanma_tarihi
        FROM isler i
        JOIN firmalar f ON i.firma_id = f.id
        WHERE i.is_kodu = ?
    ");
    $stmt->execute([$kod]);
    $is = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($is) {
        $is['toplam_utu'] = (int) $is['toplam_utu'];
        $is['toplam_paket'] = (int) $is['toplam_paket'];

        // Tarihleri formatla, null ise null kalacak
        $dateFields = ['kayit_tarihi', 'teslim_tarihi', 'tamamlanma_tarihi'];
        foreach ($dateFields as $field) {
            if (!empty($is[$field])) {
                $is[$field] = date('Y-m-d', strtotime($is[$field]));
            } else {
                $is[$field] = null;
            }
        }

        echo json_encode($is);
    } else {
        echo json_encode(['error' => 'İş bulunamadı']);
    }
} catch(PDOException $e) {
    echo json_encode(['error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
