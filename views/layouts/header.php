<!DOCTYPE html>
<html lang="tr">
<head>
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="NexaFit">
    <link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/icon.png">
    <meta charset="UTF-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <title><?= htmlspecialchars($title ?? 'NexaFit') ?> — NexaFit</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap&subset=latin,latin-ext" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js" defer></script>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // İsim / Hedef yazısını gizleme
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null, false);
        let node;
        while (node = walker.nextNode()) {
            if (node.nodeValue.includes('EMİN DEMİR KURULUŞUDUR')) { node.nodeValue = ''; }
        }
        document.querySelectorAll('button').forEach(b => { 
            if(b.innerText.includes('Hedef')) { b.style.display = 'none'; }
        });

        // HAMBURGER MENÜ ÇALIŞTIRICISI
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        if(hamburger && sidebar) {
            hamburger.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }
    });
    </script>
</head>
<body>

<?php if (!empty($_SESSION['user_id'])): ?>

<header class="topbar" id="topbar">
  <div class="topbar-brand" style="display: flex; align-items: center; gap: 8px;">
    <img src="<?= APP_URL ?>/assets/icon.png" alt="Logo" style="width: 24px; height: 24px; border-radius: 6px;">
    <span>NexaFit</span>
  </div>
  <button class="hamburger" id="hamburger" aria-label="Menüyü aç/kapat">
    ☰
  </button>
</header>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<nav class="sidebar" id="sidebar" style="display: flex; flex-direction: column; height: 100vh; justify-content: space-between;">

  <div> 
    <div class="nav-brand" style="display: flex; align-items: center; gap: 12px; padding: 25px 20px;">
      <img src="<?= APP_URL ?>/assets/icon.png" alt="NexaFit" style="width: 36px; height: 36px; border-radius: 10px; object-fit: cover; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
      <span class="brand-name" style="font-weight: 700; font-size: 1.4rem; color: #4f46e5;">NexaFit</span>
    </div>

    <ul class="nav-links" style="padding-top: 10px;">
      <li>
        <a href="<?= APP_URL ?>/dashboard" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/dashboard') ? 'active' : '' ?>">
          <span class="nav-icon">📊</span> Ana Sayfa
        </a>
      </li>
      <li>
        <a href="<?= APP_URL ?>/health" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/health') ? 'active' : '' ?>">
          <span class="nav-icon">📈</span> Sağlık Takibi
        </a>
      </li>
      <li>
        <a href="<?= APP_URL ?>/meals" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/meals') ? 'active' : '' ?>">
          <span class="nav-icon">🍽</span> Kalori Takibi
        </a>
      </li>
      <li>
        <a href="<?= APP_URL ?>/water" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/water') ? 'active' : '' ?>">
          <span class="nav-icon">💧</span> Su Takibi
        </a>
      </li>
      <li>
        <a href="<?= APP_URL ?>/goals" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/goals') ? 'active' : '' ?>">
          <span class="nav-icon">🎯</span> Hedef Kilo
        </a>
      </li>
      <li>
        <a href="<?= APP_URL ?>/profile" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/profile') ? 'active' : '' ?>">
          <span class="nav-icon">👤</span> Profil
        </a>
      </li>
      <li>
        <a href="<?= APP_URL ?>/auth/logout" class="nav-link nav-logout">
          <span class="nav-icon">🚪</span> Çıkış Yap
        </a>
      </li>
    </ul>
  </div> 

  <div style="padding: 20px; border-top: 1px solid #f1f5f9; background: #fafafa;">
    <div style="background: #ffffff; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <span style="display: block; font-size: 11px; color: #4f46e5; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;">NexaFit Premium</span>
        <small style="display: block; color: #64748b; font-size: 10px; margin-top: 4px; font-weight: 500;">NURSOFTWARE.V1.0.1</small>
        <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #f1f5f9; font-size: 10px; color: #94a3b8; font-weight: 500;">
             Tüm Hakları Saklıdır
        </div>
    </div>
  </div>

</nav>

<?php endif; ?>

<main class="main-content <?= empty($_SESSION['user_id']) ? 'auth-page' : '' ?>">

<?php
if (!empty($flash)):
  $fType = htmlspecialchars($flash['type']);
  $fMsg  = $flash['message'];
?>
<div class="alert alert-<?= $fType ?>" role="alert">
  <span><?= $fMsg ?></span>
  <button class="alert-close" onclick="this.parentElement.remove()" aria-label="Kapat">×</button>
</div>
<?php endif; ?>