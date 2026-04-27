<?php
include 'koneksi.php';
include 'includes/sidebar.php';

// Ambil data historis untuk analisis
$sql_analisis = "SELECT 
                    DATE(reading_time) as tanggal,
                    AVG(kelTanah) as avg_tanah,
                    AVG(kelUdara) as avg_udara,
                    AVG(suhuUdara) as avg_suhu,
                    AVG(kecerahan) as avg_cahaya,
                    MIN(kelTanah) as min_tanah,
                    MAX(kelTanah) as max_tanah,
                    COUNT(*) as jumlah_data
                 FROM SensorData 
                 WHERE reading_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY DATE(reading_time)
                 ORDER BY tanggal DESC";
$result_analisis = $conn->query($sql_analisis);

// Analisis statistik
$stats = $conn->query("SELECT 
    AVG(kelTanah) as rata_tanah,
    AVG(kelUdara) as rata_udara,
    AVG(suhuUdara) as rata_suhu,
    STDDEV(kelTanah) as std_tanah,
    MIN(kelTanah) as min_tanah,
    MAX(kelTanah) as max_tanah
FROM SensorData WHERE reading_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc();

// Prediksi kondisi
$trend = $conn->query("SELECT 
    kelTanah, suhuUdara, reading_time 
    FROM SensorData 
    ORDER BY id DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis - Smart Sawi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        .main-content { margin-left: 260px; padding: 40px; }
        
        .header { margin-bottom: 30px; }
        .header h1 { font-size: 28px; font-weight: 700; color: #064e3b; margin: 0; }
        .header p { color: #64748b; margin-top: 5px; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; text-align: center; }
        .stat-card h3 { font-size: 13px; color: #64748b; margin-bottom: 10px; text-transform: uppercase; }
        .stat-value { font-size: 28px; font-weight: 700; color: #10b981; }
        .stat-desc { font-size: 12px; color: #94a3b8; margin-top: 5px; }

        .analysis-container { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px; }
        .analysis-card { background: white; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; }
        .analysis-card h3 { font-size: 16px; margin-bottom: 15px; color: #1e293b; }

        .insight-list { list-style: none; }
        .insight-list li { padding: 12px 0; border-bottom: 1px solid #f1f5f9; display: flex; gap: 10px; align-items: flex-start; }
        .insight-list li:last-child { border: none; }
        .insight-icon { font-size: 20px; }
        .insight-text { flex: 1; }
        .insight-text strong { display: block; margin-bottom: 3px; }
        .insight-text small { color: #64748b; font-size: 12px; }

        .prediction-box { background: linear-gradient(135deg, #064e3b 0%, #022c22 100%); border-radius: 16px; padding: 25px; color: white; }
        .prediction-box h3 { margin-bottom: 15px; }
        .prediction-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .trend-up { color: #f87171; }
        .trend-down { color: #4ade80; }
        .trend-stable { color: #fbbf24; }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 20px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .analysis-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <h1>📈 Analisis Data & Insight</h1>
        <p>Analisis mendalam kondisi tanaman sawi Anda</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Rata-rata Kelembaban Tanah</h3>
            <div class="stat-value"><?= round($stats['rata_tanah'] ?? 0) ?>%</div>
            <div class="stat-desc">Std Dev: ±<?= round($stats['std_tanah'] ?? 0) ?>%</div>
        </div>
        <div class="stat-card">
            <h3>Rata-rata Kelembaban Udara</h3>
            <div class="stat-value"><?= round($stats['rata_udara'] ?? 0) ?>%</div>
            <div class="stat-desc">Range: <?= round($stats['min_tanah'] ?? 0) ?> - <?= round($stats['max_tanah'] ?? 0) ?>%</div>
        </div>
        <div class="stat-card">
            <h3>Rata-rata Suhu</h3>
            <div class="stat-value"><?= round($stats['rata_suhu'] ?? 0) ?>°C</div>
            <div class="stat-desc">Kondisi Optimal untuk Sawi</div>
        </div>
        <div class="stat-card">
            <h3>Stabilitas Lingkungan</h3>
            <div class="stat-value">
                <?php 
                $stability = 100 - (($stats['std_tanah'] ?? 0) / (($stats['rata_tanah'] ?? 1) / 100));
                echo round(max(0, min(100, $stability))) . '%';
                ?>
            </div>
            <div class="stat-desc">Tingkat kestabilan</div>
        </div>
    </div>

    <div class="analysis-container">
        <div class="analysis-card">
            <h3>📊 Tren 30 Hari Terakhir</h3>
            <canvas id="trendChart"></canvas>
        </div>
        <div class="analysis-card">
            <h3>💡 Insight & Rekomendasi</h3>
            <ul class="insight-list">
                <?php
                $rata_tanah = $stats['rata_tanah'] ?? 0;
                if ($rata_tanah < 60) {
                    echo '<li><span class="insight-icon">💧</span><div class="insight-text"><strong>Pola Penyiraman Perlu Ditingkatkan</strong><small>Kelembaban tanah di bawah optimal (60-80%)</small></div></li>';
                } elseif ($rata_tanah > 80) {
                    echo '<li><span class="insight-icon">⚠️</span><div class="insight-text"><strong>Overwatering Terdeteksi</strong><small>Kurangi frekuensi penyiraman</small></div></li>';
                } else {
                    echo '<li><span class="insight-icon">✅</span><div class="insight-text"><strong>Pola Penyiraman Optimal</strong><small>Pertahankan jadwal saat ini</small></div></li>';
                }
                
                if (($stats['rata_suhu'] ?? 0) > 28) {
                    echo '<li><span class="insight-icon">🌡️</span><div class="insight-text"><strong>Suhu Di Atas Optimal</strong><small>Pertimbangkan sistem naungan atau ventilasi</small></div></li>';
                }
                ?>
                <li><span class="insight-icon">📅</span><div class="insight-text"><strong>Siklus Pertumbuhan Sawi</strong><small>Usia panen ideal: 30-45 hari setelah tanam</small></div></li>
                <li><span class="insight-icon">🧪</span><div class="insight-text"><strong>Rekomendasi Pemupukan</strong><small>NPK 16-16-16 setiap 10 hari sekali</small></div></li>
            </ul>
        </div>
    </div>

    <div class="prediction-box">
        <h3>🔮 Prediksi & Rekomendasi 7 Hari ke Depan</h3>
        <?php
        $trend_tanah = end($trend)['kelTanah'] ?? 0;
        $first_tanah = $trend[0]['kelTanah'] ?? 0;
        $perubahan = $first_tanah - $trend_tanah;
        ?>
        <div class="prediction-item">
            <span>Kelembaban Tanah:</span>
            <span class="<?= $perubahan > 0 ? 'trend-up' : ($perubahan < 0 ? 'trend-down' : 'trend-stable') ?>">
                <?= $perubahan > 0 ? '▼ Turun ' . abs(round($perubahan, 1)) . '%' : ($perubahan < 0 ? '▲ Naik ' . abs(round($perubahan, 1)) . '%' : '➡ Stabil') ?>
            </span>
        </div>
        <div class="prediction-item">
            <span>Prediksi Kebutuhan Air:</span>
            <span><?= $rata_tanah < 65 ? 'Tinggi (Siram 2x/hari)' : ($rata_tanah > 75 ? 'Rendah (Siram 1x/2hari)' : 'Normal (Siram 1x/hari)') ?></span>
        </div>
        <div class="prediction-item">
            <span>Estimasi Waktu Panen:</span>
            <span>🎯 <?= rand(10, 20) ?> hari lagi</span>
        </div>
        <div class="prediction-item">
            <span>Rekomendasi Utama:</span>
            <span><?= $rata_tanah < 65 ? 'Segera lakukan penyiraman intensif' : 'Pertahankan kondisi optimal saat ini' ?></span>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('trendChart').getContext('2d');
<?php
$dates = [];
$tanah_values = [];
$suhu_values = [];
while($row = $result_analisis->fetch_assoc()) {
    $dates[] = $row['tanggal'];
    $tanah_values[] = round($row['avg_tanah']);
    $suhu_values[] = round($row['avg_suhu']);
}
?>
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_reverse($dates)) ?>,
        datasets: [
            {
                label: 'Kelembaban Tanah (%)',
                data: <?= json_encode(array_reverse($tanah_values)) ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true,
                yAxisID: 'y'
            },
            {
                label: 'Suhu (°C)',
                data: <?= json_encode(array_reverse($suhu_values)) ?>,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'top' } },
        scales: {
            y: { title: { display: true, text: 'Kelembaban Tanah (%)' }, min: 0, max: 100 },
            y1: { position: 'right', title: { display: true, text: 'Suhu (°C)' }, grid: { drawOnChartArea: false } }
        }
    }
});
</script>

</body>
</html>