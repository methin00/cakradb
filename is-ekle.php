<?php
require_once 'php/config.php';
require_once 'php/auth.php';
checkLogin();

// Firmaları veritabanından çekme
try {
    $stmt = $db->prepare("SELECT id, firma_adi FROM firmalar WHERE durum = 'aktif' ORDER BY firma_adi ASC");
    $stmt->execute();
    $firmalar = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Firmalar çekilirken hata: " . $e->getMessage());
}

// Form gönderildiğinde
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firma_id = intval($_POST['firma']);
    $toplam_parca = intval($_POST['toplamParca']);
    $teslim_tarihi = date('Y-m-d', strtotime($_POST['teslimTarihi']));
    $oncelik = $_POST['oncelik'];
    $aciklama = $_POST['aciklama'];
    
    try {
        // İş kodu oluştur (Örnek: FIRMA-001)
        $stmt = $db->prepare("SELECT firma_adi FROM firmalar WHERE id = ?");
        $stmt->execute([$firma_id]);
        $firma = $stmt->fetch();
        
        $firma_kodu = substr(strtoupper(str_replace(' ', '', $firma['firma_adi'])), 0, 5);
        $is_kodu = $firma_kodu . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        // Yeni iş ekleme
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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Yeni İş Ekle - ÇAKRA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
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

<nav class="navbar navbar-dark navbar-custom">
  <div class="container-fluid">
    <a class="navbar-brand" href="admin-panel.html">← Ana Sayfa | ÇAKRA</a>
  </div>
</nav>

<div class="main-container">
  <?php if(isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
  <?php endif; ?>

  <div class="header">
    <h2>📝 Yeni İş Kaydı</h2>
    <p>Sisteme yeni müşteri işi ekleyin</p>
  </div>
  
  <div class="content">
    <!-- İlerleme Göstergesi -->
    <div class="step-indicator">
      <div class="step active" id="step1">
        <div class="step-number">1</div>
        <div class="step-title">Firma Bilgileri</div>
      </div>
      <div class="step" id="step2">
        <div class="step-number">2</div>
        <div class="step-title">İş Detayları</div>
      </div>
      <div class="step" id="step3">
        <div class="step-number">3</div>
        <div class="step-title">Tarih & Notlar</div>
      </div>
    </div>

    <div class="form-group">
        <label for="firmaAdi" class="form-label">
          📦 Firma Adı
        </label>
        <input type="text" class="form-control" id="firmaAdi" placeholder="Örnek: XYZ Giyim" required>
        <div class="info-text">
          💡 İşin alındığı firmanın adı
        </div>
      </div>

      <!-- Toplam Parça Sayısı -->
      <div class="form-group">
        <label for="toplamParca" class="form-label">
          📦 Toplam Parça Sayısı
        </label>
        <input type="number" class="form-control" id="toplamParca" min="1" max="10000" placeholder="Örnek: 250" required>
        <div class="info-text">
          💡 İşlenecek toplam parça adedi (1-10.000 arasında)
        </div>
      </div>

      <!-- Son Teslim Tarihi -->
      <div class="form-group">
        <label for="teslimTarihi" class="form-label">
          📅 Son Teslim Tarihi
        </label>
        <input type="text" class="form-control flatpickr-input" id="teslimTarihi" placeholder="Tarih seçmek için tıklayın" required readonly>
        <div class="info-text">
          💡 İşin tamamlanması gereken son tarih
        </div>
      </div>

      <!-- Öncelik Seviyesi -->
      <div class="form-group">
        <label for="oncelik" class="form-label">
          ⚡ Öncelik Seviyesi
        </label>
        <select class="form-select" id="oncelik" required>
          <option value="">Öncelik seçin...</option>
          <option value="normal">🟢 Normal</option>
          <option value="yuksek">🟡 Yüksek</option>
          <option value="acil">🔴 Acil</option>
        </select>
        <div class="info-text">
          💡 İşin aciliyet durumunu belirleyin
        </div>
      </div>

      <!-- İş Notu -->
      <div class="form-group">
        <label for="aciklama" class="form-label">
          📝 İş Notu (Opsiyonel)
        </label>
        <textarea class="form-control" id="aciklama" rows="4" maxlength="500" placeholder="İşle ilgili özel notlarınızı buraya yazın..."></textarea>
        <div class="char-counter">
          <span id="charCount">0</span>/500 karakter
        </div>
        <div class="info-text">
          💡 Kumaş türü, özel işlemler, dikkat edilecek hususlar
        </div>
      </div>

      <!-- İlerleyiş Göstergesi -->
      <div id="progressContainer" class="progress-container" style="display: none;">
        <h6>📊 Form Tamamlanma Durumu</h6>
        <div class="progress" style="height: 8px;">
          <div class="progress-bar bg-success" id="formProgress" style="width: 0%"></div>
        </div>
        <small class="text-muted mt-2 d-block">
          <span id="progressText">0%</span> tamamlandı
        </small>
      </div>

      <!-- Kaydet Butonu -->
      <button type="submit" class="btn btn-custom" id="submitBtn">
        <span id="submitText">💾 İşi Sisteme Kaydet</span>
        <div class="spinner-border spinner-border-sm loading-spinner" role="status">
          <span class="visually-hidden">Kaydediliyor...</span>
        </div>
      </button>
    </form>

    <!-- Başarı Mesajı -->
    <div id="successMessage" class="alert alert-success alert-custom" style="display: none;">
      <h6>✅ Başarıyla Kaydedildi!</h6>
      <p class="mb-0">Yeni iş sisteme eklendi ve takip listesine alındı.</p>
    </div>

    <!-- Hata Mesajı -->
    <div id="errorMessage" class="alert alert-danger alert-custom" style="display: none;">
      <h6>❌ Hata Oluştu!</h6>
      <p class="mb-0" id="errorText">Lütfen tüm zorunlu alanları doldurun.</p>
    </div>
  </div>
</div>

<script>
// Flatpickr tarih seçici ayarları
flatpickr("#teslimTarihi", {
  dateFormat: "d.m.Y",
  locale: "tr",
  minDate: "today",
  maxDate: new Date().fp_incr(365), // 1 yıl ileri
  theme: "material_blue",
  onReady: function(selectedDates, dateStr, instance) {
    instance.calendarContainer.style.borderRadius = "15px";
    instance.calendarContainer.style.boxShadow = "0 10px 30px rgba(0,0,0,0.2)";
  },
  onChange: function(selectedDates, dateStr, instance) {
    updateSteps();
    updateProgress();
    validateInput(document.getElementById('teslimTarihi'), 1, 50);
  }
});

// Karakter sayacı
document.getElementById('aciklama').addEventListener('input', function() {
  const count = this.value.length;
  document.getElementById('charCount').textContent = count;
  
  if (count > 450) {
    document.getElementById('charCount').style.color = '#dc3545';
  } else {
    document.getElementById('charCount').style.color = '#6c757d';
  }
});

// Form ilerleme takibi
function updateProgress() {
  const fields = ['firma', 'toplamParca', 'teslimTarihi', 'oncelik'];
  const filled = fields.filter(field => {
    const element = document.getElementById(field);
    return element.value.trim() !== '';
  }).length;
  
  const percentage = Math.round((filled / fields.length) * 100);
  
  document.getElementById('formProgress').style.width = percentage + '%';
  document.getElementById('progressText').textContent = percentage + '%';
  
  if (percentage > 0) {
    document.getElementById('progressContainer').style.display = 'block';
  }
  
  return percentage;
}

// Adım göstergesi güncelleme
function updateSteps() {
  const firma = document.getElementById('firma').value.trim();
  const parcaCount = document.getElementById('toplamParca').value.trim();
  const tarih = document.getElementById('teslimTarihi').value.trim();
  
  // Tüm adımları sıfırla
  document.querySelectorAll('.step').forEach(step => {
    step.classList.remove('active', 'completed');
  });
  
  // Step 1
  if (firma) {
    document.getElementById('step1').classList.add('completed');
    document.getElementById('step2').classList.add('active');
  } else {
    document.getElementById('step1').classList.add('active');
  }
  
  // Step 2
  if (firma && parcaCount) {
    document.getElementById('step2').classList.add('completed');
    document.getElementById('step2').classList.remove('active');
    document.getElementById('step3').classList.add('active');
  }
  
  // Step 3
  if (firma && parcaCount && tarih) {
    document.getElementById('step3').classList.add('completed');
  }
}

// Input validasyonu
function validateInput(input, min, max, type = 'text') {
  const value = input.value.trim();
  
  if (type === 'number') {
    const num = parseInt(value);
    if (value === '' || isNaN(num) || num < min || num > max) {
      input.classList.add('is-invalid');
      input.classList.remove('is-valid');
      return false;
    }
  } else {
    if (value === '' || value.length < min || value.length > max) {
      input.classList.add('is-invalid');
      input.classList.remove('is-valid');
      return false;
    }
  }
  
  input.classList.add('is-valid');
  input.classList.remove('is-invalid');
  return true;
}

// Form alanlarına event listener'lar ekleme
document.getElementById('firma').addEventListener('input', function() {
  validateInput(this, 2, 100);
  updateSteps();
  updateProgress();
});

document.getElementById('toplamParca').addEventListener('input', function() {
  validateInput(this, 1, 10000, 'number');
  updateSteps();
  updateProgress();
});

document.getElementById('oncelik').addEventListener('change', function() {
  if (this.value !== '') {
    this.classList.add('is-valid');
    this.classList.remove('is-invalid');
  } else {
    this.classList.add('is-invalid');
    this.classList.remove('is-valid');
  }
  updateProgress();
});

// Form gönderimi
document.getElementById('jobForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const submitBtn = document.getElementById('submitBtn');
  const submitText = document.getElementById('submitText');
  const loadingSpinner = document.querySelector('.loading-spinner');
  const successMessage = document.getElementById('successMessage');
  const errorMessage = document.getElementById('errorMessage');
  
  // Hata ve başarı mesajlarını gizle
  successMessage.style.display = 'none';
  errorMessage.style.display = 'none';
  
  // Form verilerini al
  const formData = {
    firma: document.getElementById('firma').value.trim(),
    toplamParca: parseInt(document.getElementById('toplamParca').value),
    teslimTarihi: document.getElementById('teslimTarihi').value.trim(),
    oncelik: document.getElementById('oncelik').value,
    aciklama: document.getElementById('aciklama').value.trim()
  };
  
  // Validasyon kontrolleri
  let isValid = true;
  let errorMessages = [];
  
  if (!formData.firma || formData.firma.length < 2) {
    isValid = false;
    errorMessages.push('Firma adı en az 2 karakter olmalıdır');
  }
  
  if (!formData.toplamParca || formData.toplamParca < 1 || formData.toplamParca > 10000) {
    isValid = false;
    errorMessages.push('Parça sayısı 1-10.000 arasında olmalıdır');
  }
  
  if (!formData.teslimTarihi) {
    isValid = false;
    errorMessages.push('Teslim tarihi seçilmelidir');
  }
  
  if (!formData.oncelik) {
    isValid = false;
    errorMessages.push('Öncelik seviyesi seçilmelidir');
  }
  
  if (!isValid) {
    document.getElementById('errorText').textContent = errorMessages.join(', ');
    errorMessage.style.display = 'block';
    return;
  }
  
  // Yükleme durumunu göster
  submitBtn.disabled = true;
  submitText.textContent = 'Kaydediliyor...';
  loadingSpinner.style.display = 'inline-block';
  
  // Simüle edilmiş kaydetme işlemi
  setTimeout(() => {
    // Başarılı kayıt simülasyonu
    const jobId = 'JOB-' + Date.now().toString().substr(-6);
    
    console.log('Yeni iş kaydedildi:', {
      id: jobId,
      ...formData,
      kayitTarihi: new Date().toLocaleString('tr-TR'),
      durum: 'beklemede'
    });
    
    // Başarı mesajını göster
    successMessage.innerHTML = `
      <h6>✅ Başarıyla Kaydedildi!</h6>
      <p class="mb-2">İş ID: <strong>${jobId}</strong></p>
      <p class="mb-0">Yeni iş sisteme eklendi ve takip listesine alındı.</p>
    `;
    successMessage.style.display = 'block';
    
    // Formu temizle
    document.getElementById('jobForm').reset();
    document.getElementById('charCount').textContent = '0';
    document.getElementById('progressContainer').style.display = 'none';
    
    // Validasyon sınıflarını temizle
    document.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
      el.classList.remove('is-valid', 'is-invalid');
    });
    
    // Adım göstergesini sıfırla
    document.querySelectorAll('.step').forEach(step => {
      step.classList.remove('active', 'completed');
    });
    document.getElementById('step1').classList.add('active');
    
    // Butonu sıfırla
    submitBtn.disabled = false;
    submitText.textContent = '💾 İşi Sisteme Kaydet';
    loadingSpinner.style.display = 'none';
    
    // Sayfayı yukarı kaydır
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
  }, 2000); // 2 saniye bekleme simülasyonu
});

// Sayfa yüklendiğinde ilk durumu ayarla
document.addEventListener('DOMContentLoaded', function() {
  updateProgress();
  updateSteps();
});

// Gerçek zamanlı validasyon feedback'i
document.querySelectorAll('.form-control, .form-select').forEach(input => {
  input.addEventListener('blur', function() {
    if (this.hasAttribute('required') && this.value.trim() === '') {
      this.classList.add('is-invalid');
      this.classList.remove('is-valid');
    }
  });
});
</script>

</body>
</html>