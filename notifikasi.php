<?php
include 'koneksi.php';
include 'includes/sidebar.php';

// Ambil data sensor untuk pengecekan notifikasi
$sql_sensor = "SELECT * FROM SensorData ORDER BY id DESC LIMIT 20";
$result_sensor = $conn->query($sql_sensor);

// Generate notifikasi berdasarkan data
$notifications = [];
$critical_count = 0;
$warning_count = 0;

while($row = $result_sensor->fetch_assoc()) {
    $tanah = $row['kelTanah'];
    $suhu = $row['suhuUdara'];
    $udara = $row['kelUdara'];
    $cahaya = $row['kecerahan'];
    $waktu = $row['reading_time'];
    
    // Cek kelembaban tanah
    if ($tanah < 50) {
        $notifications[] = [
            'type' => 'critical',
            'message' => "⚠️ Kelembaban tanah kritis! (" . round($tanah) . "%) - Segera siram tanaman!",
            'time' => $waktu,
            'action' => 'Siram Sekarang'
        ];
        $critical_count++;
    } elseif ($tanah < 60) {
        $notifications[] = [
            'type' => 'warning',
            'message' => "⚡ Kelembaban tanah rendah (" . round($tanah) . "%) - Perlu perhatian",
            'time' => $waktu,
            'action' => 'Cek Tanah'
        ];
        $warning_count++;
    }
    
    // Cek kelembaban tanah terlalu tinggi
    if ($tanah > 85) {
        $notifications[] = [
            'type' => 'warning',
            'message' => "💧 Kelembaban tanah terlalu tinggi (" . round($tanah) . "%) - Hentikan penyiraman",
            'time' => $waktu,
            'action' => 'Cek Drainase'
        ];
        $warning_count++;
    }
    
    // Cek suhu
    if ($suhu > 32) {
        $notifications[] = [
            'type' => 'critical',
            'message' => "🔥 Suhu lingkungan sangat tinggi (" . round($suhu) . "°C) - Tanaman terancam layu!",
            'time' => $waktu,
            'action' => 'Berikan Naungan'
        ];
        $critical_count++;
    } elseif ($suhu > 30) {
        $notifications[] = [
            'type' => 'warning',
            'message' => "🌡️ Suhu tinggi (" . round($suhu) . "°C) - Pertumbuhan bisa terganggu",
            'time' => $waktu,
            'action' => 'Sirkulasi Udara'
        ];
        $warning_count++;
    }
    
    // Cek suhu terlalu dingin
    if ($suhu < 18) {
        $notifications[] = [
            'type' => 'warning',
            'message' => "❄️ Suhu terlalu dingin (" . round($suhu) . "°C) - Sawi sensitif terhadap suhu rendah",
            'time' => $waktu,
            'action' => 'Lindungi Tanaman'
        ];
        $warning_count++;
    }
    
    // Cek kelembaban udara terlalu kering
    if ($udara < 40) {
        $notifications[] = [
            'type' => 'warning',
            'message' => "💨 Kelembaban udara rendah (" . round($udara) . "%) - Penguapan meningkat",
            'time' => $waktu,
            'action' => 'Semprot Air'
        ];
        $warning_count++;
    }
    
    // Cek kelembaban udara terlalu tinggi
    if ($udara > 80) {
        $notifications[] = [
            'type' => 'warning',
            'message' => "💧 Kelembaban udara tinggi (" . round($udara) . "%) - Risiko jamur meningkat",
            'time' => $waktu,
            'action' => 'Cek Ventilasi'
        ];
        $warning_count++;
    }
    
    // Cek intensitas cahaya
    if ($cahaya < 3000) {
        $notifications[] = [
            'type' => 'warning',
            'message' => "☀️ Intensitas cahaya rendah (" . round($cahaya) . " Lux) - Fotosintesis terganggu",
            'time' => $waktu,
            'action' => 'Tambah Pencahayaan'
        ];
        $warning_count++;
    }
}

