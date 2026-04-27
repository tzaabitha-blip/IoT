<?php
// File: pump_control.php - Halaman kontrol pompa dari web
include 'koneksi.php';
include 'includes/sidebar.php';

// Proses update setting dari form (untuk mode & threshold)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $control_mode   = $_POST['control_mode']   ?? 'auto';
    $manual_status  = isset($_POST['manual_status']) ? 1 : 0;
    $soil_threshold = intval($_POST['soil_threshold'] ?? 40);

    $sql = "UPDATE pump_control SET
            control_mode    = ?,
            manual_status   = ?,
            soil_threshold  = ?
            WHERE id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $control_mode, $manual_status, $soil_threshold);
    $stmt->execute();

    $success = true;
}

// Ambil data kontrol saat ini
$sql    = "SELECT * FROM pump_control WHERE id = 1";
$result = $conn->query($sql);
$control = $result->fetch_assoc();

// Ambil log pompa
$sql_log    = "SELECT * FROM pump_log ORDER BY created_at DESC LIMIT 20";
$result_log = $conn->query($sql_log);

// Ambil data sensor terbaru
$sql_sensor   = "SELECT kelTanah FROM SensorData ORDER BY id DESC LIMIT 1";
$sensor       = $conn->query($sql_sensor)->fetch_assoc();
$soil_moisture = round($sensor['kelTanah'] ?? 0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Pompa - Smart Sawi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        .main-content { margin-left: 260px; padding: 40px; }

        .header { margin-bottom: 30px; }
        .header h1 { font-size: 28px; font-weight: 700; color: #064e3b; margin: 0; }
        .header p { color: #64748b; margin-top: 5px; }

        .pump-status-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            color: white;
            text-align: center;
        }
        .pump-icon { font-size: 80px; margin-bottom: 20px; }
        .pump-status-text { font-size: 32px; font-weight: 700; margin-bottom: 10px; }
        .pump-status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
        }
        .badge-on  { background: #ef4444; color: white; }
        .badge-off { background: #22c55e; color: white; }

        .control-panel {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .control-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            border: 1px solid #e2e8f0;
        }
        .control-card h3 { font-size: 18px; margin-bottom: 20px; color: #1e293b; }

        .mode-toggle { display: flex; gap: 15px; margin-bottom: 25px; }
        .mode-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        .mode-btn.active { background: #10b981; border-color: #10b981; color: white; }
        .mode-btn.auto.active   { background: #3b82f6; border-color: #3b82f6; }
        .mode-btn.manual.active { background: #f59e0b; border-color: #f59e0b; }

        .manual-control { display: flex; gap: 15px; margin-top: 20px; }
        .manual-btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-on  { background: #ef4444; color: white; }
        .btn-off { background: #22c55e; color: white; }
        .btn-on:hover  { background: #dc2626; }
        .btn-off:hover { background: #16a34a; }

        .threshold-slider { width: 100%; margin: 15px 0; }
        .threshold-value { font-size: 24px; font-weight: 700; color: #10b981; }

        .log-table {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .log-header { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; }
        th {
            background: #f1f5f9;
            text-align: left;
            padding: 12px 15px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
        }
        td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }

        .auto-refresh { font-size: 11px; color: #64748b; margin-top: 10px; }

        .loading {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 18px;
        }
        .loading.show { display: flex; }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 20px; }
            .control-panel { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="loading" id="loading">⏳ Menyimpan...</div>

<div class="main-content">
    <div class="header">
        <h1>🚰 Kontrol Pompa Air</h1>
        <p>Kontrol manual/otomatis dan monitoring pompa penyiraman</p>
    </div>

    <?php if(isset($success)): ?>
        <div style="background: #dcfce7; color: #166534; padding: 12px; border-radius: 12px; margin-bottom: 20px;">
            ✅ Pengaturan berhasil disimpan!
        </div>
    <?php endif; ?>

    <!-- Status Pompa Real-time -->
    <div class="pump-status-card">
        <div class="pump-icon">💧</div>
        <div class="pump-status-text">Pompa Air</div>
        <div>
            <span class="pump-status-badge <?= ($control['pump_status'] ?? 0) ? 'badge-on' : 'badge-off' ?>" id="pumpBadge">
                <?= ($control['pump_status'] ?? 0) ? '🔴 MENYALA' : '🟢 MATI' ?>
            </span>
        </div>
        <div class="auto-refresh" id="lastUpdate">Memuat...</div>
    </div>

    <form method="POST" id="controlForm">
        <div class="control-panel">
            <!-- Mode Kontrol -->
            <div class="control-card">
                <h3>🎮 Mode Kontrol</h3>
                <div class="mode-toggle">
                    <button type="button"
                        class="mode-btn auto <?= ($control['control_mode'] ?? 'auto') == 'auto' ? 'active' : '' ?>"
                        onclick="setMode('auto')">
                        🤖 Otomatis
                    </button>
                    <button type="button"
                        class="mode-btn manual <?= ($control['control_mode'] ?? 'auto') == 'manual' ? 'active' : '' ?>"
                        onclick="setMode('manual')">
                        👆 Manual
                    </button>
                </div>
                <input type="hidden" name="control_mode" id="control_mode"
                       value="<?= $control['control_mode'] ?? 'auto' ?>">

                <div id="autoSettings"
                     style="<?= ($control['control_mode'] ?? 'auto') == 'auto' ? '' : 'display:none;' ?>">
                    <p style="margin-bottom: 10px;">🌱 Batas Kelembaban Tanah Nyalakan Pompa:</p>
                    <div style="text-align: center;">
                        <span class="threshold-value" id="thresholdValue">
                            <?= $control['soil_threshold'] ?? 40 ?>
                        </span>%
                    </div>
                    <input type="range" name="soil_threshold" class="threshold-slider"
                           min="20" max="80" step="5"
                           value="<?= $control['soil_threshold'] ?? 40 ?>"
                           oninput="updateThreshold(this.value)">
                    <p style="margin-top: 15px; font-size: 13px; color: #64748b;">
                        📊 Kelembaban tanah saat ini: <strong><?= $soil_moisture ?>%</strong>
                        <?php if ($soil_moisture < ($control['soil_threshold'] ?? 40)): ?>
                            <span style="color: #ef4444;">(Tanah Kering – Pompa akan menyala)</span>
                        <?php else: ?>
                            <span style="color: #10b981;">(Tanah Cukup Lembab)</span>
                        <?php endif; ?>
                    </p>
                </div>

                <div id="manualSettings"
                     style="<?= ($control['control_mode'] ?? 'auto') == 'manual' ? '' : 'display:none;' ?>">
                    <div class="manual-control">
                        <!-- FIX: tombol langsung panggil setManual() -->
                        <button type="button" class="manual-btn btn-on"  onclick="setManual(1)">🔴 NYALAKAN POMPA</button>
                        <button type="button" class="manual-btn btn-off" onclick="setManual(0)">🟢 MATIKAN POMPA</button>
                    </div>
                    <input type="hidden" name="manual_status" id="manual_status"
                           value="<?= $control['manual_status'] ?? 0 ?>">
                </div>
            </div>

            <!-- Informasi Sensor -->
            <div class="control-card">
                <h3>📊 Informasi Sensor Saat Ini</h3>
                <div style="margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                        <span>🌱 Kelembaban Tanah:</span>
                        <strong id="soilDisplay"><?= $soil_moisture ?>%</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                        <span>🎮 Mode Kontrol:</span>
                        <strong id="modeDisplay">
                            <?= ($control['control_mode'] ?? 'auto') == 'auto' ? 'Otomatis' : 'Manual' ?>
                        </strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                        <span>⚙️ Batas Otomatis:</span>
                        <strong><?= $control['soil_threshold'] ?? 40 ?>%</strong>
                    </div>
                </div>
                <div style="background: #f1f5f9; padding: 12px; border-radius: 12px;">
                    <p style="font-size: 13px; margin-bottom: 5px;">💡 <strong>Cara Kerja:</strong></p>
                    <p style="font-size: 12px; color: #64748b;">
                        • Mode Otomatis: Pompa menyala jika tanah &lt; batas yang ditentukan<br>
                        • Mode Manual: Pompa dikontrol langsung dari tombol<br>
                        • Perubahan akan langsung dikirim ke ESP32
                    </p>
                </div>
            </div>
        </div>

        <button type="submit" style="display: none;" id="submitBtn">Simpan</button>
    </form>

    <!-- Log Riwayat Pompa -->
    <div class="log-table">
        <div class="log-header">
            <h3>📋 Riwayat Aktivitas Pompa</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Status Pompa</th>
                    <th>Mode</th>
                    <th>Kelembaban</th>
                    <th>Trigger</th>
                </tr>
            </thead>
            <tbody>
                <?php while($log = $result_log->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                    <td>
                        <?= $log['pump_status']
                            ? '<span style="color:#ef4444;">🔴 Menyala</span>'
                            : '<span style="color:#22c55e;">🟢 Mati</span>' ?>
                    </td>
                    <td><?= $log['control_mode'] == 'auto' ? '🤖 Otomatis' : '👆 Manual' ?></td>
                    <td><?= $log['soil_moisture'] ?>%</td>
                    <td><?= $log['trigger_type'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// =============================================
// FIX: setMode — simpan via pump_control_save.php
// =============================================
function setMode(mode) {
    document.getElementById('control_mode').value = mode;
    document.getElementById('modeDisplay').innerText = mode === 'auto' ? 'Otomatis' : 'Manual';

    document.getElementById('autoSettings').style.display   = mode === 'auto'   ? 'block' : 'none';
    document.getElementById('manualSettings').style.display = mode === 'manual' ? 'block' : 'none';

    document.querySelectorAll('.mode-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector('.mode-btn.' + mode).classList.add('active');

    const threshold = document.querySelector('[name="soil_threshold"]')?.value ?? 40;

    fetch('pump_control_save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'control_mode=' + mode + '&manual_status=0&soil_threshold=' + threshold
    })
    .then(res => res.text())
    .then(res => console.log('setMode result:', res))
    .catch(err => console.error('setMode error:', err));
}

// =============================================
// FIX: setManual — pakai action=set_manual (bukan update_pump)
//      Lanjut simpan control_mode via pump_control_save.php
// =============================================
function setManual(status) {
    document.getElementById('loading').classList.add('show');

    // STEP 1: Set manual_status + paksa control_mode=manual di DB
    fetch('pump_api.php?action=set_manual&api_key=12345abcde&manual_status=' + status)
    .then(response => response.json())
    .then(data => {
        if (data.status !== 'success') throw new Error(data.message ?? 'Gagal set_manual');

        // STEP 2: Update pump_control row juga (threshold tetap)
        const threshold = document.querySelector('[name="soil_threshold"]')?.value ?? 40;
        return fetch('pump_control_save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'control_mode=manual&manual_status=' + status + '&soil_threshold=' + threshold
        });
    })
    .then(() => {
        document.getElementById('loading').classList.remove('show');
        alert(status ? '🔴 Pompa MENYALA' : '🟢 Pompa MATI');
        location.reload();
    })
    .catch(error => {
        document.getElementById('loading').classList.remove('show');
        console.error('setManual error:', error);
        alert('Gagal mengontrol pompa: ' + error.message);
    });
}

// Update threshold display
function updateThreshold(value) {
    document.getElementById('thresholdValue').innerText = value;
}

// Auto refresh status pompa setiap 3 detik
function updatePumpStatus() {
    fetch('get_pump_status.php')
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const pumpBadge = document.getElementById('pumpBadge');
            if (data.pump_status == 1) {
                pumpBadge.className = 'pump-status-badge badge-on';
                pumpBadge.innerHTML = '🔴 MENYALA';
            } else {
                pumpBadge.className = 'pump-status-badge badge-off';
                pumpBadge.innerHTML = '🟢 MATI';
            }
            document.getElementById('lastUpdate').innerHTML =
                'Terakhir update: ' + new Date().toLocaleTimeString();
            if (data.soil_moisture !== undefined) {
                document.getElementById('soilDisplay').innerHTML = data.soil_moisture + '%';
            }
        }
    })
    .catch(error => console.log('updatePumpStatus error:', error));
}

setInterval(updatePumpStatus, 3000);
updatePumpStatus();
</script>

</body>
</html>