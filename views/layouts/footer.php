</main><?php if (!empty($_SESSION['user_id'])): ?>

<style>
  /* Sadece Mobil Ekranlar İçin Özel Ayar */
  @media (max-width: 768px) {
    /* PC Footer'ını (Yazıyı) mobilde tamamen yok et (BEYAZ BOŞLUĞUN SEBEBİ BUYDU) */
    .app-footer { display: none !important; } 
    
    /* İkonlu Alt Menüyü Ekrana Zımbala ve iPhone Çizgisine Göre Ayarla */
    .bottom-nav {
      position: fixed !important;
      bottom: 0 !important;
      left: 0 !important;
      right: 0 !important;
      background: #ffffff !important;
      border-top: 1px solid #e2e8f0 !important;
      /* iPhone'un siyah çizgisi (Safe Area) kadar alttan otomatik boşluk bırakır */
      padding-bottom: env(safe-area-inset-bottom) !important;
      z-index: 99999 !important;
      box-shadow: 0 -2px 10px rgba(0,0,0,0.05) !important;
    }
    .bottom-nav-inner {
      display: flex !important;
      justify-content: space-around !important;
      align-items: center !important;
      padding: 12px 0 !important;
      width: 100% !important;
    }
    .bottom-nav-btn {
      background: transparent !important;
      border: none !important;
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      gap: 4px !important;
      color: #64748b !important;
      font-size: 12px !important;
    }
    .bottom-nav-btn.active { color: #4f46e5 !important; font-weight: 700 !important; }
    .bn-icon { font-size: 22px !important; line-height: 1 !important; }
  }

  /* PC Ekranlarında İkonlu Menüyü Gizle */
  @media (min-width: 769px) {
    .bottom-nav { display: none !important; }
  }
</style>

<nav class="bottom-nav" role="navigation" aria-label="Mobil navigasyon">
  <div class="bottom-nav-inner">
    <button class="bottom-nav-btn <?= str_contains($_SERVER['REQUEST_URI'], '/dashboard') ? 'active' : '' ?>"
            onclick="window.location.href='<?= APP_URL ?>/dashboard'">
      <span class="bn-icon">📊</span>
      <span>Ana Sayfa</span>
    </button>
    <button class="bottom-nav-btn <?= str_contains($_SERVER['REQUEST_URI'], '/health') ? 'active' : '' ?>"
            onclick="window.location.href='<?= APP_URL ?>/health'">
      <span class="bn-icon">📈</span>
      <span>Sağlık</span>
    </button>
    <button class="bottom-nav-btn <?= str_contains($_SERVER['REQUEST_URI'], '/meals') ? 'active' : '' ?>"
            onclick="window.location.href='<?= APP_URL ?>/meals'">
      <span class="bn-icon">🍽</span>
      <span>Kalori</span>
    </button>
    <button class="bottom-nav-btn <?= str_contains($_SERVER['REQUEST_URI'], '/water') ? 'active' : '' ?>"
            onclick="window.location.href='<?= APP_URL ?>/water'">
      <span class="bn-icon">💧</span>
      <span>Su</span>
    </button>
    <button class="bottom-nav-btn <?= str_contains($_SERVER['REQUEST_URI'], '/goals') ? 'active' : '' ?>"
            onclick="window.location.href='<?= APP_URL ?>/goals'">
      <span class="bn-icon">🎯</span>
      <span>Hedef</span>
    </button>
  </div>
</nav>

<footer class="app-footer">
  <p>NexaFit &copy; <?= date('Y') ?> — BARIŞ BOGA-M.EMİN DEMİR KURULUŞUDUR</p>
</footer>

<?php endif; ?>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>

<script>
'use strict';

/* ── Hamburger / Sidebar toggle ── */
const hamburger      = document.getElementById('hamburger');
const sidebar        = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');

if (hamburger) {
  hamburger.addEventListener('click', () => {
    const isOpen = sidebar.classList.toggle('open');
    if(sidebarOverlay) sidebarOverlay.classList.toggle('open', isOpen);
    hamburger.setAttribute('aria-expanded', String(isOpen));
  });
}

if (sidebarOverlay) {
  sidebarOverlay.addEventListener('click', closeSidebar);
}

/* Escape tuşuyla kapat */
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeSidebar();
});

