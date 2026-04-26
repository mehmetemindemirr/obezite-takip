/**
 * assets/js/app.js
 * Genel UI davranışları ve Chart.js grafik kurulumu.
 */

// ----------------------------------------------------------------
//  Mobil navigasyon toggle
// ----------------------------------------------------------------
document.getElementById('navToggle')?.addEventListener('click', () => {
  document.getElementById('navLinks')?.classList.toggle('open');
});

// ----------------------------------------------------------------
//  Şifre göster/gizle
// ----------------------------------------------------------------
window.togglePassword = (id) => {
  const input = document.getElementById(id);
  if (!input) return;
  input.type = input.type === 'password' ? 'text' : 'password';
};

// ----------------------------------------------------------------
//  Kilo Grafiği (sağlık takibi)
// ----------------------------------------------------------------
window.renderWeightChart = (canvasId, rawData) => {
  const canvas = document.getElementById(canvasId);
  if (!canvas || !rawData?.length) return;

  const labels   = rawData.map(r => r.date);
  const weights  = rawData.map(r => parseFloat(r.weight));

  new Chart(canvas, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Kilo (kg)',
        data: weights,
        borderColor: '#4f46e5',
        backgroundColor: 'rgba(79,70,229,.08)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: '#4f46e5',
        fill: true,
        tension: 0.3,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 11 } } }
      }
    }
  });
};

// ----------------------------------------------------------------
//  VKİ Grafiği
// ----------------------------------------------------------------
window.renderBMIChart = (canvasId, rawData) => {
  const canvas = document.getElementById(canvasId);
  if (!canvas || !rawData?.length) return;

  const labels = rawData.map(r => r.date);
  const bmis   = rawData.map(r => parseFloat(r.bmi));

  new Chart(canvas, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'VKİ',
        data: bmis,
        borderColor: '#059669',
        backgroundColor: 'rgba(5,150,105,.08)',
        borderWidth: 2,
        pointRadius: 4,
        fill: true,
        tension: 0.3,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        y: {
          grid: { color: '#f1f5f9' },
          ticks: { font: { size: 11 } },
          // VKİ referans çizgileri için annotation gerekir; şimdilik sade tutuyoruz
        }
      }
    }
  });
};

// ----------------------------------------------------------------
//  Haftalık Kalori Bar Grafiği
// ----------------------------------------------------------------
window.renderCalorieChart = (canvasId, rawData) => {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;

  // Son 7 günü doldur (veri yoksa 0)
  const days  = [];
  const totals = [];
  for (let i = 6; i >= 0; i--) {
    const d   = new Date();
    d.setDate(d.getDate() - i);
    const key = d.toISOString().split('T')[0];
    days.push(key.slice(5)); // ay-gün göster
    const found = rawData?.find(r => r.date === key);
    totals.push(found ? parseInt(found.total) : 0);
  }

  new Chart(canvas, {
    type: 'bar',
    data: {
      labels: days,
      datasets: [{
        label: 'Kalori (kcal)',
        data: totals,
        backgroundColor: 'rgba(79,70,229,.7)',
        borderRadius: 6,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 11 } } }
      }
    }
  });
};

// ----------------------------------------------------------------
//  Su Takibi - Görsel bardak (200ml = 1 bardak)
// ----------------------------------------------------------------
window.initWaterCups = (totalMl, goalMl) => {
  const container = document.getElementById('waterCups');
  if (!container) return;

  const cupSize   = 200; // ml / bardak
  const totalCups = Math.ceil(goalMl / cupSize);
  const filledCups= Math.floor(totalMl / cupSize);

  container.innerHTML = '';
  for (let i = 0; i < totalCups; i++) {
    const cup = document.createElement('span');
    cup.className  = `water-cup ${i < filledCups ? 'filled' : 'empty'}`;
    cup.textContent = '💧';
    cup.title       = `${(i + 1) * cupSize} ml`;
    container.appendChild(cup);
  }
};

// ----------------------------------------------------------------
//  Tarih seçici kısayolu: bugüne dön
// ----------------------------------------------------------------
window.setToday = (inputId) => {
  const el = document.getElementById(inputId);
  if (el) el.value = new Date().toISOString().split('T')[0];
};

// ----------------------------------------------------------------
//  Form gönderimini doğrula (kalori > 0 kontrolü)
// ----------------------------------------------------------------
document.querySelectorAll('form[data-validate]').forEach(form => {
  form.addEventListener('submit', e => {
    const calorieInput = form.querySelector('[name="calories"]');
    if (calorieInput && parseInt(calorieInput.value) <= 0) {
      e.preventDefault();
      alert('Kalori değeri 0\'dan büyük olmalıdır.');
    }
  });
});
