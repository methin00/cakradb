<?php
require_once 'php/config.php';
require_once 'php/auth.php';
checkLogin();

// Filtreleme parametreleri
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-t');
$status = isset($_GET['status']) ? $_GET['status'] : '';
$priority = isset($_GET['priority']) ? $_GET['priority'] : '';

// Rapor verilerini çekme
try {
    $sql = "SELECT i.*, f.firma_adi FROM isler i JOIN firmalar f ON i.firma_id = f.id WHERE 1=1";
    $params = [];
    
    if(!empty($startDate) && !empty($endDate)) {
        $sql .= " AND i.kayit_tarihi BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate . ' 23:59:59';
    }
    
    if(!empty($status)) {
        $sql .= " AND i.durum = ?";
        $params[] = $status;
    }
    
    if(!empty($priority)) {
        $sql .= " AND i.oncelik = ?";
        $params[] = $priority;
    }
    
    $sql .= " ORDER BY i.teslim_tarihi ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $raporVerileri = $stmt->fetchAll();
    
    // İstatistikler
    $toplamIs = count($raporVerileri);
    $tamamlanan = 0;
    $geciken = 0;
    $toplamParca = 0;
    
    foreach($raporVerileri as $is) {
        $toplamParca += $is['toplam_parca'];
        if($is['durum'] == 'tamamlandi') $tamamlanan++;
        if($is['durum'] == 'gecikti') $geciken++;
    }
    
} catch(PDOException $e) {
    die("Rapor verileri çekilirken hata: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raporlar - ÇAKRA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
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

        .page-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #6c757d;
            font-size: 18px;
            margin: 0;
        }

        .filter-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: slideUp 0.7s ease-out;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            animation: slideUp 0.8s ease-out;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .stat-card.warning::before {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }

        .stat-card.danger::before {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
        }

        .stat-card.info::before {
            background: linear-gradient(135deg, #17a2b8, #6f42c1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 16px;
            color: #6c757d;
            font-weight: 500;
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }

        .chart-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            height: 400px;
            min-width: 450px;
            overflow: hidden;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: slideUp 0.9s ease-out;
        }

        .chart-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 25px;
            text-align: center;
        }

        .table-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: slideUp 1s ease-out;
        }

        .form-control, .form-select {
            border-radius: 15px;
            border: 2px solid #e0e6ed;
            padding: 12px 20px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f8f9ff;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: white;
        }

        .btn-custom {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 15px;
            padding: 12px 25px;
            font-size: 14px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-export {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 15px;
            padding: 12px 25px;
            font-size: 14px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.4);
            color: white;
        }

        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
            margin-top: 20px;
        }

        .table {
            margin: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, #f8f9ff, #e9ecef);
            border: none;
            font-weight: 600;
            color: #2c3e50;
            padding: 20px 15px;
        }

        .table tbody td {
            padding: 15px;
            border-top: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .badge-status {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-beklemede {
            background: #fff3cd;
            color: #856404;
        }

        .badge-devam {
            background: #cff4fc;
            color: #055160;
        }

        .badge-tamamlandi {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-gecikti {
            background: #f8d7da;
            color: #721c24;
        }

        .priority-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }

        .priority-normal {
            background: #d1e7dd;
            color: #0f5132;
        }

        .priority-yuksek {
            background: #fff3cd;
            color: #664d03;
        }

        .priority-acil {
            background: #f8d7da;
            color: #721c24;
        }

        .loading {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }

        .export-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 20px auto;
                padding: 0 15px;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }

            .chart-section {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .stat-number {
                font-size: 2.5rem;
            }

            .export-buttons {
                justify-content: center;
            }
        }

        .flatpickr-input {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='%236c757d' d='M6 1a1 1 0 0 1 2 0v1h2V1a1 1 0 1 1 2 0v1h1.5A1.5 1.5 0 0 1 15 3.5v10A1.5 1.5 0 0 1 13.5 15h-11A1.5 1.5 0 0 1 1 13.5v-10A1.5 1.5 0 0 1 2.5 2H4V1a1 1 0 0 1 2 0v1h0zm-5 4v8.5A.5.5 0 0 0 1.5 14h11a.5.5 0 0 0 .5-.5V5H1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            padding-right: 45px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin-panel.php">← Ana Sayfa | ÇAKRA</a>
    </div>
</nav>

<div class="main-container">
    <!-- Sayfa Başlığı -->
    <div class="page-header">
        <h1>📊 Raporlar & Analizler</h1>
        <p>İş süreçlerinizi analiz edin ve performansınızı takip edin</p>
    </div>

    <!-- Filtre Bölümü -->
        <div class="filter-section">
            <form method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" name="startDate" value="<?php echo $startDate; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Bitiş Tarihi</label>
                <input type="text" class="form-control flatpickr-input" id="endDate" placeholder="Tarih seçin" readonly>
            </div>
            <div class="col-md-3">
                <label class="form-label">Durum</label>
                <select class="form-select" id="statusFilter">
                    <option value="">Tüm Durumlar</option>
                                        <option value="devam">Devam Ediyor</option>
                    <option value="tamamlandi">Tamamlandı</option>
                    <option value="gecikti">Gecikmiş</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Öncelik</label>
                <select class="form-select" id="priorityFilter">
                    <option value="">Tüm Öncelikler</option>
                    <option value="normal">Normal</option>
                    <option value="yuksek">Yüksek</option>
                    <option value="acil">Acil</option>
                </select>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12 text-end">
                <button class="btn btn-custom" onclick="applyFilters()">
                    🔍 Filtreleri Uygula
                </button>
                <button class="btn btn-outline-secondary ms-2" onclick="resetFilters()">
                    🔄 Temizle
                </button>
            </div>
        </div>
    </div>

    <!-- İstatistik Kartları -->
     <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-number"><?php echo $toplamIs; ?></div>
      <div class="stat-label">Toplam İş</div>
    </div>
        <div class="stat-card warning">
            <div class="stat-icon">⏳</div>
            <div class="stat-number" id="pendingJobs">0</div>
            <div class="stat-label">Bekleyen İşler</div>
        </div>
        <div class="stat-card info">
            <div class="stat-icon">🔄</div>
            <div class="stat-number" id="activeJobs">0</div>
            <div class="stat-label">Aktif İşler</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-number" id="completedJobs">0</div>
            <div class="stat-label">Tamamlanan</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-icon">⚠️</div>
            <div class="stat-number" id="overdueJobs">0</div>
            <div class="stat-label">Geciken İşler</div>
        </div>
        <div class="stat-card info">
            <div class="stat-icon">📦</div>
            <div class="stat-number" id="totalPieces">0</div>
            <div class="stat-label">Toplam Parça</div>
        </div>
    </div>

    <!-- Grafik Bölümü -->
    <div class="chart-section">
        <div class="chart-card">
            <h5 class="chart-title">📈 Aylık İş Dağılımı</h5>
            <canvas id="monthlyChart" style="width: 100%; height: 100%;"></canvas>
        </div>
        <div class="chart-card">
            <h5 class="chart-title">🥧 Durum Dağılımı</h5>
            <canvas id="statusChart" style="width: 100%; height: 100%; max-height: 300px;"></canvas>
        </div>
    </div>

    <!-- Tablo Bölümü -->
    <div class="table-section">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">📋 Detaylı İş Listesi</h5>
            <div class="export-buttons">
                <button class="btn btn-export" onclick="exportToCSV()">
                    📄 CSV İndir
                </button>
                <button class="btn btn-export" onclick="exportToPDF()">
                    📑 PDF İndir
                </button>
                <button class="btn btn-export" onclick="printReport()">
                    🖨️ Yazdır
                </button>
            </div>
        </div>

        <div class="table-section">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>İş ID</th>
          <th>Firma Adı</th>
          <th>Parça Sayısı</th>
          <th>Teslim Tarihi</th>
          <th>Durum</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($raporVerileri as $is): ?>
        <tr>
          <td><?php echo $is['is_kodu']; ?></td>
          <td><?php echo $is['firma_adi']; ?></td>
          <td><?php echo number_format($is['toplam_parca'], 0, ',', '.'); ?></td>
          <td><?php echo date('d.m.Y', strtotime($is['teslim_tarihi'])); ?></td>
          <td>
            <span class="badge-status badge-<?php echo $is['durum']; ?>">
              <?php 
                $durumlar = [
                  'beklemede' => 'Beklemede',
                  'devam' => 'Devam Ediyor',
                  'tamamlandi' => 'Tamamlandı',
                  'gecikti' => 'Gecikmiş'
                ];
                echo $durumlar[$is['durum']]; 
              ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
let allJobs = [];
let filteredJobs = [];
let monthlyChart, statusChart;

// Örnek veri oluşturma
function generateSampleData() {
    const companies = ['Moda Tekstil A.Ş.', 'Başak Konfeksiyon', 'Elit Giyim Ltd.', 'Özkan Tekstil', 'Güler Moda'];
    const statuses = ['devam', 'tamamlandi', 'gecikti'];
    const priorities = ['normal', 'yuksek', 'acil'];
    
    allJobs = [];
    
    for (let i = 1; i <= 50; i++) {
        const randomDate = new Date();
        randomDate.setDate(randomDate.getDate() - Math.floor(Math.random() * 90));
        
        const deliveryDate = new Date();
        deliveryDate.setDate(deliveryDate.getDate() + Math.floor(Math.random() * 30));
        
        const job = {
            id: `JOB-${String(i).padStart(3, '0')}`,
            firma: companies[Math.floor(Math.random() * companies.length)],
            parcaSayisi: Math.floor(Math.random() * 1000) + 50,
            teslimTarihi: deliveryDate.toLocaleDateString('tr-TR'),
            oncelik: priorities[Math.floor(Math.random() * priorities.length)],
            durum: statuses[Math.floor(Math.random() * statuses.length)],
            kayitTarihi: randomDate.toLocaleDateString('tr-TR'),
            rawTeslimTarihi: deliveryDate,
            rawKayitTarihi: randomDate
        };
        
        allJobs.push(job);
    }
    
    filteredJobs = [...allJobs];
}

// Tarih seçicileri başlatma
function initializeDatePickers() {
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 30);

    flatpickr("#startDate", {
        dateFormat: "d.m.Y",
        locale: "tr",
        maxDate: "today",
        defaultDate: thirtyDaysAgo,
        onChange: function() {
            applyFilters();
        }
    });

    flatpickr("#endDate", {
        dateFormat: "d.m.Y",
        locale: "tr",
        maxDate: "today",
        defaultDate: today,
        onChange: function() {
            applyFilters();
        }
    });
}

// İstatistikleri güncelleme
function updateStatistics() {
    const total = filteredJobs.length;
    const pending = filteredJobs.filter(job => job.durum === 'beklemede').length;
    const active = filteredJobs.filter(job => job.durum === 'devam').length;
    const completed = filteredJobs.filter(job => job.durum === 'tamamlandi').length;
    const overdue = filteredJobs.filter(job => job.durum === 'gecikti').length;
    const totalPieces = filteredJobs.reduce((sum, job) => sum + job.parcaSayisi, 0);

    document.getElementById('totalJobs').textContent = total;
    document.getElementById('pendingJobs').textContent = pending;
    document.getElementById('activeJobs').textContent = active;
    document.getElementById('completedJobs').textContent = completed;
    document.getElementById('overdueJobs').textContent = overdue;
    document.getElementById('totalPieces').textContent = totalPieces.toLocaleString('tr-TR');

    // Animasyonlu sayı artışı
    animateNumbers();
}

// Sayı animasyonu
function animateNumbers() {
    const counters = document.querySelectorAll('.stat-number');
    counters.forEach(counter => {
        const target = parseInt(counter.textContent.replace(/\./g, ''));
        let current = 0;
        const increment = target / 30;
        
        const updateCounter = () => {
            if (current < target) {
                current += increment;
                counter.textContent = Math.ceil(current).toLocaleString('tr-TR');
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target.toLocaleString('tr-TR');
            }
        };
        
        updateCounter();
    });
}


// Aylık veri hesaplama
function getMonthlyData() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    // Tarih aralığını belirle
    const start = startDate ? new Date(startDate.split('.').reverse().join('-')) : null;
    const end = endDate ? new Date(endDate.split('.').reverse().join('-')) : new Date();
    
    // Aylar için dinamik etiketler oluştur
    const months = [];
    const monthlyCount = [];
    
    // Tarih aralığına göre ayları hesapla
    if (start && end) {
        let currentMonth = new Date(start);
        while (currentMonth <= end) {
            const monthYear = `${currentMonth.getMonth() + 1}.${currentMonth.getFullYear()}`;
            months.push(monthYear);
            monthlyCount.push(0);
            currentMonth.setMonth(currentMonth.getMonth() + 1);
        }
    } else {
        // Varsayılan olarak son 6 ayı göster
        const today = new Date();
        for (let i = 5; i >= 0; i--) {
            const month = new Date(today.getFullYear(), today.getMonth() - i, 1);
            const monthYear = `${month.getMonth() + 1}.${month.getFullYear()}`;
            months.push(monthYear);
            monthlyCount.push(0);
        }
    }
    
    // Filtrelenmiş işleri say
    filteredJobs.forEach(job => {
        const jobDate = job.rawKayitTarihi;
        const monthYear = `${jobDate.getMonth() + 1}.${jobDate.getFullYear()}`;
        const index = months.indexOf(monthYear);
        if (index !== -1) {
            monthlyCount[index]++;
        }
    });
    
    // Türkçe ay isimleri
    const monthNames = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
    const formattedLabels = months.map(monthYear => {
        const [month, year] = monthYear.split('.');
        return `${monthNames[parseInt(month) - 1]} ${year}`;
    });
    
    return {
        labels: formattedLabels,
        data: monthlyCount
    };
}