function closeSidebar() {
  if (!sidebar) return;
  sidebar.classList.remove('open');
  if(sidebarOverlay) sidebarOverlay.classList.remove('open');
  if (hamburger) hamburger.setAttribute('aria-expanded', 'false');
}

/* ── Şifre göster/gizle ── */
window.togglePassword = function(inputId) {
  const el = document.getElementById(inputId);
  if (!el) return;
  el.type = el.type === 'password' ? 'text' : 'password';
};

/* ── Su bardakları ── */
window.initWaterCups = function(totalMl, goalMl) {
  const container = document.getElementById('waterCups');
  if (!container) return;
  const cupSize    = 200;
  const totalCups  = Math.ceil(goalMl / cupSize);
  const filledCups = Math.floor(totalMl / cupSize);
  container.innerHTML = '';
  for (let i = 0; i < totalCups; i++) {
    const span = document.createElement('span');
    span.className   = 'water-cup ' + (i < filledCups ? 'filled' : 'empty');
    span.textContent = '💧';
    span.title       = `${(i + 1) * cupSize} ml`;
    container.appendChild(span);
  }
};

/* ── Kilo Grafiği ── */
window.renderWeightChart = function(canvasId, rawData) {
  const canvas = document.getElementById(canvasId);
  if (!canvas || !rawData?.length) return;
  new Chart(canvas, {
    type: 'line',
    data: {
      labels:   rawData.map(r => r.date),
      datasets: [{
        label: 'kg',
        data:  rawData.map(r => parseFloat(r.weight)),
        borderColor: '#4f46e5',
        backgroundColor: 'rgba(79,70,229,.07)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: '#4f46e5',
        fill: true,
        tension: 0.35
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        y: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { size: 11 } } }
      }
    }
  });
};

/* ── VKİ Grafiği ── */
window.renderBMIChart = function(canvasId, rawData) {
  const canvas = document.getElementById(canvasId);
  if (!canvas || !rawData?.length) return;
  new Chart(canvas, {
    type: 'line',
    data: {
      labels:   rawData.map(r => r.date),
      datasets: [{
        label: 'VKİ',
        data:  rawData.map(r => parseFloat(r.bmi)),
        borderColor: '#059669',
        backgroundColor: 'rgba(5,150,105,.07)',
        borderWidth: 2,
        pointRadius: 4,
        fill: true,
        tension: 0.35
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        y: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { size: 11 } } }
      }
    }
  });
};

/* ── Haftalık Kalori Bar Grafiği ── */
window.renderCalorieChart = function(canvasId, rawData) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;

  const days = [], totals = [];
  for (let i = 6; i >= 0; i--) {
    const d   = new Date();
    d.setDate(d.getDate() - i);
    const key = d.toISOString().split('T')[0];
    days.push(d.toLocaleDateString('tr-TR', { day: '2-digit', month: 'short' }));
    const found = rawData?.find(r => r.date === key);
    totals.push(found ? parseInt(found.total) : 0);
  }

  new Chart(canvas, {
    type: 'bar',
    data: {
      labels: days,
      datasets: [{
        label: 'kcal',
        data: totals,
        backgroundColor: 'rgba(79,70,229,.65)',
        borderRadius: 5
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        y: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { size: 11 } } }
      }
    }
  });
};

/* ── Bugünün tarihini date input'a yaz ── */
window.setToday = function(inputId) {
  const el = document.getElementById(inputId);
  if (el) el.value = new Date().toISOString().split('T')[0];
};
</script>

</body>
</html>