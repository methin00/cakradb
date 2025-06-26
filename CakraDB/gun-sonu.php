<?php
require_once 'php/config.php';
require_once 'php/auth.php';
checkLogin();

// Veritabanından aktif işleri çekme
try {
    $stmt = $db->prepare("
        SELECT i.id, i.is_kodu, f.firma_adi, i.toplam_parca,
               (SELECT SUM(utu_parca) FROM gunluk_uretim WHERE is_id = i.id) AS toplam_utu,
               (SELECT SUM(paket_parca) FROM gunluk_uretim WHERE is_id = i.id) AS toplam_paket
        FROM isler i
        JOIN firmalar f ON i.firma_id = f.id
        WHERE i.durum = 'devam'
        ORDER BY i.oncelik DESC, i.teslim_tarihi ASC
    ");
    $stmt->execute();
    $activeJobs = $stmt->fetchAll();
} catch(PDOException $e) {
    die("İşler çekilirken hata: " . $e->getMessage());
}

// Form gönderildiğinde
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $is_id = intval($_POST['firmaSecim']);
    $utuMiktar = intval($_POST['utuMiktar']);
    $paketMiktar = intval($_POST['paketMiktar']);
    $tarih = date('Y-m-d');
    
    try {
        // Günlük üretim kaydı ekleme
        $stmt = $db->prepare("INSERT INTO gunluk_uretim (is_id, tarih, utu_parca, paket_parca, kullanici_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$is_id, $tarih, $utuMiktar, $paketMiktar, $_SESSION['user_id']]);
        
        // Başarı mesajı
        $_SESSION['success'] = "Gün sonu verileri başarıyla kaydedildi!";
        header("Location: gun-sonu.php");
        exit();
    } catch(PDOException $e) {
        $error = "Kayıt sırasında hata: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gün Sonu Girişi - ÇAKRA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .main-container {
      max-width: 800px;
      margin: 20px auto;
      background: white;
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.1);
      overflow: hidden;
    }
    
    .header {
      background: linear-gradient(135deg, #4CAF50, #45a049);
      color: white;
      padding: 30px;
      text-align: center;
    }
    
    .header h2 {
      margin: 0;
      font-size: 2rem;
      font-weight: 300;
    }
    
    .content {
      padding: 40px;
    }
    
    .step-card {
      background: #f8f9ff;
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 25px;
      border-left: 5px solid #667eea;
    }
    
    .step-number {
      background: #667eea;
      color: white;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 18px;
      margin-bottom: 15px;
    }
    
    .form-control, .form-select {
      border-radius: 10px;
      border: 2px solid #e0e6ed;
      padding: 15px;
      font-size: 16px;
      transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .btn-custom {
      background: linear-gradient(135deg, #667eea, #764ba2);
      border: none;
      border-radius: 15px;
      padding: 15px 30px;
      font-size: 18px;
      font-weight: 600;
      color: white;
      width: 100%;
      transition: all 0.3s ease;
    }
    
    .btn-custom:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
      color: white;
    }
    
    .progress-container {
      background: #fff;
      border-radius: 15px;
      padding: 20px;
      border: 2px solid #e0e6ed;
      margin-top: 20px;
    }
    
    .progress {
      height: 25px;
      border-radius: 15px;
      margin-bottom: 10px;
    }
    
    .progress-bar {
      border-radius: 15px;
      background: linear-gradient(135deg, #4CAF50, #45a049);
    }
    
    .alert-custom {
      border-radius: 15px;
      border: none;
      padding: 20px;
      font-size: 16px;
    }
    
    .navbar-custom {
      background: linear-gradient(135deg, #2c3e50, #34495e) !important;
      padding: 15px 20px;
    }
    
    .navbar-brand {
      font-size: 20px;
      font-weight: 600;
    }
    
    .info-text {
      color: #6c757d;
      font-size: 14px;
      margin-top: 8px;
    }
    
    .summary-card {
      background: linear-gradient(135deg, #e3f2fd, #bbdefb);
      border-radius: 15px;
      padding: 20px;
      margin-top: 20px;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-dark navbar-custom">
  <div class="container-fluid">
    <a class="navbar-brand" href="admin-panel.php">← Ana Sayfa | ÇAKRA</a>
  </div>
</nav>

<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
  <?php endif; ?>
  
  <?php if(isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
  <?php endif; ?>

  <div class="header">
    <h2>🕔 Gün Sonu Girişi</h2>
    <p class="mb-0">Bugün yapılan işleri kaydedin</p>
  </div>
  
  <div class="content">
    <form id="gunSonuForm" method="POST">
      <!-- Firma Seçimi -->
      <div class="step-card">
        <div class="step-number">1</div>
        <h5>Hangi firmanın işi yapıldı?</h5>
        <select class="form-select" id="firmaSecim" name="firmaSecim" required>
          <option value="">Firma seçin...</option>
          <?php foreach($activeJobs as $job): ?>
            <option value="<?php echo $job['id']; ?>">
              <?php echo $job['firma_adi'] . ' (' . $job['is_kodu'] . ') - ' . ($job['toplam_parca'] - $job['toplam_utu']) . ' parça kaldı'; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Adım 2: Ütü İşlemi -->
      <div class="step-card">
        <div class="step-number">2</div>
        <h5>Bugün kaç parça ütü yapıldı?</h5>
        <input type="number" class="form-control" id="utuMiktar" min="0" placeholder="Örnek: 50">
        <div class="info-text">Sadece sayı girin (örnek: 25, 100, vb.)</div>
      </div>

      <!-- Adım 3: Paketleme İşlemi -->
      <div class="step-card">
        <div class="step-number">3</div>
        <h5>Bugün kaç parça paketleme yapıldı?</h5>
        <input type="number" class="form-control" id="paketMiktar" min="0" placeholder="Örnek: 30">
        <div class="info-text">Sadece sayı girin (örnek: 20, 80, vb.)</div>
      </div>

      <!-- İlerleme Göstergesi -->
      <div id="ilerlemeDurumu" class="progress-container" style="display: none;">
        <h6>📊 İş Durumu</h6>
        <div class="row">
          <div class="col-md-6">
            <label class="form-label">Ütü İlerlemesi</label>
            <div class="progress">
              <div class="progress-bar" id="utuProgress" style="width: 0%"></div>
            </div>
            <small id="utuText">0 / 0 parça</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Paketleme İlerlemesi</label>
            <div class="progress">
              <div class="progress-bar" id="paketProgress" style="width: 0%"></div>
            </div>
            <small id="paketText">0 / 0 parça</small>
          </div>
        </div>
      </div>

      <!-- Kaydet Butonu -->
      <button type="submit" class="btn btn-custom mt-4">
        💾 Gün Sonu Verilerini Kaydet
      </button>
    </form>

    <!-- Başarı Mesajı -->
    <div id="basariMesaji" class="alert alert-success alert-custom mt-3" style="display: none;">
      <h6>✅ Başarıyla Kaydedildi!</h6>
      <p class="mb-0">Bugünkü üretim verileri sisteme başarıyla işlendi.</p>
    </div>

    <!-- Özet Kartı -->
    <div id="ozetKart" class="summary-card" style="display: none;">
      <h6>📋 Günlük Özet</h6>
      <div class="row">
        <div class="col-6">
          <strong>Toplam Ütü:</strong><br>
          <span id="toplamUtu" class="h5 text-success">0 parça</span>
        </div>
        <div class="col-6">
          <strong>Toplam Paket:</strong><br>
          <span id="toplamPaket" class="h5 text-info">0 parça</span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Örnek firma verileri (normalde PHP'den gelecek)
const firmaVerileri = {
  'moda-tekstil': {
    ad: 'Moda Tekstil',
    toplamParca: 250,
    mevcutUtu: 150,
    mevcutPaket: 50
  },
  'inci-giyim': {
    ad: 'İnci Giyim', 
    toplamParca: 300,
    mevcutUtu: 300,
    mevcutPaket: 300
  },
  'elit-moda': {
    ad: 'Elit Moda',
    toplamParca: 180,
    mevcutUtu: 80,
    mevcutPaket: 30
  }
};

// Firma seçildiğinde ilerleme durumunu göster
document.getElementById('firmaSecim').addEventListener('change', function() {
  const seciliFirma = this.value;
  
  if (seciliFirma) {
    const firma = firmaVerileri[seciliFirma];
    document.getElementById('ilerlemeDurumu').style.display = 'block';
    
    // İlerleme barlarını güncelle
    guncellemeIlerleme(firma.mevcutUtu, firma.toplamParca, 'utu');
    guncellemeIlerleme(firma.mevcutPaket, firma.mevcutUtu, 'paket'); // Paket max ütü kadar olabilir
    
    // Kalan miktarları göster
    const kalanUtu = firma.toplamParca - firma.mevcutUtu;
    const kalanPaket = firma.mevcutUtu - firma.mevcutPaket;
    
    // Input placeholder'larını güncelle
    document.getElementById('utuMiktar').placeholder = `Kalan: ${kalanUtu} parça`;
    document.getElementById('paketMiktar').placeholder = `Kalan: ${kalanPaket} parça`;
    
    // Max değerlerini ayarla
    document.getElementById('utuMiktar').max = kalanUtu;
    document.getElementById('paketMiktar').max = kalanPaket;
    
  } else {
    document.getElementById('ilerlemeDurumu').style.display = 'none';
    document.getElementById('utuMiktar').placeholder = 'Örnek: 50';
    document.getElementById('paketMiktar').placeholder = 'Örnek: 30';
    document.getElementById('utuMiktar').max = '';
    document.getElementById('paketMiktar').max = '';
  }
});

function guncellemeIlerleme(mevcut, toplam, tip) {
  const yuzde = Math.round((mevcut / toplam) * 100);
  document.getElementById(tip + 'Progress').style.width = yuzde + '%';
  document.getElementById(tip + 'Text').textContent = mevcut + ' / ' + toplam + ' parça (' + yuzde + '%)';
}

// Form gönderildiğinde
document.getElementById('gunSonuForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const firma = document.getElementById('firmaSecim').value;
  const utuMiktar = parseInt(document.getElementById('utuMiktar').value) || 0;
  const paketMiktar = parseInt(document.getElementById('paketMiktar').value) || 0;
  
  if (!firma) {
    alert('❌ Lütfen bir firma seçin!');
    return;
  }
  
  if (!utuMiktar && !paketMiktar) {
    alert('❌ En az bir alanda sayı girmelisiniz!');
    return;
  }
  
  // Seçili firma verilerini al
  const firmaVeri = firmaVerileri[firma];
  
  // Ütü kontrolleri
  const kalanUtu = firmaVeri.toplamParca - firmaVeri.mevcutUtu;
  if (utuMiktar > kalanUtu) {
    alert(`❌ HATA: ${firmaVeri.ad} için ${kalanUtu} parça ütü kaldı, ${utuMiktar} parça giremezsiniz!`);
    return;
  }
  
  // Paketleme kontrolleri
  const kalanPaket = firmaVeri.mevcutUtu - firmaVeri.mevcutPaket; // Sadece ütüsü yapılanlar paketlenebilir
  if (paketMiktar > kalanPaket) {
    alert(`❌ HATA: ${firmaVeri.ad} için ${kalanPaket} parça paketleme kaldı, ${paketMiktar} parça giremezsiniz!\n(Paketleme için önce ütü yapılmalı)`);
    return;
  }
  
  // Negatif sayı kontrolü
  if (utuMiktar < 0 || paketMiktar < 0) {
    alert('❌ HATA: Negatif sayı giremezsiniz!');
    return;
  }
  
  // Başarı mesajını göster
  document.getElementById('basariMesaji').style.display = 'block';
  
  // Özet kartını göster
  document.getElementById('ozetKart').style.display = 'block';
  document.getElementById('toplamUtu').textContent = (utuMiktar || 0) + ' parça';
  document.getElementById('toplamPaket').textContent = (paketMiktar || 0) + ' parça';
  
  // Formu temizle
  this.reset();
  document.getElementById('ilerlemeDurumu').style.display = 'none';
  
  // 3 saniye sonra mesajları gizle
  setTimeout(() => {
    document.getElementById('basariMesaji').style.display = 'none';
    document.getElementById('ozetKart').style.display = 'none';
  }, 5000);
});

// Sadece sayı girişine izin ver ve gerçek zamanlı kontrol
document.getElementById('utuMiktar').addEventListener('input', function() {
  this.value = this.value.replace(/[^0-9]/g, '');
  
  const firma = document.getElementById('firmaSecim').value;
  if (firma) {
    const firmaVeri = firmaVerileri[firma];
    const kalanUtu = firmaVeri.toplamParca - firmaVeri.mevcutUtu;
    const girilenDeger = parseInt(this.value);
    
    if (girilenDeger > kalanUtu) {
      this.style.borderColor = '#dc3545';
      this.style.backgroundColor = '#ffe6e6';
    } else {
      this.style.borderColor = '#28a745';
      this.style.backgroundColor = '#e6ffe6';
    }
  }
});

document.getElementById('paketMiktar').addEventListener('input', function() {
  this.value = this.value.replace(/[^0-9]/g, '');
  
  const firma = document.getElementById('firmaSecim').value;
  if (firma) {
    const firmaVeri = firmaVerileri[firma];
    const kalanPaket = firmaVeri.mevcutUtu - firmaVeri.mevcutPaket;
    const girilenDeger = parseInt(this.value);
    
    if (girilenDeger > kalanPaket) {
      this.style.borderColor = '#dc3545';
      this.style.backgroundColor = '#ffe6e6';
    } else {
      this.style.borderColor = '#28a745';
      this.style.backgroundColor = '#e6ffe6';
    }
  }
});
</script>

</body>
</html>