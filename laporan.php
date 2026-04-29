<?php
include 'koneksi.php';
include 'includes/sidebar.php';

// Filter periode
$periode = $_GET['periode'] ?? 'minggu';
$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-d', strtotime('-7 days'));
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');

// Export Excel
if (isset($_GET['export_excel'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=laporan_smart_sawi_" . date('Y-m-d') . ".xls");
    
    echo "Waktu\tTanah (%)\tUdara (%)\tSuhu (°C)\tCahaya (%)\tKoordinat\n";
    
    $sql_export = "SELECT * FROM SensorData ORDER BY reading_time DESC";
    $result_export = $conn->query($sql_export);
    while($row = $result_export->fetch_assoc()) {
        echo date('d/m/Y H:i:s', strtotime($row['reading_time'])) . "\t";
        echo $row['kelTanah'] . "\t";
        echo $row['kelUdara'] . "\t";
        echo $row['suhuUdara'] . "\t";
        echo $row['kecerahan'] . "\t";
        echo $row['latitude'] . ", " . $row['longitude'] . "\n";
    }
    exit;
}

switch($periode) {
    case 'hari':
        $sql_filter = "WHERE reading_time >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
        break;
    case 'minggu':
        $sql_filter = "WHERE reading_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'bulan':
        $sql_filter = "WHERE reading_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    default:
        $sql_filter = "WHERE DATE(reading_time) BETWEEN '$tgl_awal' AND '$tgl_akhir'";
}

// Ambil data laporan
$sql_laporan = "SELECT * FROM SensorData $sql_filter ORDER BY reading_time DESC";
$result_laporan = $conn->query($sql_laporan);

// Hitung statistik
$sql_stats = "SELECT 
    AVG(kelTanah) as avg_tanah,
    AVG(kelUdara) as avg_udara,
    AVG(suhuUdara) as avg_suhu,
    AVG(kecerahan) as avg_cahaya,
    MIN(kelTanah) as min_tanah,
    MAX(kelTanah) as max_tanah,
    MIN(suhuUdara) as min_suhu,
    MAX(suhuUdara) as max_suhu,
    COUNT(*) as total_data
FROM SensorData $sql_filter";
$stats = $conn->query($sql_stats)->fetch_assoc();

// Rekomendasi
$rekomendasi = [];
if (($stats['avg_tanah'] ?? 0) < 60) $rekomendasi[] = "🌱 Tingkatkan frekuensi penyiraman (tanah kering)";
if (($stats['avg_tanah'] ?? 0) > 85) $rekomendasi[] = "⚠️ Kurangi frekuensi penyiraman dan periksa drainase";
if (($stats['avg_suhu'] ?? 0) > 30) $rekomendasi[] = "☀️ Berikan naungan tambahan (suhu terlalu panas)";
if (($stats['avg_suhu'] ?? 0) < 20) $rekomendasi[] = "❄️ Lindungi tanaman dari suhu dingin";
if (($stats['avg_cahaya'] ?? 0) < 30) $rekomendasi[] = "💡 Tambah pencahayaan (cahaya kurang optimal)";

$periode_text = match($periode) {
    'hari' => 'Hari Ini',
    'minggu' => '7 Hari Terakhir',
    'bulan' => '30 Hari Terakhir',
    'custom' => date('d/m/Y', strtotime($tgl_awal)) . ' s/d ' . date('d/m/Y', strtotime($tgl_akhir)),
    default => '7 Hari Terakhir'
};
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Smart Sawi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        
        /* === SIDEBAR INTERAKTIF === */
        .main-content {
            margin-left: 260px;
            padding: 40px;
            transition: margin-left 0.3s ease;
        }
        
        /* Header */
        .header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #064e3b;
            margin: 0;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .btn-print {
            background: #6366f1;
        }
        
        .btn-print:hover {
            background: #4f46e5;
        }
        
        .btn-excel {
            background: #22c55e;
        }
        
        .btn-excel:hover {
            background: #16a34a;
        }
        
        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-group label {
            display: block;
            font-size: 12px;
            color: #64748b;
            margin-bottom: 5px;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
        }
        
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            text-align: center;
            transition: all 0.3s;
        }
        
        .summary-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        
        .summary-card h3 {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .summary-value {
            font-size: 28px;
            font-weight: 700;
            color: #10b981;
        }
        
        .summary-sub {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 5px;
        }
        
        .report-table {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            overflow-x: auto;
        }
        
        .report-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .report-header h3 {
            font-size: 18px;
            color: #1e293b;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f1f5f9;
            text-align: left;
            padding: 12px 15px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .badge-tanah {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .recommendation-section {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            border-radius: 12px;
            margin-top: 30px;
        }
        
        .recommendation-section h3 {
            color: #92400e;
            margin-bottom: 10px;
        }
        
        .recommendation-section ul {
            margin-left: 20px;
            color: #78350f;
        }
        
        .recommendation-section li {
            margin: 8px 0;
        }
        
        .info-footer {
            margin-top: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 12px;
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }
        
        .print-header {
            display: none;
        }
        
        .print-footer {
            display: none;
        }
        
        /* === RESPONSIVE MOBILE === */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                padding: 20px;
                padding-top: 80px;
            }
            
            .stats-summary {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .btn-group {
                width: 100%;
                justify-content: flex-start;
            }
        }
        
        /* === PERBAIKAN CETAK PDF === */
        @media print {
            /* Reset semua margin */
            body, html {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            
            /* Hilangkan sidebar */
            .my-sidebar,
            .hamburger,
            .sidebar-overlay {
                display: none !important;
            }
            
            /* Hilangkan tombol dan filter */
            .btn-print,
            .btn-excel,
            .filter-box,
            .no-print,
            .header button,
            .btn-group {
                display: none !important;
            }
            
            /* Konten utama full width */
            .main-content {
                margin-left: 0 !important;
                padding: 10px !important;
                width: 100% !important;
            }
            
            /* Tabel tidak terpotong */
            .report-table {
                overflow-x: visible !important;
                width: 100% !important;
            }
            
            table {
                width: 100% !important;
                table-layout: auto !important;
            }
            
            th, td {
                white-space: normal !important;
                word-break: break-word !important;
                font-size: 10px !important;
                padding: 6px 8px !important;
            }
            
            /* Tampilkan header/footer print */
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 2px solid #064e3b;
            }
            
            .print-header h1 {
                color: #064e3b;
                font-size: 20px;
            }
            
            .print-header .subtitle {
                font-size: 11px;
                color: #64748b;
            }
            
            .print-header .period {
                font-size: 10px;
                background: #f1f5f9;
                padding: 3px 10px;
                border-radius: 15px;
                display: inline-block;
                margin-top: 5px;
            }
            
            .print-footer {
                display: block !important;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                text-align: center;
                font-size: 9px;
                color: #94a3b8;
                padding: 10px;
                border-top: 1px solid #e2e8f0;
                background: white;
            }
            
            /* Hindari potongan halaman */
            .stats-summary,
            .report-table,
            .recommendation-section {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            
            @page {
                size: A4;
                margin: 1cm;
            }
            
            .summary-value {
                font-size: 16px !important;
            }
            
            .summary-card, .report-table, .recommendation-section {
                border: 1px solid #ccc !important;
            }
            
            .header {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<!-- KONTEN UTAMA -->
<div class="main-content">
    
    <!-- HEADER PRINT -->
    <div class="print-header">
        <h1>🌱 SMART SAWI</h1>
        <div class="subtitle">Sistem Monitoring Tanaman Otomatis</div>
        <div class="period">Laporan Periode: <?= $periode_text ?></div>
        <div class="date-printed">Dicetak: <?= date('d/m/Y H:i:s') ?></div>
    </div>
    
    <!-- HEADER WEB -->
    <div class="header">
        <h1>📄 Laporan Monitoring Tanaman</h1>
        <div class="btn-group">
            <button class="btn btn-print no-print" onclick="window.print()">🖨️ Cetak PDF</button>
            <a href="?export_excel=1<?= $periode == 'custom' ? '&periode=custom&tgl_awal=' . $tgl_awal . '&tgl_akhir=' . $tgl_akhir : '&periode=' . $periode ?>" class="btn btn-excel no-print">📊 Export Excel</a>
        </div>
    </div>
    
    <!-- FILTER -->
    <div class="filter-box no-print">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label>Periode Cepat</label>
                <select name="periode" onchange="this.form.submit()">
                    <option value="hari" <?= $periode == 'hari' ? 'selected' : '' ?>>Hari Ini</option>
                    <option value="minggu" <?= $periode == 'minggu' ? 'selected' : '' ?>>7 Hari Terakhir</option>
                    <option value="bulan" <?= $periode == 'bulan' ? 'selected' : '' ?>>30 Hari Terakhir</option>
                    <option value="custom" <?= $periode == 'custom' ? 'selected' : '' ?>>Kustom</option>
                </select>
            </div>
            <?php if($periode == 'custom'): ?>
            <div class="filter-group">
                <label>Tanggal Awal</label>
                <input type="date" name="tgl_awal" value="<?= $tgl_awal ?>">
            </div>
            <div class="filter-group">
                <label>Tanggal Akhir</label>
                <input type="date" name="tgl_akhir" value="<?= $tgl_akhir ?>">
            </div>
            <div class="filter-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn">Filter</button>
            </div>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- STATISTIK -->
    <div class="stats-summary">
        <div class="summary-card">
            <h3>🌱 Rata-rata Kelembaban Tanah</h3>
            <div class="summary-value"><?= round($stats['avg_tanah'] ?? 0) ?>%</div>
            <div class="summary-sub">Min: <?= round($stats['min_tanah'] ?? 0) ?>% | Max: <?= round($stats['max_tanah'] ?? 0) ?>%</div>
        </div>
        <div class="summary-card">
            <h3>💧 Rata-rata Kelembaban Udara</h3>
            <div class="summary-value"><?= round($stats['avg_udara'] ?? 0) ?>%</div>
        </div>
        <div class="summary-card">
            <h3>🌡️ Rata-rata Suhu</h3>
            <div class="summary-value"><?= round($stats['avg_suhu'] ?? 0) ?>°C</div>
            <div class="summary-sub">Min: <?= round($stats['min_suhu'] ?? 0) ?>°C | Max: <?= round($stats['max_suhu'] ?? 0) ?>°C</div>
        </div>
        <div class="summary-card">
            <h3>📊 Total Data Terekam</h3>
            <div class="summary-value"><?= $stats['total_data'] ?? 0 ?></div>
            <div class="summary-sub">point data</div>
        </div>
    </div>
    
    <!-- TABEL -->
    <div class="report-table">
        <div class="report-header">
            <h3>📊 Data Detail Periode Laporan</h3>
            <span class="no-print" style="font-size: 12px; color: #64748b;">Total: <?= $stats['total_data'] ?? 0 ?> record</span>
        </div>
        <div style="overflow-x: auto;">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Waktu</th>
                        <th>Tanah (%)</th>
                        <th>Udara (%)</th>
                        <th>Suhu (°C)</th>
                        <th>Cahaya (%)</th>
                        <th>Koordinat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result_laporan && $result_laporan->num_rows > 0): ?>
                        <?php $no = 1; ?>
                        <?php while($row = $result_laporan->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= date('d/m/Y H:i:s', strtotime($row['reading_time'])) ?></td>
                            <td class="badge-tanah">
                                <?= $row['kelTanah'] ?>
                                <?php if($row['kelTanah'] < 50): ?> 🔴
                                <?php elseif($row['kelTanah'] < 60): ?> 🟡
                                <?php else: ?> 🟢
                                <?php endif; ?>
                            </td>
                            <td><?= $row['kelUdara'] ?></td>
                            <td><?= $row['suhuUdara'] ?>°C</td>
                            <td><?= number_format($row['kecerahan'], 0, ',', '.') ?>%</td>
                            <td style="font-size: 11px; font-family: monospace;"><?= $row['latitude'] ?>, <?= $row['longitude'] ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">Tidak ada data dalam periode ini</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- REKOMENDASI -->
    <?php if(!empty($rekomendasi)): ?>
    <div class="recommendation-section">
        <h3>💡 Rekomendasi Berdasarkan Laporan</h3>
        <ul>
            <?php foreach($rekomendasi as $r): ?>
                <li><?= $r ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- FOOTER -->
    <div class="info-footer no-print">
        📌 Sistem Monitoring Tanaman Sawi Otomatis | Data dikirim dari ESP32 setiap 10 detik
    </div>
    
    <div class="print-footer">
        Smart Sawi - Sistem Monitoring Tanaman Otomatis | Laporan dicetak: <?= date('d/m/Y H:i:s') ?>
    </div>
    
</div>

<script>
document.querySelectorAll('select[name="periode"]').forEach(select => {
    select.addEventListener('change', function() {
        this.form.submit();
    });
});
</script>

</body>
</html>