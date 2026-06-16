// public/js/main.js  — SITANGKIS Global JS

// Mobile nav toggle
function toggleMenu() {
  const m = document.getElementById('mobileMenu');
  if (m) m.classList.toggle('open');
}

// Auto-dismiss alerts after 4s
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.alert[data-auto-dismiss]').forEach(el => {
    setTimeout(() => el.style.display = 'none', 4000);
  });

  // Confirm delete links
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm || 'Yakin ingin menghapus?')) {
        e.preventDefault();
      }
    });
  });

  // Rupiah formatter on inputs with class .rupiah-input
  document.querySelectorAll('.rupiah-preview').forEach(el => {
    const input = document.getElementById(el.dataset.for);
    if (!input) return;
    const update = () => {
      const v = parseInt(input.value) || 0;
      el.textContent = 'Rp ' + v.toLocaleString('id-ID');
    };
    input.addEventListener('input', update);
    update();
  });
});

// Chart helpers — dipakai di halaman yang butuh chart
function makeBarChart(id, labels, datasets) {
  const ctx = document.getElementById(id);
  if (!ctx) return null;
  return new Chart(ctx, {
    type: 'bar',
    data: { labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom' } },
      scales: {
        y: {
          ticks: { callback: v => 'Rp ' + (v / 1e6).toFixed(0) + 'jt' },
          grid: { color: '#f1f5f9' }
        },
        x: { grid: { display: false } }
      }
    }
  });
}

function makePieChart(id, labels, data, colors) {
  const ctx = document.getElementById(id);
  if (!ctx) return null;
  return new Chart(ctx, {
    type: 'doughnut',
    data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 2 }] },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom' } }
    }
  });
}

function makeLineChart(id, labels, data) {
  const ctx = document.getElementById(id);
  if (!ctx) return null;
  return new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Realisasi (jt)',
        data,
        borderColor: '#1a3a6b',
        backgroundColor: 'rgba(26,58,107,.1)',
        borderWidth: 2.5,
        pointBackgroundColor: '#1a3a6b',
        tension: 0.4,
        fill: true
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom' } },
      scales: {
        y: { ticks: { callback: v => v + 'jt' }, grid: { color: '#f1f5f9' } },
        x: { grid: { display: false } }
      }
    }
  });
}
