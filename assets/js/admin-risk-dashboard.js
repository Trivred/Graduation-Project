(function () {
  'use strict';

  function readPayload() {
    const node = document.getElementById('riskDashboardData');
    if (!node) return null;
    try { return JSON.parse(node.textContent || '{}'); }
    catch (error) { console.error('Risk dashboard JSON error', error); return null; }
  }

  document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('riskChart');
    const data = readPayload() || { labels: [], values: [] };
    if (!canvas || typeof Chart === 'undefined') return;
    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: data.labels || ['مرتفع', 'متوسط', 'منخفض'],
        datasets: [{ data: data.values || [], backgroundColor: ['#dc2626', '#f59e0b', '#16a34a'], borderWidth: 0 }]
      },
      options: { plugins: { legend: { position: 'bottom' } }, cutout: '62%' }
    });
  });
})();
