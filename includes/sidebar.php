<?php
// Mendapatkan nama file saat ini untuk menentukan menu aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<div style="
    width: 260px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    padding: 30px 15px;
    font-family: 'Inter', sans-serif;
    background: linear-gradient(180deg, #064e3b 0%, #022c22 100%); /* Deep Emerald Gradient */
    box-shadow: 10px 0 30px rgba(0,0,0,0.15);
    z-index: 1000;
    color: white;
">

    <div style="padding: 0 15px; margin-bottom: 45px;">
        <div style="
            display: flex; 
            align-items: center; 
            gap: 12px; 
            background: rgba(255, 255, 255, 0.05); 
            padding: 10px 15px; 
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        ">
            <span style="font-size: 24px;">🥬</span>
            <h2 style="font-size: 20px; font-weight: 700; margin: 0; letter-spacing: -0.5px;">
                Smart<span style="color: #4ade80;">Sawi</span>
            </h2>
        </div>
    </div>

    <style>
        .menu-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px 20px;
            color: #94a3b8; /* Slate gray light */
            text-decoration: none;
            border-radius: 14px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 14.5px;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        /* Hover Effect */
        .menu-item:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            transform: translateX(8px);
        }

        /* Active Menu Styling */
        .menu-item.active {
            background: #10b981; /* Emerald 500 */
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }

        /* Dot Indicator for Active Menu */
        .menu-item.active::after {
            content: "";
            position: absolute;
            right: 15px;
            width: 6px;
            height: 6px;
            background: white;
            border-radius: 50%;
        }

        .menu-icon { 
            font-size: 20px; 
            filter: grayscale(1); 
            transition: 0.3s;
        }

        .menu-item.active .menu-icon, 
        .menu-item:hover .menu-icon { 
            filter: grayscale(0); 
        }

        /* Footer Sidebar Info */
        .sidebar-footer {
            position: absolute;
            bottom: 30px;
            left: 15px;
            right: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            text-align: center;
        }
    </style>

    <div class="menu-container">
        <a href="esp-data.php" class="menu-item <?= ($current_page == 'esp-data.php') ? 'active' : '' ?>">
            <span class="menu-icon">📊</span> Dashboard
        </a>

        <a href="notifikasi.php" class="menu-item <?= ($current_page == 'notifikasi.php') ? 'active' : '' ?>">
            <span class="menu-icon">🔔</span> Notifikasi
        </a>

        <a href="analisis.php" class="menu-item <?= ($current_page == 'analisis.php') ? 'active' : '' ?>">
            <span class="menu-icon">📈</span> Analisis
        </a>

        <a href="monitoring-air.php" class="menu-item <?= ($current_page == 'monitoring-air.php') ? 'active' : '' ?>">
            <span class="menu-icon">💧</span> Monitoring Air
        </a>

        <a href="laporan.php" class="menu-item <?= ($current_page == 'laporan.php') ? 'active' : '' ?>">
            <span class="menu-icon">📄</span> Laporan
        </a>
    </div>

    <div class="sidebar-footer">
        <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">System Status</div>
        <div style="font-size: 13px; color: #4ade80; display: flex; align-items: center; justify-content: center; gap: 5px;">
            <span style="width: 8px; height: 8px; background: #4ade80; border-radius: 50%; display: inline-block; animation: pulse 2s infinite;"></span>
            Online
        </div>
    </div>
</div>

<style>
    @keyframes pulse {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.2); }
        100% { opacity: 1; transform: scale(1); }
    }
</style>