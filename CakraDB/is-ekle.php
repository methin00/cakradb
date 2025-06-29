<?php
require_once 'php/config.php';
require_once 'php/auth.php';
checkLogin();

if (isset($_POST['firma_sil'])) {
    $firma_id = intval($_POST['firma_id']);

    try {
        $stmt = $db->prepare("UPDATE firmalar SET durum = 'pasif' WHERE id = ?");
        $stmt->execute([$firma_id]);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit();
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}

try {
    $stmt = $db->prepare("SELECT id, firma_adi FROM firmalar WHERE durum = 'aktif' ORDER BY firma_adi ASC");
    $stmt->execute();
    $firmalar = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Firmalar çekilirken hata: " . $e->getMessage());
}

if (isset($_POST['yeni_firma'])) {
    $firma_adi = trim($_POST['firma_adi']);
    $telefon = trim($_POST['telefon'] ?? null);

    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM firmalar WHERE firma_adi = ?");
        $stmt->execute([$firma_adi]);
        $sayac = $stmt->fetchColumn();

        if ($sayac > 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Bu firma adı zaten mevcut!'
            ]);
            exit();
        }

        $stmt = $db->prepare("INSERT INTO firmalar (firma_adi, telefon, durum) VALUES (?, ?, 'aktif')");
        $stmt->execute([$firma_adi, $telefon]);

        $yeni_firma_id = $db->lastInsertId();

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'firma_id' => $yeni_firma_id,
            'firma_adi' => $firma_adi
        ]);
        exit();
    } catch(PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Firma eklenirken hata: ' . $e->getMessage()
        ]);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['yeni_firma']) && !isset($_POST['firma_sil'])) {
    $firma_id = intval($_POST['firma']);
    $toplam_parca = intval($_POST['toplamParca']);
    $teslim_tarihi = date('Y-m-d', strtotime($_POST['teslimTarihi']));
    $oncelik = $_POST['oncelik'];
    $aciklama = trim($_POST['aciklama']);

    try {
        $stmt = $db->prepare("SELECT firma_adi FROM firmalar WHERE id = ?");
        $stmt->execute([$firma_id]);
        $firma = $stmt->fetch();

        $firma_kodu = substr(strtoupper(str_replace(' ', '', $firma['firma_adi'])), 0, 5);
        $is_kodu = $firma_kodu . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);

        $stmt = $db->prepare("INSERT INTO isler (is_kodu, firma_id, toplam_parca, teslim_tarihi, oncelik, aciklama) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$is_kodu, $firma_id, $toplam_parca, $teslim_tarihi, $oncelik, $aciklama]);

        $_SESSION['success'] = "Yeni iş başarıyla eklendi! İş Kodu: " . $is_kodu;
        header("Location: admin-panel.php");
        exit();
    } catch(PDOException $e) {
        $error = "İş eklenirken hata: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Yeni İş Ekle - ÇAKRA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" />
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .navbar-custom {
      background: linear-gradient(135deg, #2c3e50, #34495e) !important;
      padding: 15px 20px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .navbar-brand {
      font-size: 20px;
      font-weight: 600;
      color: white !important;
      transition: all 0.3s ease;
    }
    
    .navbar-brand:hover {
      color: #667eea !important;
    }
    
    .main-container {
      max-width: 700px;
      margin: 30px auto;
      background: white;
      border-radius: 25px;
      box-shadow: 0 25px 50px rgba(0,0,0,0.15);
      overflow: hidden;
      animation: slideUp 0.6s ease-out;
    }
    
    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-20px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }
    
    .header {
      background: linear-gradient(135deg, #28a745, #20c997);
      color: white;
      padding: 40px;
      text-align: center;
    }
    
    .header h2 {
      margin: 0;
      font-size: 2.2rem;
      font-weight: 300;
      margin-bottom: 10px;
    }
    
    .header p {
      margin: 0;
      opacity: 0.9;
      font-size: 16px;
    }
    
    .content {
      padding: 50px;
    }
    
    .form-group {
      margin-bottom: 30px;
    }
    
    .form-label {
      font-weight: 600;
      color: #2c3e50;
      margin-bottom: 12px;
      font-size: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .form-control, .form-select {
      border-radius: 15px;
      border: 2px solid #e0e6ed;
      padding: 18px 20px;
      font-size: 16px;
      transition: all 0.3s ease;
      background: #f8f9ff;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
      background: white;
    }
    
    .form-control.is-valid {
      border-color: #28a745;
      background: #f0fff4;
    }
    
    .form-control.is-invalid {
      border-color: #dc3545;
      background: #fff5f5;
    }
    
    .info-text {
      color: #6c757d;
      font-size: 14px;
      margin-top: 8px;
      display: flex;
      align-items: center;
      gap: 5px;
    }
    
    .btn-custom {
      background: linear-gradient(135deg, #28a745, #20c997);
      border: none;
      border-radius: 15px;
      padding: 18px 35px;
      font-size: 18px;
      font-weight: 600;
      color: white;
      width: 100%;
      transition: all 0.3s ease;
      margin-top: 20px;
    }
    
    .btn-custom:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 30px rgba(40, 167, 69, 0.4);
      color: white;
    }
    
    .btn-custom:active {
      transform: translateY(-1px);
    }
    
    .input-group .btn-success {
      border-radius: 0 15px 15px 0;
      padding: 18px 20px;
      white-space: nowrap;
    }
    
    .input-group .btn-success i {
      margin-right: 5px;
    }
    
    .modal-content {
      border-radius: 20px;
      overflow: hidden;
    }
    
    .modal-header {
      background: linear-gradient(135deg, #28a745, #20c997);
      color: white;
    }
    
    .modal-title {
      font-weight: 600;
    }
    
    .modal-footer .btn {
      border-radius: 10px;
      padding: 10px 20px;
      font-weight: 500;
    }
    
    .btn-custom:disabled {
      background: #6c757d;
      transform: none;
      box-shadow: none;
    }
    
    .alert-custom {
      border-radius: 15px;
      border: none;
      padding: 20px;
      font-size: 16px;
      font-weight: 500;
      margin-top: 25px;
      animation: slideIn 0.5s ease-out;
    }
    
    .alert-success {
      background: linear-gradient(135deg, #d4edda, #c3e6cb);
      color: #155724;
      border: 2px solid #b8dacc;
    }
    
    .alert-danger {
      background: linear-gradient(135deg, #f8d7da, #f1b8bd);
      color: #721c24;
      border: 2px solid #f5c6cb;
    }
    
    .step-indicator {
      display: flex;
      justify-content: space-between;
      margin-bottom: 40px;
    }
    
    .step {
      flex: 1;
      text-align: center;
      position: relative;
    }
    
    .step-number {
      background: #e0e6ed;
      color: #6c757d;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      margin: 0 auto 10px;
      transition: all 0.3s ease;
    }
    
    .step.active .step-number {
      background: #667eea;
      color: white;
    }
    
    .step.completed .step-number {
      background: #28a745;
      color: white;
    }
    
    .step-title {
      font-size: 14px;
      color: #6c757d;
      font-weight: 500;
    }
    
    .step.active .step-title {
      color: #667eea;
      font-weight: 600;
    }
    
    .step.completed .step-title {
      color: #28a745;
      font-weight: 600;
    }
    
    .loading-spinner {
      display: none;
      margin-left: 10px;
    }
    
    .char-counter {
      text-align: right;
      font-size: 12px;
      color: #6c757d;
      margin-top: 5px;
    }
    
    .progress-container {
      background: #f8f9ff;
      border-radius: 15px;
      padding: 20px;
      margin-top: 25px;
      border: 2px solid #e0e6ed;
    }
    
    .flatpickr-input {
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='%236c757d' d='M6 1a1 1 0 0 1 2 0v1h2V1a1 1 0 1 1 2 0v1h1.5A1.5 1.5 0 0 1 15 3.5v10A1.5 1.5 0 0 1 13.5 15h-11A1.5 1.5 0 0 1 1 13.5v-10A1.5 1.5 0 0 1 2.5 2H4V1a1 1 0 0 1 2 0v1h0zm-5 4v8.5A.5.5 0 0 0 1.5 14h11a.5.5 0 0 0 .5-.5V5H1z'/%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-position: right 20px center;
      background-size: 16px;
      padding-right: 50px;
    }
    
    @media (max-width: 768px) {
      .main-container {
        margin: 20px 15px;
        border-radius: 20px;
      }
      
      .content {
        padding: 30px 25px;
      }
      
      .header {
        padding: 30px 25px;
      }
      
      .header h2 {
        font-size: 1.8rem;
      }
    }
  </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark p-3">
  <div class="container-fluid">
    <a class="navbar-brand" href="admin-panel.php">← Ana Sayfa | ÇAKRA</a>
  </div>
</nav>

<div class="container my-4 p-4 bg-white rounded shadow" style="max-width:700px;">

  <?php if(isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <h2 class="mb-4">📝 Yeni İş Kaydı</h2>

  <form id="jobForm" method="POST" novalidate>
    <div class="mb-3">
      <label for="firma" class="form-label">🏢 Firma Adı</label>
      <div class="input-group">
        <select class="form-select" id="firma" name="firma" required>
          <option value="">Firma seçin...</option>
          <?php foreach($firmalar as $firma): ?>
            <option value="<?= htmlspecialchars($firma['id']) ?>"><?= htmlspecialchars($firma['firma_adi']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#firmaEkleModal">
          <i class="bi bi-plus-lg"></i> Yeni Ekle
        </button>
        <button type="button" class="btn btn-danger" id="firmaSilBtn" title="Seçili firmayı sil">
          <i class="bi bi-trash"></i> Sil
        </button>
      </div>
    </div>

    <div class="mb-3">
      <label for="toplamParca" class="form-label">📦 Toplam Parça Sayısı</label>
      <input type="number" class="form-control" id="toplamParca" name="toplamParca" min="1" max="10000" required placeholder="Örnek: 250" />
    </div>

    <div class="mb-3">
      <label for="teslimTarihi" class="form-label">📅 Son Teslim Tarihi</label>
      <input type="text" class="form-control" id="teslimTarihi" name="teslimTarihi" required placeholder="Tarih seçmek için tıklayın" readonly />
    </div>

    <div class="mb-3">
      <label for="oncelik" class="form-label">⚡ Öncelik Seviyesi</label>
      <select class="form-select" id="oncelik" name="oncelik" required>
        <option value="">Öncelik seçin...</option>
        <option value="normal">🟢 Normal</option>
        <option value="yuksek">🟡 Yüksek</option>
        <option value="acil">🔴 Acil</option>
      </select>
    </div>

    <div class="mb-3">
      <label for="aciklama" class="form-label">📝 İş Notu (Opsiyonel)</label>
      <textarea class="form-control" id="aciklama" name="aciklama" rows="4" maxlength="500" placeholder="İşle ilgili özel notlarınızı buraya yazın..."></textarea>
      <div class="text-end text-muted" style="font-size: 12px;"><span id="charCount">0</span>/500</div>
    </div>

    <button type="submit" class="btn btn-success w-100" id="submitBtn">
      💾 İşi Sisteme Kaydet
    </button>
  </form>
</div>

<div class="modal fade" id="firmaEkleModal" tabindex="-1" aria-labelledby="firmaEkleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content rounded-3">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="firmaEkleModalLabel">Yeni Firma Ekle</h5>
        <button type="button" class="wierd
        <div class="modal-body">
          <div class="mb-3">
            <label for="yeniFirmaAdi" class="form-label">Firma Adı *</label>
            <input type="text" class="form-control" id="yeniFirmaAdi" required>
          </div>
          <div class="mb-3">
            <label for="yeniFirmaTelefon" class="form-label">Telefon</label>
            <input type="tel" class="form-control" id="yeniFirmaTelefon">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-success" id="firmaEkleKaydet">Kaydet</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const firmaEkleModal = new bootstrap.Modal(document.getElementById('firmaEkleModal'));

  flatpickr("#teslimTarihi", {
    dateFormat: "d.m.Y",
    locale: "tr",
    minDate: "today"
  });

  const aciklama = document.getElementById('aciklama');
  const charCount = document.getElementById('charCount');
  aciklama.addEventListener('input', () => {
    charCount.textContent = aciklama.value.length;
  });

  document.getElementById('firmaEkleKaydet').addEventListener('click', function () {
    const firmaAdi = document.getElementById('yeniFirmaAdi').value.trim();
    const telefon = document.getElementById('yeniFirmaTelefon').value.trim();

    if (!firmaAdi) {
      alert('Firma adı zorunludur!');
      return;
    }

    const formData = new URLSearchParams();
    formData.append('yeni_firma', '1');
    formData.append('firma_adi', firmaAdi);
    formData.append('telefon', telefon);

    fetch(window.location.href, {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        const select = document.getElementById('firma');
        const option = document.createElement('option');
        option.value = data.firma_id;
        option.textContent = data.firma_adi;
        select.appendChild(option);
        select.value = data.firma_id;

        firmaEkleModal.hide();
        document.getElementById('yeniFirmaAdi').value = '';
        document.getElementById('yeniFirmaTelefon').value = '';
      } else {
        alert(data.message || 'Firma eklenirken hata oluştu.');
      }
    })
    .catch(err => alert('Bir hata oluştu: ' + err.message));
  });

  const firmaSilBtn = document.getElementById('firmaSilBtn');
  const firmaSelect = document.getElementById('firma');

  firmaSilBtn.addEventListener('click', function () {
    const firmaId = firmaSelect.value;
    const firmaAdi = firmaSelect.options[firmaSelect.selectedIndex]?.text || '';

    if (!firmaId) {
      alert('Lütfen önce silmek istediğiniz firmayı seçin.');
      return;
    }

    if (!confirm(`"${firmaAdi}" firmasını silmek istediğinize emin misiniz?`)) {
      return;
    }

    const formData = new URLSearchParams();
    formData.append('firma_sil', '1');
    formData.append('firma_id', firmaId);

    fetch(window.location.href, {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        const optionToRemove = firmaSelect.querySelector(`option[value="${firmaId}"]`);
        if (optionToRemove) optionToRemove.remove();

        firmaSelect.value = '';

        alert('Firma başarıyla silindi.');
      } else {
        alert(data.message || 'Firma silinirken hata oluştu.');
      }
    })
    .catch(err => alert('Bir hata oluştu: ' + err.message));
  });
});
</script>

</body>
</html>
