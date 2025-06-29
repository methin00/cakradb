<?php
require_once 'php/config.php';
require_once 'php/auth.php';
checkLogin();

$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-t');
$status = isset($_GET['status']) ? $_GET['status'] : '';
$priority = isset($_GET['priority']) ? $_GET['priority'] : '';

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
    <div class="page-header">
        <h1>📊 Raporlar & Analizler</h1>
        <p>İş süreçlerinizi analiz edin ve performansınızı takip edin</p>
    </div>

    <div class="filter-section">
        <form method="GET">
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Başlangıç Tarihi</label>
                    <input type="date" class="form-control" name="startDate" value="<?php echo $startDate; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bitiş Tarihi</label>
                    <input type="date" class="form-control" name="endDate" value="<?php echo $endDate; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Durum</label>
                    <select class="form-select" id="statusFilter" name="status">
                        <option value="" <?php echo $status == '' ? 'selected' : ''; ?>>Tüm Durumlar</option>
                        <option value="beklemede" <?php echo $status == 'beklemede' ? 'selected' : ''; ?>>Devam Ediyor</option>
                        <option value="tamamlandi" <?php echo $status == 'tamamlandi' ? 'selected' : ''; ?>>Tamamlandı</option>
                        <option value="gecikti" <?php echo $status == 'gecikti' ? 'selected' : ''; ?>>Gecikmiş</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Öncelik</label>
                    <select class="form-select" id="priorityFilter" name="priority">
                        <option value="" <?php echo $priority == '' ? 'selected' : ''; ?>>Tüm Öncelikler</option>
                        <option value="normal" <?php echo $priority == 'normal' ? 'selected' : ''; ?>>Normal</option>
                        <option value="yuksek" <?php echo $priority == 'yuksek' ? 'selected' : ''; ?>>Yüksek</option>
                        <option value="acil" <?php echo $priority == 'acil' ? 'selected' : ''; ?>>Acil</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12 text-end">
                    <button type="submit" class="btn btn-custom">
                        🔍 Filtreleri Uygula
                    </button>
                    <button type="button" class="btn btn-outline-secondary ms-2" onclick="resetFilters()">
                        🔄 Temizle
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-number"><?php echo $toplamIs; ?></div>
            <div class="stat-label">Toplam İş</div>
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
            <div class="stat-icon">🧮</div>
            <div class="stat-number"><?php echo number_format($toplamParca, 0, ',', '.'); ?></div>
            <div class="stat-label">Toplam Parça Sayısı</div>
        </div>
    </div>

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

    <div class="table-section">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">📋 Detaylı İş Listesi</h5>
            <div class="export-buttons">
                <button class="btn btn-export" onclick="exportToCSV()">
                    📄 CSV İndir
                </button>
                <button class="btn btn-export" onclick="printReport()">
                    🖨️ Yazdır
                </button>
            </div>
        </div>

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
                                    'beklemede' => 'Devam Ediyor',
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

<script>
    const chartData = <?php echo json_encode($raporVerileri); ?>;

    const durumlar = {
        beklemede: 0,
        tamamlandi: 0,
        gecikti: 0
    };

    chartData.forEach(item => {
        if (durumlar.hasOwnProperty(item.durum)) {
            durumlar[item.durum]++;
        }
    });

    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Devam Ediyor', 'Tamamlandı', 'Gecikmiş'],
            datasets: [{
                data: [durumlar.beklemede, durumlar.tamamlandi, durumlar.gecikti],
                backgroundColor: ['#17a2b8', '#28a745', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Durum Dağılımı'
                }
            }
        }
    });

    const raporVerileri = <?php echo json_encode($raporVerileri); ?>;
    const monthlyCounts = {};

    raporVerileri.forEach(item => {
        const kayitTarihi = new Date(item.kayit_tarihi);
        const ay = String(kayitTarihi.getMonth() + 1).inStart(2, '0');
        const yil = kayitTarihi.getFullYear();
        const label = `${ay}. ${yil}`;

        if (!monthly Counts[label]) {
            monthlyCounts[label] = 0;
        }
        monthlyCounts[label]++;
    });

    const labels29 = Object.keys(monthlyCounts).sort((a, b) => {
        const [aMonth, aYear] = a.split('.');
        const [bMonth, bYear] = b.split('.');
        return new Date(`${aYear}-${aMonth}-01`) - new Date(`${bYear}-${bMonth}-01`);
    });

    const values = labels.map(label => monthlyCounts[label]);
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');

    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'İş Sayısı',
                data: values,
                backgroundColor: 'rgba(102, 126, 234, 0.6)',
                borderColor: 'rgb(102, 126, 234)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Aylık İş Dağılımı'
                },
                legend: {
                    display: false
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

    function resetFilters() {
        window.location.href = window.location.pathname;
    }

    function exportToCSV() {
        let table = document.querySelector(".table");
        let rows = Array.from(table.querySelectorAll("tr"));
        let csv = [];

        rows.forEach(row => {
            let cols = Array.from(row.querySelectorAll("td, th")).map(cell => {
                return `"${cell.innerText.replace(/"/g, '""')}"`;
            });
            csv.push(cols.join(","));
        });

        let csvContent = csv.join("\n");
        let blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
        let link = document.createElement("a");
        link.setAttribute("href", URL.createObjectURL(blob));
        link.setAttribute("download", "rapor.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function printReport() {
        let content = document.querySelector(".table-section").innerHTML;
        let printWindow = window.open("", "", "height=700,width=900");
        printWindow.document.write(`
            <html><head><title>Rapor Yazdır</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
            </head><body>
            <div class="container mt-4">${content}</div>
            </body></html>`);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }
</script>

</body>
</html>
