<?php
include 'koneksi.php';
include 'includes/sidebar.php';

// Ambil data kelembaban
$sql_air = "SELECT * FROM SensorData ORDER BY id DESC LIMIT 50";
$result_air = $conn->query($sql_air);

$rata_tanah = $conn->query("SELECT AVG(kelTanah) as avg FROM SensorData WHERE reading_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc();
$rata_udara = $conn->query("SELECT AVG(kelUdara) as avg FROM SensorData WHERE reading_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc();

// Ambil status pompa dari database
$sql_pump = "SELECT pump_status, control_mode FROM pump_control WHERE id = 1";
$pump_result = $conn->query($sql_pump);
$pump_data = $pump_result->fetch_assoc();

// Jika tabel belum ada, buat data default
if (!$pump_data) {
    $pump_data = ['pump_status' => 0, 'control_mode' => 'auto'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Air - Smart Sawi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        .main-content { margin-left: 260px; padding: 40px; }
        
        .header { margin-bottom: 30px; }
        .header h1 { font-size: 28px; font-weight: 700; color: #064e3b; margin: 0; }
        .header p { color: #64748b; margin-top: 5px; }

        .water-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .water-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .water-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: #10b981;
        }
        .water-value { font-size: 48px; font-weight: 700; color: #10b981; margin: 10px 0; }
        .water-label { font-size: 14px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
        
        .water-meter {
            width: 150px;
            height: 150px;
            margin: 20px auto;
            position: relative;
        }
        .meter-fill {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
            border-radius: 0 0 75px 75px;
            transition: height 0.5s ease;
        }
        
        .schedule-box {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 16px;
            padding: 25px;
            color: white;
            margin-top: 30px;
        }
        .schedule-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        
        /* Pump Status Card */
        .pump-status-card {
            background: linear-gradient(135deg, #064e3b 0%, #022c22 100%);
            border-radius: 16px;
            padding: 25px;
            color: white;
            margin-bottom: 30px;
        }
        .pump-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .pump-header h3 {
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .pump-status {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .pump-badge {
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
        }
        .pump-badge.on {
            background: #ef4444;
            color: white;
        }
        .pump-badge.off {
            background: #22c55e;
            color: white;
        }
        .mode-badge {
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
            background: #3b82f6;
            color: white;
        }
        .mode-badge.manual {
            background: #f59e0b;
        }
        .btn-control {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-control:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Quick Control Buttons */
        .quick-control {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            justify-content: center;
        }
        .quick-btn {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            padding: 10px 20px;
            border-radius: 30px;
            color: white;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        .quick-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .quick-btn.on {
            background: #ef4444;
        }
        .quick-btn.off {
            background: #22c55e;
        }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 20px; }
            .water-stats { grid-template-columns: 1fr; }
            .pump-header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <h1>💧 Monitoring Kelembaban & Air</h1>
        <p>Pantau kebutuhan air tanaman sawi secara real-time</p>
    </div>

    <!-- STATUS POMPA -->
    <div class="pump-status-card">
        <div class="pump-header">
            <h3>🚰 STATUS POMPA AIR</h3>
            <div class="pump-status">
                <span class="pump-badge <?= ($pump_data['pump_status'] ?? 0) ? 'on' : 'off' ?>" id="pumpBadge">
                    <?= ($pump_data['pump_status'] ?? 0) ? '🔴 MENYALA' : '🟢 MATI' ?>
                </span>
                <span class="mode-badge <?= ($pump_data['control_mode'] ?? 'auto') == 'manual' ? 'manual' : '' ?>" id="modeBadge">
                    <?= ($pump_data['control_mode'] ?? 'auto') == 'auto' ? '🤖 Mode Otomatis' : '👆 Mode Manual' ?>
                </span>
                <a href="pump_control.php" class="btn-control">⚙️ Kontrol Penuh →</a>
            </div>
        </div>
        
        <!-- QUICK CONTROL -->
        <div class="quick-control">
            <button class="quick-btn" onclick="setMode('auto')">🤖 Mode Otomatis</button>
            <button class="quick-btn" onclick="setMode('manual')">👆 Mode Manual</button>
            <button class="quick-btn on" onclick="setManual(1)">🔴 Nyalakan Pompa</button>
            <button class="quick-btn off" onclick="setManual(0)">🟢 Matikan Pompa</button>
        </div>
        
        <div style="font-size: 13px; opacity: 0.8; margin-top: 15px;">
            💡 Pompa akan menyala otomatis jika kelembaban tanah di bawah batas yang ditentukan.
            <br>Klik tombol di atas untuk kontrol cepat.
        </div>
    </div>

    <div class="water-stats">
        <div class="water-card">
            <div class="water-label">💧 Kelembaban Tanah (Rata-rata 24 Jam)</div>
            <div class="water-value"><?= round($rata_tanah['avg'] ?? 0) ?>%</div>
            <div class="water-meter">
                <div style="position: relative; width: 100%; height: 100%; background: #e2e8f0; border-radius: 75px;">
                    <div class="meter-fill" style="height: <?= ($rata_tanah['avg'] ?? 0) ?>%; border-radius: 0 0 75px 75px;"></div>
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; font-size: 20px;">
                        <?= round($rata_tanah['avg'] ?? 0) ?>%
                    </div>
                </div>
            </div>
            <div style="margin-top: 15px;">
                <?php 
                $tanah_val = $rata_tanah['avg'] ?? 0;
                if ($tanah_val < 50) echo '<span style="color: #ef4444;">⚠️ Kritis - Segera siram!</span>';
                elseif ($tanah_val < 65) echo '<span style="color: #f59e0b;">⚡ Rendah - Perlu penyiraman</span>';
                elseif ($tanah_val <= 80) echo '<span style="color: #10b981;">✅ Optimal</span>';
                else echo '<span style="color: #3b82f6;">💧 Berlebih - Hentikan penyiraman</span>';
                ?>
            </div>
        </div>
        
        <div class="water-card">
            <div class="water-label">🌫️ Kelembaban Udara (Rata-rata 24 Jam)</div>
            <div class="water-value"><?= round($rata_udara['avg'] ?? 0) ?>%</div>
            <div class="water-meter">
                <div style="position: relative; width: 100%; height: 100%; background: #e2e8f0; border-radius: 75px;">
                    <div class="meter-fill" style="height: <?= ($rata_udara['avg'] ?? 0) ?>%; background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);"></div>
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; font-size: 20px;">
                        <?= round($rata_udara['avg'] ?? 0) ?>%
                    </div>
                </div>
            </div>
        </div>
        
        <div class="water-card">
            <div class="water-label">💦 Estimasi Kebutuhan Air Harian</div>
            <div class="water-value">
                <?php 
                $kebutuhan = match(true) {
                    ($tanah_val < 50) => '2.5',
                    ($tanah_val < 65) => '2.0',
                    ($tanah_val <= 80) => '1.5',
                    default => '1.0'
                };
                echo $kebutuhan . ' L';
                ?>
            </div>
            <div>per meter persegi</div>
            <div style="margin-top: 15px; font-size: 13px;">
                💡 Rekomendasi: <?= $tanah_val < 65 ? 'Tambah frekuensi penyiraman' : ($tanah_val > 80 ? 'Kurangi frekuensi penyiraman' : 'Pertahankan pola saat ini') ?>
            </div>
        </div>
    </div>

    <div class="schedule-box">
        <h3>📅 Jadwal Penyiraman Rekomendasi</h3>
        <div class="schedule-item">
            <span>🌅 Pagi (06:00 - 08:00):</span>
            <span><?= $tanah_val < 70 ? '2 liter/m²' : '1 liter/m²' ?></span>
        </div>
        <div class="schedule-item">
            <span>🌇 Sore (16:00 - 18:00):</span>
            <span><?= $tanah_val < 60 ? '2 liter/m²' : ($tanah_val > 80 ? '0 liter/m²' : '1 liter/m²') ?></span>
        </div>
        <div class="schedule-item">
            <span>💡 Tips:</span>
            <span>Hindari penyiraman saat tengah hari untuk mengurangi penguapan</span>
        </div>
        <div class="schedule-item">
            <span>🚿 Metode Terbaik:</span>
            <span>Irigasi tetes atau sprinkler di pagi hari</span>
        </div>
        <div class="schedule-item">
            <span>📊 Status Pompa:</span>
            <span id="scheduleStatus" style="<?= ($pump_data['pump_status'] ?? 0) ? 'color: #ef4444;' : 'color: #4ade80;' ?>">
                <?= ($pump_data['pump_status'] ?? 0) ? '🔴 Sedang Menyiram' : '🟢 Istirahat' ?>
            </span>
        </div>
    </div>
</div>

<script>
// Fungsi untuk set mode (Auto/Manual)
function setMode(mode) {
    fetch('pump_control_save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'control_mode=' + mode + '&manual_status=0&soil_threshold=40'
    })
    .then(response => response.text())
    .then(() => {
        console.log('Mode diubah ke: ' + mode);
        updatePumpStatus();
    })
    .catch(error => console.log('Error:', error));
}

// Fungsi untuk set manual (Nyalakan/Matikan pompa)
function setManual(status) {
    fetch('pump_control_save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'control_mode=manual&manual_status=' + status + '&soil_threshold=40'
    })
    .then(response => response.text())
    .then(() => {
        console.log('Manual status diubah ke: ' + (status ? 'ON' : 'OFF'));
        updatePumpStatus();
    })
    .catch(error => console.log('Error:', error));
}

// Fungsi untuk update status dari database
function updatePumpStatus() {
    fetch('get_pump_status.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Update badge status
                const pumpBadge = document.getElementById('pumpBadge');
                if (data.pump_status == 1) {
                    pumpBadge.className = 'pump-badge on';
                    pumpBadge.innerHTML = '🔴 MENYALA';
                } else {
                    pumpBadge.className = 'pump-badge off';
                    pumpBadge.innerHTML = '🟢 MATI';
                }
                
                // Update mode badge
                const modeBadge = document.getElementById('modeBadge');
                if (data.control_mode === 'manual') {
                    modeBadge.className = 'mode-badge manual';
                    modeBadge.innerHTML = '👆 Mode Manual';
                } else {
                    modeBadge.className = 'mode-badge';
                    modeBadge.innerHTML = '🤖 Mode Otomatis';
                }
                
                // Update status di schedule box
                const scheduleStatus = document.getElementById('scheduleStatus');
                if (data.pump_status == 1) {
                    scheduleStatus.innerHTML = '🔴 Sedang Menyiram';
                    scheduleStatus.style.color = '#ef4444';
                } else {
                    scheduleStatus.innerHTML = '🟢 Istirahat';
                    scheduleStatus.style.color = '#4ade80';
                }
            }
        })
        .catch(error => console.log('Error:', error));
}

// Update setiap 3 detik
setInterval(updatePumpStatus, 3000);
updatePumpStatus();
</script>

</body>
</html>