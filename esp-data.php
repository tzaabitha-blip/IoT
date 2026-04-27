<?php
include 'koneksi.php';
include 'includes/sidebar.php';
include 'deepseek_api.php';

// Ambil data terbaru untuk kartu
$sql_latest = "SELECT * FROM SensorData ORDER BY id DESC LIMIT 1";
$res_latest = $conn->query($sql_latest);
$data = $res_latest->fetch_assoc();

// Cek apakah perlu rekomendasi baru (setiap 1 jam atau saat ada permintaan)
$forceRefresh = isset($_GET['refresh_ai']) && $_GET['refresh_ai'] == '1';
$lastRecommendation = getLatestRecommendation();
$needNewRecommendation = true;

if ($lastRecommendation && !$forceRefresh) {
    $lastTime = strtotime($lastRecommendation['created_at']);
    $currentTime = time();
    $diffHours = ($currentTime - $lastTime) / 3600;
    
    // Jika rekomendasi kurang dari 1 jam, gunakan yang lama
    if ($diffHours < 1) {
        $needNewRecommendation = false;
        $aiRecommendation = $lastRecommendation['recommendation'];
    }
}

// Dapatkan rekomendasi AI baru jika diperlukan
$aiError = null;
if ($needNewRecommendation && $data) {
    $aiResult = getDeepSeekRecommendation($data);
    
    if ($aiResult['success']) {
        $aiRecommendation = $aiResult['content'];
        // Simpan ke database
        saveRecommendation($data['id'], $aiRecommendation);
    } else {
        $aiError = $aiResult['error'];
        // Fallback ke rekomendasi manual
        $aiRecommendation = null;
    }
}

// Fungsi untuk menentukan status (tetap sama seperti sebelumnya)
function getStatus($value, $type) {
    if ($type == 'tanah') {
        if ($value >= 60 && $value <= 80) return ['normal', 'Kelembaban Tanah Ideal'];
        if ($value < 60) return ['warning', 'Kelembaban Tanah Rendah, Segera Siram'];
        return ['critical', 'Kelembaban Tanah Berlebih'];
    } elseif ($type == 'udara') {
        if ($value >= 50 && $value <= 70) return ['normal', 'Kelembaban Udara Optimal'];
        if ($value < 50) return ['warning', 'Udara Terlalu Kering'];
        return ['warning', 'Kelembaban Udara Tinggi'];
    } elseif ($type == 'suhu') {
        if ($value >= 20 && $value <= 28) return ['normal', 'Suhu Ideal untuk Sawi'];
        if ($value < 20) return ['warning', 'Suhu Terlalu Dingin'];
        return ['warning', 'Suhu Terlalu Panas'];
    }
    return ['normal', 'Normal'];
}