// Aylık grafik oluşturma
function createMonthlyChart() {
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    
    const monthlyData = getMonthlyData();
    
    if (monthlyChart) {
        monthlyChart.destroy();
    }
    
    monthlyChart = new Chart(ctx, {
        type: 'bar', // Daha net bir görünüm için 'line' yerine 'bar' kullanıldı
        data: {
            labels: monthlyData.labels,
            datasets: [{
                label: 'Toplam İş',
                data: monthlyData.data,
                backgroundColor: 'rgba(102, 126, 234, 0.6)',
                borderColor: 'rgb(102, 126, 234)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                title: {
                    display: true,
                    text: 'Aylık İş Dağılımı'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'İş Sayısı'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Ay'
                    }
                }
            }
        }
    });
}

// Durum verisi hesaplama
function getStatusData() {
    // Durumları sıfırlayın
    const statusCounts = {
        beklemede: 0,
        devam: 0,
        tamamlandi: 0,
        gecikti: 0
    };

    // Filtrelenmiş işleri say
    filteredJobs.forEach(job => {
        if (statusCounts.hasOwnProperty(job.durum)) {
            statusCounts[job.durum]++;
        }
    });

    return {
        devam: statusCounts.devam,
        tamamlandi: statusCounts.tamamlandi,
        gecikti: statusCounts.gecikti
    };
}

