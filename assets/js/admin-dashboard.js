(function () {
  'use strict';

  function readPayload() {
    const node = document.getElementById('adminDashboardData');
    if (!node) return null;
    try { return JSON.parse(node.textContent || '{}'); }
    catch (error) { console.error('Dashboard data JSON error', error); return null; }
  }

  function animateCounters() {
    document.querySelectorAll('.counter').forEach(counter => {
      const target = Number(counter.dataset.target || 0);
      let current = 0;
      const step = Math.max(1, Math.ceil(target / 40));
      const timer = setInterval(() => {
        current += step;
        if (current >= target) { current = target; clearInterval(timer); }
        counter.textContent = current.toLocaleString('ar-SA');
      }, 18);
    });
  }

  function createBarChart(id, labels, values, label, color, max) {
    const canvas = document.getElementById(id);
    if (!canvas || typeof Chart === 'undefined') return;
    new Chart(canvas, {
      type: 'bar',
      data: { labels, datasets: [{ label, data: values, backgroundColor: color, borderRadius: 10 }] },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max } } }
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    const data = readPayload() || {};
    animateCounters();
    createBarChart('diseaseChart', data.diseaseLabels || [], data.diseaseValues || [], 'انتشار المرض %', '#d32f2f', 100);
    createBarChart('densityChart', data.densityLabels || [], data.densityValues || [], 'الكثافة', '#f9a825');

    if (data.showToast) {
      setTimeout(() => {
        const toast = document.getElementById('toast');
        toast?.classList.add('show');
        setTimeout(() => toast?.classList.remove('show'), 4200);
      }, 700);
    }
  });
})();
