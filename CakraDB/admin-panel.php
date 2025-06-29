<?php
require_once 'php/auth.php';
checkLogin();

try {
    $stmt = $db->prepare("
        SELECT i.*, f.firma_adi, 
               (SELECT SUM(utu_parca) FROM gunluk_uretim WHERE is_id = i.id) AS toplam_utu,
               (SELECT SUM(paket_parca) FROM gunluk_uretim WHERE is_id = i.id) AS toplam_paket
        FROM isler i
        JOIN firmalar f ON i.firma_id = f.id
        ORDER BY 
          CASE 
            WHEN i.durum = 'tamamlandi' THEN 1
            ELSE 0
          END,
          i.oncelik DESC,
          i.teslim_tarihi ASC
    ");
    $stmt->execute();
    $jobs = $stmt->fetchAll();
    
    $total_jobs = count($jobs);
    $completed_jobs = 0;
    $urgent_jobs = 0;
    
    foreach($jobs as $job) {
        if($job['durum'] == 'tamamlandi') $completed_jobs++;
        if($job['oncelik'] == 'acil') $urgent_jobs++;
    }
    
} catch(PDOException $e) {
    die("İşler çekilirken hata: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ÇAKRA Admin Paneli - Hoşgeldiniz, <?php echo $_SESSION['user_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-custom {
            background: linear-gradient(135deg, #2c3e50, #34495e) !important;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: 600;
            color: white !important;
        }

        .main-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .welcome-card h2 {
            color: #2c3e50;
            font-weight: 300;
            margin-bottom: 10px;
        }

        .welcome-card p {
            color: #6c757d;
            font-size: 18px;
        }

        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .action-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            text-decoration: none;
            color: inherit;
        }

        .action-card .icon {
            font-size: 48px;
            margin-bottom: 20px;
            display: block;
        }

        .action-card.primary .icon { color: #667eea; }
        .action-card.warning .icon { color: #ffc107; }
        .action-card.success .icon { color: #28a745; }

        .action-card h4 {
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .action-card p {
            color: #6c757d;
            margin: 0;
        }

        .jobs-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .jobs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .jobs-header h4 {
            color: #2c3e50;
            font-weight: 600;
            margin: 0;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #e0e6ed;
            background: white;
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active,
        .filter-btn:hover {
            background: #667eea;
            border-color: #667eea;
            color: white;
        }

        .job-card {
            background: #f8f9ff;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
        }

        .job-card:hover {
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
            transform: translateX(5px);
        }

        .job-card.completed {
            border-left-color: #28a745;
            background: #f0fff4;
        }

        .job-card.urgent {
            border-left-color: #dc3545;
            background: #fff5f5;
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .job-title {
            flex: 1;
        }

        .job-title h5 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .job-date {
            font-size: 14px;
            color: #6c757d;
        }

        .job-status {
            margin-left: 20px;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.completed {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.in-progress {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.urgent {
            background: #f8d7da;
            color: #721c24;
        }

        .progress-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .progress-item h6 {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .progress-bar-container {
            background: #e9ecef;
            border-radius: 10px;
            height: 8px;
            margin-bottom: 5px;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .progress-bar-fill.primary { background: #667eea; }
        .progress-bar-fill.success { background: #28a745; }
        .progress-bar-fill.warning { background: #ffc107; }

        .progress-text {
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 8px 16px;
            border-radius: 20px;
            border: none;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            text-decoration: none;
        }

        .btn-complete {
            background: #28a745;
            color: white;
        }

        .btn-complete:hover {
            background: #218838;
            color: white;
        }

        .btn-edit {
            background: #17a2b8;
            color: white;
        }

        .btn-edit:hover {
            background: #138496;
            color: white;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            color: white;
        }

        .btn-details {
            background: #6c757d;
            color: white;
        }

        .btn-details:hover {
            background: #5a6268;
            color: white;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-number.primary { color: #667eea; }
        .stat-number.success { color: #28a745; }
        .stat-number.warning { color: #ffc107; }
        .stat-number.danger { color: #dc3545; }

        .stat-label {
            font-size: 14px;
            color: #6c757d;
            text-transform: uppercase;
        }

        @media (max-width: 768px) {
            .job-header {
                flex-direction: column;
                align-items: start;
            }

            .job-status {
                margin-left: 0;
                margin-top: 10px;
            }

            .action-buttons {
                justify-content: start;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">ÇAKRA - Yönetim Paneli</a>
            <div class="d-flex">
                <span class="text-light me-3">Hoşgeldiniz, <?php echo $_SESSION['user_name']; ?></span>
                <a href="?logout=1" class="btn btn-outline-light btn-sm">Çıkış</a>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <div class="welcome-card">
            <h2>🌟 ÇAKRA Yönetim Sistemi</h2>
            <p>İş takibi, üretim yönetimi ve raporlama merkezi</p>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number primary"><?php echo $total_jobs - $completed_jobs; ?></div>
                <div class="stat-label">Aktif İş</div>
            </div>
            <div class="stat-card">
                <div class="stat-number success"><?php echo $completed_jobs; ?></div>
                <div class="stat-label">Tamamlanan</div>
            </div>
            <div class="stat-card">
                <div class="stat-number warning"><?php echo $urgent_jobs; ?></div>
                <div class="stat-label">Acil</div>
            </div>
        </div>

        <div class="action-cards">
            <a href="is-ekle.php" class="action-card primary">
                <span class="icon">➕</span>
                <h4>Yeni İş Ekle</h4>
                <p>Sisteme yeni müşteri işi kaydedin</p>
            </a>
            <a href="gun-sonu.php" class="action-card warning">
                <span class="icon">🕔</span>
                <h4>Gün Sonu Girişi</h4>
                <p>Günlük üretim verilerini kaydedin</p>
            </a>
            <a href="rapor.php" class="action-card success">
                <span class="icon">📊</span>
                <h4>Raporlar</h4>
                <p>Haftalık ve aylık raporları görüntüleyin</p>
            </a>
        </div>

        <div class="jobs-container">
            <div class="jobs-header">
                <h4>📋 İş Takip Listesi</h4>
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="all">Tümü</button>
                    <button class="filter-btn" data-filter="in-progress">Devam Eden</button>
                    <button class="filter-btn" data-filter="completed">Tamamlanan</button>
                    <button class="filter-btn" data-filter="urgent">Acil</button>
                </div>
            </div>

            <?php foreach($jobs as $job): 
                $remaining_days = floor((strtotime($job['teslim_tarihi']) - time()) / (60 * 60 * 24));
                $status_class = '';
                $status_badge = '';
                
                if($job['durum'] == 'tamamlandi') {
                    $status_class = 'completed';
                    $status_badge = '<span class="status-badge completed">Tamamlandı</span>';
                } elseif($job['oncelik'] == 'acil' && $remaining_days <= 3) {
                    $status_class = 'urgent';
                    $status_badge = '<span class="status-badge urgent">Acil</span>';
                } elseif($job['durum'] == 'beklemede') {
                    $status_badge = '<span class="status-badge in-progress">Devam Ediyor</span>';
                }
                
                $total_utu = $job['toplam_utu'] ?? 0;
                $total_paket = $job['toplam_paket'] ?? 0;
                $utu_percent = $job['toplam_parca'] > 0 ? round(($total_utu / $job['toplam_parca']) * 100) : 0;
                $paket_percent = $job['toplam_parca'] > 0 ? round(($total_paket / $job['toplam_parca']) * 100) : 0;
            ?>
            <div class="job-card <?php echo $status_class; ?>" data-category="<?php 
                echo ($job['durum'] == 'beklemede' ? 'in-progress ' : '') . 
                     ($job['durum'] == 'tamamlandi' ? 'completed ' : '') . 
                     ($job['oncelik'] == 'acil' ? 'urgent' : '');
            ?>">
                <div class="job-header">
                    <div class="job-title">
                        <h5><?php echo $job['firma_adi']; ?></h5>
                        <div class="job-date">🗓️ 
                            <?php if($job['durum'] == 'tamamlandi'): ?>
                                Teslim Edildi: <strong><?php echo date('d.m.Y', strtotime($job['tamamlanma_tarihi'])); ?></strong>
                            <?php else: ?>
                                Son Teslim: <strong><?php echo date('d.m.Y', strtotime($job['teslim_tarihi'])); ?></strong> 
                                (<?php echo $remaining_days > 0 ? $remaining_days . ' gün kaldı' : 'Süre doldu'; ?>)
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="job-status">
                        <?php echo $status_badge; ?>
                    </div>
                </div>
                
                <div class="progress-section">
                    <div class="progress-item">
                        <h6>Toplam İş</h6>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill primary" style="width: 100%"></div>
                        </div>
                        <div class="progress-text"><?php echo $job['toplam_parca']; ?> / <?php echo $job['toplam_parca']; ?> parça</div>
                    </div>
                    <div class="progress-item">
                        <h6>Ütü Durumu</h6>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill warning" style="width: <?php echo $utu_percent; ?>%"></div>
                        </div>
                        <div class="progress-text"><?php echo $total_utu; ?> / <?php echo $job['toplam_parca']; ?> parça (%<?php echo $utu_percent; ?>)</div>
                    </div>
                    <div class="progress-item">
                        <h6>Paketleme</h6>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill success" style="width: <?php echo $paket_percent; ?>%"></div>
                        </div>
                        <div class="progress-text"><?php echo $total_paket; ?> / <?php echo $job['toplam_parca']; ?> parça (%<?php echo $paket_percent; ?>)</div>
                    </div>
                </div>

                <div class="action-buttons">
                    <?php if($job['durum'] != 'tamamlandi'): ?>
                    <button class="btn-action btn-complete" onclick="tamamla('<?php echo $job['id']; ?>')">
                        ✅ Tamamlandı İşaretle
                    </button>
                    <?php endif; ?>
                    <a href="#" class="btn-action btn-details"
                       data-bs-toggle="modal" data-bs-target="#isDetayModal"
                       data-id="<?php echo $job['id']; ?>"
                       data-aciklama="<?php echo htmlspecialchars($job['aciklama']); ?>"
                       is-kodu="<?php echo htmlspecialchars($job['is_kodu']); ?>"
                       data-teslim="<?php echo htmlspecialchars(date('d.m.Y', strtotime($job['teslim_tarihi']))); ?>"
                       data-utu="<?php echo $total_utu . ' / ' . $job['toplam_parca']; ?>"
                       data-paket="<?php echo $total_paket . ' / ' . $job['toplam_parca']; ?>"
                       data-oncelik="<?php echo htmlspecialchars($job['oncelik']); ?>"
                    >👁️ Detaylar</a>

                    <button class="btn-action btn-delete" onclick="sil('<?php echo $job['id']; ?>')">
                        🗑️ Sil
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade" id="isDetayModal" tabindex="-1" aria-labelledby="isDetayModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4 border-0">
                <div class="modal-header bg-gradient-primary text-white rounded-top-4">
                    <h5 class="modal-title" id="isDetayModalLabel"><i class="bi bi-info-circle-fill me-2"></i>İş Detayları</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body p-4" style="background: #f8f9fa;">
                    <h6 class="text-primary" id="is-kodu"></h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="text-secondary">Açıklama</h6>
                            <p id="detay-aciklama" class="fs-5 fw-semibold text-dark">-</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-secondary">Son Teslim Tarihi</h6>
                            <p id="detay-teslim" class="fs-5 fw-semibold text-dark">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-secondary">Ütü Durumu</h6>
                            <p id="detay-utu" class="fs-5 fw-semibold text-dark">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-secondary">Paket Durumu</h6>
                            <p id="detay-paket" class="fs-5 fw-semibold text-dark">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-secondary">Öncelik</h6>
                            <p id="detay-oncelik" class="fs-5 fw-semibold text-dark">-</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 justify-content-center">
                    <button type="button" class="btn btn-primary btn-lg rounded-pill px-4" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                const jobCards = document.querySelectorAll('.job-card');
                
                jobCards.forEach(card => {
                    if (filter === 'all') {
                        card.style.display = 'block';
                    } else {
                        const categories = card.getAttribute('data-category');
                        if (categories && categories.includes(filter)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    }
                });
            });
        });

        function tamamla(isId) {
            if (confirm('Bu işi tamamlandı olarak işaretlemek istediğinize emin misiniz?')) {
                fetch('is-tamamla.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'is_id=' + isId
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('✅ İş başarıyla tamamlandı olarak işaretlendi!');
                        location.reload();
                    } else {
                        alert('❌ Hata: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('❌ İşlem sırasında hata oluştu: ' + error);
                });
            }
        }

        function sil(isId) {
            if (confirm('⚠️ Bu işi silmek istediğinize emin misiniz?\nBu işlem geri alınamaz!')) {
                if (confirm('🔴 SON UYARI: İş kalıcı olarak silinecek. Devam etmek istediğinize emin misiniz?')) {
                    fetch('is-sil.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'is_id=' + isId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            alert('🗑️ İş başarıyla silindi!');
                            location.reload();
                        } else {
                            alert('❌ Hata: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('❌ İşlem sırasında hata oluştu: ' + error);
                    });
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            var isDetayModal = document.getElementById('isDetayModal');

            isDetayModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;

                var aciklama = button.getAttribute('data-aciklama');
                var is_kodu = button.getAttribute('is-kodu');
                var teslim = button.getAttribute('data-teslim');
                var utu = button.getAttribute('data-utu');
                var paket = button.getAttribute('data-paket');
                var oncelik = button.getAttribute('data-oncelik');

                isDetayModal.querySelector('#detay-aciklama').textContent = aciklama;
                isDetayModal.querySelector('#is-kodu').textContent = is_kodu;
                isDetayModal.querySelector('#detay-teslim').textContent = teslim;
                isDetayModal.querySelector('#detay-utu').textContent = utu;
                isDetayModal.querySelector('#detay-paket').textContent = paket;
                isDetayModal.querySelector('#detay-oncelik').textContent = oncelik;
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const bugun = new Date();
            
            document.querySelectorAll('.job-date').forEach(dateEl => {
                const tarihText = dateEl.textContent;
                if (tarihText.includes('Son Teslim')) {
                    const gun = parseInt(tarihText.match(/\d+/)[0]);
                    if (gun <= 5) {
                        dateEl.style.color = '#dc3545';
                        dateEl.style.fontWeight = 'bold';
                    }
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-zT3HprLFrr6FVF7syQZbSu+/0f81HZ+wt7frRU8OUCkTxBhe6iTyiJKe6VpGdeLF" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js" integrity="sha384-JHeu8SnNp0FtpHt5Dp0p5h/TaYDn6x45P+5Nv+ojpFdw/5ZhHwJ/T0kkxZGn6Nns" crossorigin="anonymous"></script>
</body>
</html>