// Durum grafik oluşturma
function createStatusChart() {
    const ctx = document.getElementById('statusChart').getContext('2d');
    
    const statusData = getStatusData();
    
    // Eski grafiği temizle
    if (statusChart) {
        statusChart.destroy();
    }
    
    // Verilerin toplamı sıfırsa, grafik yerine mesaj göster
    const totalData = statusData.beklemede + statusData.devam + statusData.tamamlandi + statusData.gecikti;
    if (totalData === 0) {
        document.getElementById('statusChart').style.display = 'none';
        const chartCard = document.getElementById('statusChart').parentElement;
        let noDataMessage = chartCard.querySelector('.no-data-message');
        if (!noDataMessage) {
            noDataMessage = document.createElement('p');
            noDataMessage.className = 'no-data-message text-muted text-center';
            noDataMessage.textContent = 'Veri bulunamadı.';
            chartCard.appendChild(noDataMessage);
        }
        return;
    } else {
        document.getElementById('statusChart').style.display = 'block';
        const noDataMessage = document.querySelector('.no-data-message');
        if (noDataMessage) {
            noDataMessage.remove();
        }
    }
    
    statusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Devam Ediyor', 'Tamamlandı', 'Gecikmiş'],
            datasets: [{
                data: [
                    statusData.devam,
                    statusData.tamamlandi,
                    statusData.gecikti
                ],
                backgroundColor: [
                    '#17a2b8', // Devam Ediyor
                    '#28a745', // Tamamlandı
                    '#dc3545'  // Gecikmiş
                ],
                borderColor: [
                    '#138496',
                    '#218838',
                    '#c82333'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        font: {
                            size: 12
                        }
                    }
                },
                title: {
                    display: true,
                    text: 'Durum Dağılımı',
                    font: {
                        size: 14,
                        weight: '600'
                    },
                    padding: {
                        top: 10,
                        bottom: 20
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}
// Tablo güncelleme
function updateTable() {
    filteredJobs.sort((a, b) => b.rawKayitTarihi - a.rawKayitTarihi);
    const tbody = document.getElementById('jobsTableBody');
    
    if (filteredJobs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-5">
                    <div class="text-muted">
                        <h5>📋 Veri Bulunamadı</h5>
                        <p>Seçilen kriterlere uygun iş bulunamadı.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    const rows = filteredJobs.map(job => `
        <tr>
            <td><strong>${job.id}</strong></td>
            <td>${job.firma}</td>
            <td>${job.parcaSayisi.toLocaleString('tr-TR')}</td>
            <td>${job.teslimTarihi}</td>
            <td><span class="priority-badge priority-${job.oncelik}">${getPriorityText(job.oncelik)}</span></td>
            <td><span class="badge-status badge-${job.durum}">${getStatusText(job.durum)}</span></td>
            <td>${job.kayitTarihi}</td>
        </tr>
    `).join('');
    
    tbody.innerHTML = rows;
}

// Öncelik metni
function getPriorityText(priority) {
    const texts = {
        normal: '🟢 Normal',
        yuksek: '🟡 Yüksek',
        acil: '🔴 Acil'
    };
    return texts[priority] || priority;
}

// Durum metni
function getStatusText(status) {
    const texts = {
        beklemede: 'Beklemede',
        devam: 'Devam Ediyor',
        tamamlandi: 'Tamamlandı',
        gecikti: 'Gecikmiş'
    };
    return texts[status] || status;
}

// Filtreleri uygulama
function applyFilters() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const status = document.getElementById('statusFilter').value;
    const priority = document.getElementById('priorityFilter').value;
    
    filteredJobs = allJobs.filter(job => {
        let matches = true;
        
        if (startDate) {
            const start = new Date(startDate.split('.').reverse().join('-'));
            if (job.rawKayitTarihi < start) matches = false;
        }
        
        if (endDate) {
            const end = new Date(endDate.split('.').reverse().join('-'));
            if (job.rawKayitTarihi > end) matches = false;
        }
        
        if (status && job.durum !== status) matches = false;
        if (priority && job.oncelik !== priority) matches = false;
        
        return matches;
    });
    
    updateStatistics();
    updateTable();
    createMonthlyChart();
    createStatusChart();
}

// Filtreleri temizleme
function resetFilters() {
    document.getElementById('startDate').value = '';
    document.getElementById('endDate').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('priorityFilter').value = '';
    
    filteredJobs = [...allJobs];
    updateStatistics();
    updateTable();
    createMonthlyChart();
    createStatusChart();
}

// Export fonksiyonları
function exportToCSV() {
    let csv = 'İş ID,Firma Adı,Parça Sayısı,Teslim Tarihi,Öncelik,Durum,Kayıt Tarihi\n';
    
    filteredJobs.forEach(job => {
        csv += `${job.id},"${job.firma}",${job.parcaSayisi},${job.teslimTarihi},${getPriorityText(job.oncelik)},"${getStatusText(job.durum)}",${job.kayitTarihi}\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `rapor_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function exportToPDF() {
    // PDF export için basit bir çözüm
    window.print();
}

function printReport() {
    window.print();
}

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    generateSampleData();
    initializeDatePickers();
    updateStatistics();
    updateTable();
    createMonthlyChart();
    createStatusChart();
    
    // Otomatik güncelleme (30 saniyede bir)
    setInterval(() => {
        // Gerçek uygulamada burada API'den veri çekilir
        console.log('Veri güncelleme kontrolü...');
    }, 30000);
});

// Responsive chart güncelleme
window.addEventListener('resize', function() {
    // Debounce ile resize eventi optimize edildi
    clearTimeout(window.resizeTimeout);
    window.resizeTimeout = setTimeout(() => {
        if (monthlyChart && monthlyChart.canvas) {
            monthlyChart.resize();
        }
        if (statusChart && statusChart.canvas) {
            statusChart.resize();
        }
    }, 250);
});
</script>

</body>
</html>