$statusTanah = getStatus($data['kelTanah'] ?? 0, 'tanah');
$statusUdara = getStatus($data['kelUdara'] ?? 0, 'udara');
$statusSuhu = getStatus($data['suhuUdara'] ?? 0, 'suhu');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Sawi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        .main-content { margin-left: 260px; padding: 40px; }
        
        .header { margin-bottom: 30px; }
        .header h1 { font-size: 28px; font-weight: 700; color: #064e3b; margin: 0; }
        .header p { color: #64748b; margin-top: 5px; }

        /* GRID KARTU */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card h3 { font-size: 14px; color: #64748b; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card .value { font-size: 28px; font-weight: 700; color: #10b981; margin: 12px 0 0 0; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 500; margin-top: 10px; }
        .status-normal { background: #dcfce7; color: #166534; }
        .status-warning { background: #fed7aa; color: #9a3412; }
        .status-critical { background: #fee2e2; color: #991b1b; }

        /* CHARTS CONTAINER */
        .charts-container { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px; }
        .chart-card { background: white; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; }
        .chart-card h3 { font-size: 16px; margin-bottom: 15px; color: #1e293b; }

        /* TABEL */
        .table-container {
            background: white;
            margin-top: 35px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .table-header { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .table-header h2 { font-size: 18px; margin: 0; color: #1e293b; }

        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; text-align: left; padding: 15px 25px; font-size: 13px; color: #64748b; font-weight: 600; }
        td { padding: 15px 25px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; }
        tr:last-child td { border: none; }
        tr:hover { background: #f8fafc; }
        
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            background: #dcfce7;
            color: #166534;
        }

        /* REKOMENDASI BOX dengan AI */
        .recommendation-box {
            background: linear-gradient(135deg, #064e3b 0%, #022c22 100%);
            border-radius: 16px;
            padding: 25px;
            color: white;
            margin-top: 30px;
        }
        .recommendation-box h3 { 
            font-size: 18px; 
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .recommendation-list { list-style: none; }
        .recommendation-list li { padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 10px; }
        .recommendation-list li:last-child { border: none; }
        
        .ai-badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            margin-left: 10px;
        }
        
        .refresh-ai {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        .refresh-ai:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .ai-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            border-radius: 12px;
            padding: 15px;
            margin-top: 15px;
        }
        
        /* Style untuk konten AI */
        .ai-analysis {
            line-height: 1.6;
        }
        .ai-analysis ul {
            margin: 10px 0 10px 20px;
        }
        .ai-analysis li {
            margin: 8px 0;
        }
        .ai-analysis p {
            margin: 10px 0;
        }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 20px; }
            .charts-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <h1>🌱 Ringkasan Kebun Sawi</h1>
        <p>Pantau kondisi real-time tanaman sawi Anda secara otomatis.</p>
    </div>

    <!-- KARTU DATA REAL-TIME -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Kelembapan Tanah</h3>
            <p class="value"><?= $data['kelTanah'] ?? '0' ?> <span style="font-size: 16px;">%</span></p>
            <span class="status-badge status-<?= $statusTanah[0] ?>"><?= $statusTanah[1] ?></span>
        </div>
        <div class="stat-card">
            <h3>Kelembapan Udara</h3>
            <p class="value"><?= $data['kelUdara'] ?? '0' ?> <span style="font-size: 16px;">%</span></p>
            <span class="status-badge status-<?= $statusUdara[0] ?>"><?= $statusUdara[1] ?></span>
        </div>
        <div class="stat-card">
            <h3>Suhu Lingkungan</h3>
            <p class="value"><?= $data['suhuUdara'] ?? '0' ?> <span style="font-size: 16px;">°C</span></p>
            <span class="status-badge status-<?= $statusSuhu[0] ?>"><?= $statusSuhu[1] ?></span>
        </div>
        <div class="stat-card">
            <h3>Intensitas Cahaya</h3>
            <p class="value"><?= $data['kecerahan'] ?? '0' ?> <span style="font-size: 16px;">Lux</span></p>
            <span class="status-badge status-normal">Cahaya Terekam</span>
        </div>
    </div>

    <!-- GRAFIK TREN -->
    <div class="charts-container">
        <div class="chart-card">
            <h3>📈 Tren Kelembaban Tanah (7 Hari Terakhir)</h3>
            <canvas id="tanahChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>🌡️ Tren Suhu & Kelembaban Udara</h3>
            <canvas id="suhuUdaraChart"></canvas>
        </div>
    </div>

    <!-- REKOMENDASI PERAWATAN DENGAN DEEPSEEK AI -->
    <div class="recommendation-box">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <h3>
                🤖 Rekomendasi Perawatan oleh AI (DeepSeek)
                <span class="ai-badge">Powered by DeepSeek</span>
            </h3>
            <a href="?refresh_ai=1" class="refresh-ai" onclick="showLoading()">🔄 Refresh Rekomendasi</a>
        </div>
        
        <?php if (isset($aiRecommendation) && $aiRecommendation): ?>
            <div class="ai-analysis">
                <?= $aiRecommendation ?>
            </div>
            <div style="margin-top: 15px; font-size: 11px; opacity: 0.6; text-align: right;">
                ✨ Rekomendasi diperbarui: <?= date('H:i:s') ?>
            </div>
        <?php elseif ($aiError): ?>
            <div class="ai-error">
                <p>⚠️ Gagal menghubungi DeepSeek API: <?= htmlspecialchars($aiError) ?></p>
                <p style="font-size: 12px; margin-top: 10px;">Menampilkan rekomendasi standar...</p>
            </div>
            <!-- FALLBACK: Rekomendasi manual jika API error -->
            <ul class="recommendation-list">
                <?php
                $tanah_val = $data['kelTanah'] ?? 0;
                $suhu_val = $data['suhuUdara'] ?? 0;
                $cahaya_val = $data['kecerahan'] ?? 0;
                
                if ($tanah_val < 60) echo "<li>💧 <strong>Segera lakukan penyiraman</strong> - Kelembaban tanah rendah ($tanah_val%)</li>";
                elseif ($tanah_val > 80) echo "<li>⚠️ <strong>Hentikan penyiraman sementara</strong> - Kelembaban tanah terlalu tinggi</li>";
                else echo "<li>✅ <strong>Kelembaban tanah optimal</strong> - Lanjutkan pola penyiraman saat ini</li>";
                
                if ($suhu_val > 30) echo "<li>🌞 <strong>Berikan naungan tambahan</strong> - Suhu terlalu panas untuk sawi</li>";
                elseif ($suhu_val < 18) echo "<li>❄️ <strong>Lindungi tanaman dari suhu dingin</strong> - Sawi sensitif terhadap suhu rendah</li>";
                
                if ($cahaya_val < 5000) echo "<li>💡 <strong>Tambah pencahayaan</strong> - Intensitas cahaya terlalu rendah</li>";
                
                echo "<li>📅 <strong>Jadwal pemupukan</strong> - Berikan pupuk organik setiap 7-10 hari sekali</li>";
                echo "<li>🐛 <strong>Inspeksi hama</strong> - Periksa secara rutin tanda-tanda ulat atau kutu daun</li>";
                ?>
            </ul>
        <?php else: ?>
            <div class="ai-error">
                <p>⏳ Memuat rekomendasi AI...</p>
                <p style="font-size: 12px; margin-top: 10px;">Silahkan refresh halaman untuk mendapatkan rekomendasi.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- TABEL RIWAYAT SENSOR -->
    <div class="table-container">
        <div class="table-header">
            <h2>📡 Riwayat Log Sensor</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>WAKTU</th>
                    <th>TANAH</th>
                    <th>UDARA</th>
                    <th>SUHU</th>
                    <th>CAHAYA</th>
                    <th>KOORDINAT</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql_all = "SELECT * FROM SensorData ORDER BY id DESC LIMIT 15";
                $result = $conn->query($sql_all);
                while($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><strong><?= date('H:i', strtotime($row['reading_time'])) ?></strong> <span style="color:#94a3b8; font-size:11px;"><?= date('d M', strtotime($row['reading_time'])) ?></span></td>
                    <td><span class="badge"><?= $row['kelTanah'] ?>%</span></td>
                    <td><?= $row['kelUdara'] ?>%</td>
                    <td><?= $row['suhuUdara'] ?>°C</td>
                    <td><?= $row['kecerahan'] ?></td>
                    <td style="font-family: monospace; color: #64748b; font-size: 12px;">
                        <?= $row['latitude'] ?>, <?= $row['longitude'] ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Chart Kelembaban Tanah
const ctxTanah = document.getElementById('tanahChart').getContext('2d');
<?php
$sql_chart = "SELECT DATE_FORMAT(reading_time, '%d/%m %H:%i') as waktu, kelTanah FROM SensorData ORDER BY id DESC LIMIT 7";
$result_chart = $conn->query($sql_chart);
$waktu = [];
$tanah = [];
while($row = $result_chart->fetch_assoc()) {
    array_unshift($waktu, $row['waktu']);
    array_unshift($tanah, $row['kelTanah']);
}
?>
new Chart(ctxTanah, {
    type: 'line',
    data: {
        labels: <?= json_encode($waktu) ?>,
        datasets: [{
            label: 'Kelembaban Tanah (%)',
            data: <?= json_encode($tanah) ?>,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } }
    }
});

// Chart Suhu & Kelembaban Udara
const ctxSuhu = document.getElementById('suhuUdaraChart').getContext('2d');
<?php
$sql_chart2 = "SELECT DATE_FORMAT(reading_time, '%d/%m %H:%i') as waktu, suhuUdara, kelUdara FROM SensorData ORDER BY id DESC LIMIT 7";
$result_chart2 = $conn->query($sql_chart2);
$waktu2 = [];
$suhu = [];
$udara = [];
while($row = $result_chart2->fetch_assoc()) {
    array_unshift($waktu2, $row['waktu']);
    array_unshift($suhu, $row['suhuUdara']);
    array_unshift($udara, $row['kelUdara']);
}
?>
new Chart(ctxSuhu, {
    type: 'line',
    data: {
        labels: <?= json_encode($waktu2) ?>,
        datasets: [
            {
                label: 'Suhu (°C)',
                data: <?= json_encode($suhu) ?>,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4,
                yAxisID: 'y'
            },
            {
                label: 'Kelembaban Udara (%)',
                data: <?= json_encode($udara) ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: { title: { display: true, text: 'Suhu (°C)' } },
            y1: { position: 'right', title: { display: true, text: 'Kelembaban (%)' }, grid: { drawOnChartArea: false } }
        }
    }
});

function showLoading() {
    const refreshBtn = document.querySelector('.refresh-ai');
    if (refreshBtn) {
        refreshBtn.textContent = '⏳ Memuat...';
        refreshBtn.style.opacity = '0.5';
        refreshBtn.style.pointerEvents = 'none';
    }
}
</script>

</body>
</html>