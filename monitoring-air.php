<?php
include 'koneksi.php';
include 'includes/sidebar.php';

$rata_tanah = $conn->query("SELECT AVG(kelTanah) as avg FROM SensorData WHERE reading_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc();
$rata_udara = $conn->query("SELECT AVG(kelUdara) as avg FROM SensorData WHERE reading_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc();

$sql_pump = "SELECT pump_status, control_mode, soil_threshold FROM pump_control WHERE id = 1";
$pump_result = $conn->query($sql_pump);
$pump_data = $pump_result->fetch_assoc();

if (!$pump_data) {
    $pump_data = ['pump_status' => 0, 'control_mode' => 'auto', 'soil_threshold' => 40];
}

$tanah_val = round($rata_tanah['avg'] ?? 0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Air - Smart Sawi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f4f8; color: #1e293b; }
        
        .main-content {
            margin-left: 260px;
            padding: 40px;
            transition: margin-left 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 0 !important; padding: 20px; padding-top: 80px; }
        }
        
        .header { margin-bottom: 30px; }
        .header h1 { font-size: 28px; font-weight: 800; color: #064e3b; margin: 0; }
        .header p { color: #64748b; margin-top: 5px; }

        .pump-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 28px;
            padding: 28px;
            margin-bottom: 30px;
            box-shadow: 0 20px 35px -10px rgba(0,0,0,0.2);
        }
        
        .pump-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .pump-title { font-size: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        
        .badge-container { display: flex; gap: 12px; flex-wrap: wrap; }
        
        .badge {
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .badge-pump { background: #ef4444; color: white; }
        .badge-pump.on { background: #22c55e; }
        .badge-mode { background: #3b82f6; color: white; }
        .badge-mode.manual { background: #f59e0b; }
        
        .btn-link {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 50px;
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-link:hover { background: rgba(255,255,255,0.2); }
        
        .control-bar {
            background: rgba(255,255,255,0.05);
            border-radius: 60px;
            padding: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .mode-switch {
            display: flex;
            gap: 8px;
            background: rgba(0,0,0,0.3);
            border-radius: 50px;
            padding: 4px;
        }
        
        .mode-btn {
            padding: 10px 28px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            background: transparent;
            color: rgba(255,255,255,0.6);
            border: none;
        }
        .mode-btn.active { background: #10b981; color: white; }
        .mode-btn.auto.active { background: #3b82f6; }
        .mode-btn.manual.active { background: #f59e0b; }
        
        .manual-controls { display: flex; gap: 12px; }
        
        .manual-btn {
            padding: 10px 28px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .manual-btn.on { background: #22c55e; color: white; }
        .manual-btn.off { background: #ef4444; color: white; }
        .manual-btn:hover { transform: scale(1.02); }
        
        .threshold-control {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(0,0,0,0.3);
            padding: 5px 18px;
            border-radius: 50px;
        }
        .threshold-control input {
            width: 65px;
            padding: 6px 10px;
            border-radius: 30px;
            border: none;
            text-align: center;
            font-weight: 600;
            background: #334155;
            color: white;
        }
        .threshold-control button {
            background: #10b981;
            border: none;
            padding: 5px 14px;
            border-radius: 30px;
            color: white;
            cursor: pointer;
        }
        
        .info-text { font-size: 12px; opacity: 0.7; text-align: center; margin-top: 15px; }
        .soil-info { font-size: 11px; opacity: 0.5; text-align: center; margin-top: 8px; }
        
        .water-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 30px; }
        
        .stat-card {
            background: white;
            padding: 28px 20px;
            border-radius: 24px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .stat-icon { font-size: 32px; margin-bottom: 12px; }
        .stat-value { font-size: 44px; font-weight: 800; color: #10b981; margin: 8px 0; }
        .stat-label { font-size: 12px; color: #64748b; text-transform: uppercase; }
        
        .meter {
            width: 140px;
            height: 140px;
            margin: 15px auto;
            position: relative;
            background: #e2e8f0;
            border-radius: 50%;
            overflow: hidden;
        }
        .meter-fill {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
            transition: height 0.3s;
        }
        .meter-value {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-weight: 800;
            font-size: 22px;
            color: #1e293b;
            background: white;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .schedule-box {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 24px;
            padding: 24px;
            color: white;
        }
        .schedule-item {
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .schedule-item:last-child { border: none; }
        
        /* Toast */
        .toast-notif {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #1e293b;
            color: white;
            padding: 12px 20px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);
        }
        .toast-notif.show { opacity: 1; visibility: visible; }
        .toast-notif.success { border-left: 4px solid #22c55e; }
        .toast-notif.error { border-left: 4px solid #ef4444; }
        .toast-notif.warning { border-left: 4px solid #f59e0b; }
        
        @media (max-width: 768px) {
            .water-stats { grid-template-columns: 1fr; }
            .control-bar { flex-direction: column; border-radius: 24px; }
            .toast-notif { bottom: 20px; right: 20px; left: 20px; }
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <h1>💧 Monitoring Kelembaban & Air</h1>
        <p>Pantau kebutuhan air tanaman sawi secara real-time</p>
    </div>

    <div class="pump-card">
        <div class="pump-header">
            <div class="pump-title"><i class="fas fa-water-pump"></i> STATUS POMPA AIR</div>
            <div class="badge-container">
                <span class="badge badge-pump <?= ($pump_data['pump_status'] ?? 0) ? 'on' : '' ?>" id="pumpBadge">
                    <i class="fas <?= ($pump_data['pump_status'] ?? 0) ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <?= ($pump_data['pump_status'] ?? 0) ? ' MENYALA' : ' MATI' ?>
                </span>
                <span class="badge badge-mode <?= ($pump_data['control_mode'] ?? 'auto') == 'manual' ? 'manual' : '' ?>" id="modeBadge">
                    <i class="fas <?= ($pump_data['control_mode'] ?? 'auto') == 'auto' ? 'fa-robot' : 'fa-hand-peace' ?>"></i>
                    <?= ($pump_data['control_mode'] ?? 'auto') == 'auto' ? ' Mode Otomatis' : ' Mode Manual' ?>
                </span>
                <a href="pump_control.php" class="btn-link"><i class="fas fa-sliders-h"></i> Kontrol Penuh →</a>
            </div>
        </div>
        
        <div class="control-bar">
            <div class="mode-switch">
                <button class="mode-btn auto <?= ($pump_data['control_mode'] ?? 'auto') == 'auto' ? 'active' : '' ?>" onclick="setMode('auto')">🤖 Auto</button>
                <button class="mode-btn manual <?= ($pump_data['control_mode'] ?? 'auto') == 'manual' ? 'active' : '' ?>" onclick="setMode('manual')">👆 Manual</button>
            </div>
            <div class="manual-controls">
                <button class="manual-btn on" onclick="checkBeforeManual(1)">🔴 Nyalakan</button>
                <button class="manual-btn off" onclick="checkBeforeManual(0)">🟢 Matikan</button>
            </div>
            <div class="threshold-control">
                <label>🌱 Batas:</label>
                <input type="number" id="thresholdVal" value="<?= $pump_data['soil_threshold'] ?? 40 ?>" min="20" max="80" step="5">
                <button onclick="updateThreshold()">Apply</button>
            </div>
        </div>
        
        <div class="info-text" id="infoText">
            <i class="fas fa-lightbulb"></i> 
            <?= ($pump_data['control_mode'] ?? 'auto') == 'auto' ? 'Mode Otomatis: Pompa menyala jika tanah < batas' : 'Mode Manual: Kontrol dari tombol di atas' ?>
        </div>
        <div class="soil-info">
            <i class="fas fa-seedling"></i> Kelembaban tanah: <strong id="currentSoil"><?= $tanah_val ?></strong>% 
            — <span id="soilStatus"><?= $tanah_val < ($pump_data['soil_threshold'] ?? 40) ? 'Tanah kering' : 'Tanah lembab' ?></span>
        </div>
    </div>

    <div class="water-stats">
        <div class="stat-card">
            <div class="stat-icon">💧</div>
            <div class="stat-label">Kelembaban Tanah</div>
            <div class="stat-value"><?= $tanah_val ?>%</div>
            <div class="meter">
                <div class="meter-fill" style="height: <?= $tanah_val ?>%"></div>
                <div class="meter-value"><?= $tanah_val ?>%</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🌫️</div>
            <div class="stat-label">Kelembaban Udara</div>
            <div class="stat-value"><?= round($rata_udara['avg'] ?? 0) ?>%</div>
            <div class="meter">
                <div class="meter-fill" style="height: <?= round($rata_udara['avg'] ?? 0) ?>%; background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);"></div>
                <div class="meter-value"><?= round($rata_udara['avg'] ?? 0) ?>%</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💦</div>
            <div class="stat-label">Estimasi Air</div>
            <div class="stat-value">
                <?php 
                echo match(true) {
                    ($tanah_val < 50) => '2.5',
                    ($tanah_val < 65) => '2.0',
                    ($tanah_val <= 80) => '1.5',
                    default => '1.0'
                } . 'L';
                ?>
            </div>
            <div style="font-size: 13px; color:#64748b;">per m²/hari</div>
        </div>
    </div>

    <div class="schedule-box">
        <h3>📅 Jadwal Penyiraman</h3>
        <div class="schedule-item"><span>🌅 Pagi</span><span><?= $tanah_val < 70 ? '2 liter/m²' : '1 liter/m²' ?></span></div>
        <div class="schedule-item"><span>🌇 Sore</span><span><?= $tanah_val < 60 ? '2 liter/m²' : ($tanah_val > 80 ? '0 liter/m²' : '1 liter/m²') ?></span></div>
        <div class="schedule-item"><span>📊 Status Pompa</span><span id="scheduleStatus"><?= ($pump_data['pump_status'] ?? 0) ? '🟢 Menyiram' : '🔴 Istirahat' ?></span></div>
    </div>
</div>

<div id="toast" class="toast-notif"></div>

<script>
let currentMode = '<?= $pump_data['control_mode'] ?? 'auto' ?>';
let toastTimer = null;

function showToast(msg, type = 'success') {
    if (toastTimer) clearTimeout(toastTimer);
    const toast = document.getElementById('toast');
    const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
    toast.innerHTML = `<i class="fas ${icon}"></i> ${msg}`;
    toast.className = `toast-notif ${type} show`;
    toastTimer = setTimeout(() => toast.classList.remove('show'), 2000);
}

function updateModeUI(mode) {
    const badge = document.getElementById('modeBadge');
    if (mode === 'manual') {
        badge.className = 'badge badge-mode manual';
        badge.innerHTML = '<i class="fas fa-hand-peace"></i> Mode Manual';
    } else {
        badge.className = 'badge badge-mode';
        badge.innerHTML = '<i class="fas fa-robot"></i> Mode Otomatis';
    }
    
    document.querySelectorAll('.mode-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`.mode-btn.${mode}`)?.classList.add('active');
    document.getElementById('infoText').innerHTML = mode === 'auto' 
        ? '<i class="fas fa-robot"></i> Mode Otomatis: Pompa menyala jika tanah &lt; batas'
        : '<i class="fas fa-hand-peace"></i> Mode Manual: Kontrol dari tombol di atas';
}

function setMode(mode) {
    const threshold = document.getElementById('thresholdVal').value;
    fetch('pump_control_save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `control_mode=${mode}&manual_status=0&soil_threshold=${threshold}`
    }).then(() => {
        currentMode = mode;
        updateModeUI(mode);
        updatePumpStatus();
        showToast(mode === 'auto' ? 'Mode Otomatis aktif' : 'Mode Manual aktif');
    }).catch(() => showToast('Gagal ganti mode', 'error'));
}

function checkBeforeManual(status) {
    if (currentMode === 'auto') {
        showToast('Ups! Mode Otomatis aktif. Ganti ke Mode Manual dulu.', 'warning');
        return;
    }
    setManual(status);
}

function setManual(status) {
    const threshold = document.getElementById('thresholdVal').value;
    fetch('pump_control_save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `control_mode=manual&manual_status=${status}&soil_threshold=${threshold}`
    }).then(() => {
        currentMode = 'manual';
        updateModeUI('manual');
        updatePumpStatus();
        showToast(status ? '💧 Pompa dinyalakan' : '💧 Pompa dimatikan');
    }).catch(() => showToast('Gagal kontrol pompa', 'error'));
}

function updateThreshold() {
    const newVal = document.getElementById('thresholdVal').value;
    fetch('pump_control_save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `control_mode=${currentMode}&manual_status=0&soil_threshold=${newVal}`
    }).then(() => {
        updatePumpStatus();
        showToast(`✅ Batas diubah menjadi ${newVal}%`);
    }).catch(() => showToast('Gagal ubah batas', 'error'));
}

function updatePumpStatus() {
    fetch('get_pump_status.php')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const pumpBadge = document.getElementById('pumpBadge');
                if (data.pump_status == 1) {
                    pumpBadge.className = 'badge badge-pump on';
                    pumpBadge.innerHTML = '<i class="fas fa-check-circle"></i> MENYALA';
                } else {
                    pumpBadge.className = 'badge badge-pump';
                    pumpBadge.innerHTML = '<i class="fas fa-exclamation-circle"></i> MATI';
                }
                
                updateModeUI(data.control_mode);
                currentMode = data.control_mode;
                
                document.getElementById('scheduleStatus').innerHTML = data.pump_status == 1 ? '🟢 Menyiram' : '🔴 Istirahat';
                document.getElementById('currentSoil').innerHTML = Math.round(data.soil_moisture);
                
                const threshold = document.getElementById('thresholdVal').value;
                const soilStatus = document.getElementById('soilStatus');
                if (data.soil_moisture < threshold) {
                    soilStatus.innerHTML = 'Tanah kering, pompa akan menyala';
                    soilStatus.style.color = '#f59e0b';
                } else {
                    soilStatus.innerHTML = 'Tanah cukup lembab';
                    soilStatus.style.color = '#4ade80';
                }
            }
        })
        .catch(err => console.log(err));
}

setInterval(updatePumpStatus, 3000);
updatePumpStatus();
</script>
</body>
</html>