// Batasi notifikasi yang ditampilkan (ambil 30 terbaru)
$notifications = array_slice($notifications, 0, 30);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - Smart Sawi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        .main-content { margin-left: 260px; padding: 40px; }
        
        .header { margin-bottom: 30px; }
        .header h1 { font-size: 28px; font-weight: 700; color: #064e3b; margin: 0; }
        .header p { color: #64748b; margin-top: 5px; }

        .stats-summary { display: flex; gap: 20px; margin-bottom: 30px; }
        .stat-summary-card {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .stat-summary-card.critical { border-left: 4px solid #ef4444; }
        .stat-summary-card.warning { border-left: 4px solid #f59e0b; }
        .stat-summary-card .count { font-size: 36px; font-weight: 700; }
        .stat-summary-card.critical .count { color: #ef4444; }
        .stat-summary-card.warning .count { color: #f59e0b; }
        .stat-summary-card .label { color: #64748b; margin-top: 5px; }

        .notification-list { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; }
        .notification-header { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
        .notification-header h2 { font-size: 18px; color: #1e293b; }
        
        .notification-item {
            padding: 20px 25px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background 0.2s;
        }
        .notification-item:hover { background: #f8fafc; }
        .notification-item.critical { background: #fef2f2; }
        .notification-item.warning { background: #fffbeb; }
        
        .notification-content { flex: 1; display: flex; align-items: center; gap: 15px; }
        .notification-icon { font-size: 24px; }
        .notification-message { flex: 1; }
        .notification-message strong { display: block; font-size: 15px; margin-bottom: 5px; }
        .notification-message small { color: #94a3b8; font-size: 12px; }
        
        .notification-action button {
            background: #10b981;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .notification-action button:hover { background: #059669; }
        
        .empty-state { text-align: center; padding: 60px; color: #94a3b8; }
        .empty-state span { font-size: 64px; display: block; margin-bottom: 15px; }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 20px; }
            .stats-summary { flex-direction: column; }
            .notification-item { flex-direction: column; gap: 15px; }
            .notification-content { width: 100%; }
            .notification-action { width: 100%; }
            .notification-action button { width: 100%; }
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <h1>🔔 Notifikasi & Peringatan</h1>
        <p>Pantau kondisi tanaman dan terima peringatan dini</p>
    </div>

    <div class="stats-summary">
        <div class="stat-summary-card critical">
            <div class="count"><?= $critical_count ?></div>
            <div class="label">⚠️ Peringatan Kritis</div>
        </div>
        <div class="stat-summary-card warning">
            <div class="count"><?= $warning_count ?></div>
            <div class="label">⚡ Perlu Perhatian</div>
        </div>
        <div class="stat-summary-card">
            <div class="count"><?= count($notifications) ?></div>
            <div class="label">📋 Total Notifikasi</div>
        </div>
    </div>

    <div class="notification-list">
        <div class="notification-header">
            <h2>📢 Daftar Notifikasi Terbaru</h2>
        </div>
        
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <span>✅</span>
                <p>Semua kondisi dalam keadaan baik!</p>
                <small>Tidak ada notifikasi saat ini</small>
            </div>
        <?php else: ?>
            <?php foreach($notifications as $notif): ?>
            <div class="notification-item <?= $notif['type'] ?>">
                <div class="notification-content">
                    <div class="notification-icon">
                        <?= $notif['type'] == 'critical' ? '🚨' : '⚠️' ?>
                    </div>
                    <div class="notification-message">
                        <strong><?= htmlspecialchars($notif['message']) ?></strong>
                        <small><?= date('d M Y H:i:s', strtotime($notif['time'])) ?></small>
                    </div>
                </div>
                <div class="notification-action">
                    <button onclick="handleAction('<?= htmlspecialchars($notif['action']) ?>')"><?= htmlspecialchars($notif['action']) ?></button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function handleAction(action) {
    alert('Aksi: ' + action + '\nFitur ini akan segera terintegrasi dengan sistem kontrol otomatis.');
}
</script>

</body>
</html>