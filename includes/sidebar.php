<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    /* === SIDEBAR === */
    .my-sidebar {
        width: 260px;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        padding: 30px 15px;
        font-family: 'Inter', sans-serif;
        background: linear-gradient(180deg, #064e3b 0%, #022c22 100%);
        box-shadow: 10px 0 30px rgba(0,0,0,0.15);
        z-index: 1000;
        color: white;
        transition: transform 0.3s ease;
        overflow-y: auto;
    }
    
    /* Tombol close di dalam sidebar */
    .close-sidebar-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(255,255,255,0.15);
        border: none;
        color: white;
        font-size: 18px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }
    
    .close-sidebar-btn:hover {
        background: rgba(255,255,255,0.3);
        transform: scale(1.1);
    }
    
    /* Tombol hamburger (untuk buka sidebar) */
    .hamburger {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1001;
        background: #064e3b;
        border: none;
        color: white;
        width: 45px;
        height: 45px;
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        transition: all 0.3s;
    }
    
    .hamburger:hover {
        background: #10b981;
        transform: scale(1.05);
    }
    
    /* Overlay */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 999;
    }
    
    /* Menu items */
    .menu-container {
        display: flex;
        flex-direction: column;
        gap: 5px;
        margin-top: 30px;
    }
    
    .menu-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px 18px;
        color: #94a3b8;
        text-decoration: none;
        border-radius: 12px;
        transition: all 0.3s;
        font-size: 14px;
        font-weight: 500;
        position: relative;
    }
    
    .menu-item:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        transform: translateX(5px);
    }
    
    .menu-item.active {
        background: #10b981;
        color: #ffffff;
        font-weight: 600;
        box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
    }
    
    .menu-item.active::before {
        content: "";
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 20px;
        background: white;
        border-radius: 3px;
    }
    
    .menu-icon {
        font-size: 20px;
        width: 28px;
        text-align: center;
    }
    
    /* Logo */
    .logo-wrapper {
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .logo-wrapper:hover {
        transform: scale(1.02);
    }
    
    .logo-inner {
        display: flex;
        align-items: center;
        gap: 12px;
        background: rgba(255, 255, 255, 0.05);
        padding: 12px 15px;
        border-radius: 15px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s;
    }
    
    .logo-wrapper:hover .logo-inner {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.2);
    }
    
    .sidebar-footer {
        position: absolute;
        bottom: 30px;
        left: 15px;
        right: 15px;
        padding: 15px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 12px;
        text-align: center;
    }
    
    @keyframes pulse {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.2); }
        100% { opacity: 1; transform: scale(1); }
    }
    
    /* === DESKTOP: sidebar bisa ditutup === */
    @media (min-width: 769px) {
        .my-sidebar {
            transform: translateX(0);
        }
        
        .my-sidebar.closed {
            transform: translateX(-100%);
        }
        
        /* Saat sidebar tertutup, hamburger muncul */
        .hamburger {
            display: flex;
        }
        
        /* Saat sidebar terbuka, hamburger disembunyikan */
        .my-sidebar:not(.closed) ~ .hamburger {
            display: none;
        }
    }
    
    /* === MOBILE === */
    @media (max-width: 768px) {
        .my-sidebar {
            transform: translateX(-100%);
        }
        
        .my-sidebar.open {
            transform: translateX(0);
        }
        
        .hamburger {
            display: flex;
        }
        
        .sidebar-overlay.show {
            display: block;
        }
    }
</style>

<!-- Tombol hamburger -->
<button class="hamburger" id="hamburgerBtn">☰</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<div class="my-sidebar" id="mySidebar">
    
    <!-- LOGO BISA DIKLIK -->
    <div class="logo-wrapper" id="logoClick">
        <div class="logo-inner">
            <span style="font-size: 24px;">🥬</span>
            <h2 style="font-size: 20px; font-weight: 700; margin: 0;">
                Smart<span style="color: #4ade80;">Sawi</span>
            </h2>
        </div>
    </div>
    
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
        <div style="font-size: 11px; color: #64748b; margin-bottom: 5px;">System Status</div>
        <div style="font-size: 13px; color: #4ade80; display: flex; align-items: center; justify-content: center; gap: 5px;">
            <span style="width: 8px; height: 8px; background: #4ade80; border-radius: 50%; display: inline-block; animation: pulse 2s infinite;"></span>
            Online
        </div>
    </div>
</div>

<script>
// === SIDEBAR LOGIC ===
const sidebar = document.getElementById('mySidebar');
const hamburger = document.getElementById('hamburgerBtn');
const closeBtn = document.getElementById('closeSidebarBtn');
const overlay = document.getElementById('sidebarOverlay');
const logo = document.getElementById('logoClick');

function isMobile() {
    return window.innerWidth <= 768;
}

// Buka sidebar
function openSidebar() {
    if (isMobile()) {
        sidebar.classList.add('open');
        overlay.classList.add('show');
    } else {
        sidebar.classList.remove('closed');
        // Sembunyikan hamburger saat sidebar terbuka
        if (hamburger) hamburger.style.display = 'none';
    }
    // Update margin konten
    updateContentMargin();
}

// Tutup sidebar
function closeSidebar() {
    if (isMobile()) {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    } else {
        sidebar.classList.add('closed');
        // Tampilkan hamburger saat sidebar tertutup
        if (hamburger) hamburger.style.display = 'flex';
    }
    // Update margin konten
    updateContentMargin();
}

// Toggle sidebar
function toggleSidebar() {
    if (isMobile()) {
        if (sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    } else {
        if (sidebar.classList.contains('closed')) {
            openSidebar();
        } else {
            closeSidebar();
        }
    }
}

// Update margin konten utama saat sidebar berubah
function updateContentMargin() {
    const mainContent = document.querySelector('.main-content');
    if (!mainContent) return;
    
    if (isMobile()) {
        mainContent.style.marginLeft = '0';
    } else {
        if (sidebar.classList.contains('closed')) {
            mainContent.style.marginLeft = '0';
        } else {
            mainContent.style.marginLeft = '260px';
        }
    }
}

// Event listeners
if (hamburger) hamburger.addEventListener('click', toggleSidebar);
if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
if (overlay) overlay.addEventListener('click', closeSidebar);
if (logo) logo.addEventListener('click', toggleSidebar);

// Saat window di-resize
window.addEventListener('resize', function() {
    if (!isMobile()) {
        // Desktop mode
        if (sidebar.classList.contains('closed')) {
            sidebar.classList.add('closed');
            if (hamburger) hamburger.style.display = 'flex';
        } else {
            sidebar.classList.remove('closed');
            if (hamburger) hamburger.style.display = 'none';
        }
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    } else {
        // Mobile mode
        sidebar.classList.remove('closed');
        if (hamburger) hamburger.style.display = 'flex';
    }
    updateContentMargin();
});

// Inisialisasi awal
if (!isMobile() && !sidebar.classList.contains('closed')) {
    if (hamburger) hamburger.style.display = 'none';
}
updateContentMargin();
</script>