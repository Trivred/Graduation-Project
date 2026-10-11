/* =========================================================
   Admin Table / UI Tools
   Purpose: one shared script for search, sorting, export, modals and simple card filtering.
   ========================================================= */
(function () {
  'use strict';

  const byId = (id) => document.getElementById(id);
  const qsa = (selector, root = document) => Array.from(root.querySelectorAll(selector));

  function visibleRows(table) {
    return qsa('tr', table).filter(row => row.style.display !== 'none');
  }

  function normalizeText(value) {
    return (value || '').toString().trim().toLowerCase();
  }

  function shouldSkipColumn(cell, index, row) {
    const header = row.closest('table')?.tHead?.rows?.[0]?.cells?.[index];
    if (!header) return false;
    return header.dataset.sortable === 'false' || header.dataset.export === 'false' || /العمليات|actions/i.test(header.innerText);
  }

  function exportTableToCSV(filename, table = byId('dataTable')) {
    if (!table) return;
    const csv = visibleRows(table).map(row => {
      return qsa('th,td', row)
        .filter((cell, index) => !shouldSkipColumn(cell, index, row))
        .map(cell => '"' + cell.innerText.replace(/"/g, '""').trim() + '"')
        .join(',');
    }).join('\n');

    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename || 'table_export.csv';
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(link.href);
  }

  function sortTable(table, index, numeric) {
    const tbody = table.tBodies[0];
    if (!tbody) return;

    const rows = Array.from(tbody.rows);
    const current = table.dataset.sortIndex == index ? table.dataset.sortDir : 'desc';
    const dir = current === 'asc' ? 'desc' : 'asc';

    rows.sort((a, b) => {
      let av = a.cells[index]?.innerText.trim() || '';
      let bv = b.cells[index]?.innerText.trim() || '';
      if (numeric) {
        av = parseFloat(av.replace(/[^0-9.-]/g, '')) || 0;
        bv = parseFloat(bv.replace(/[^0-9.-]/g, '')) || 0;
        return (av - bv) * (dir === 'asc' ? 1 : -1);
      }
      return av.localeCompare(bv, 'ar', { numeric: true, sensitivity: 'base' }) * (dir === 'asc' ? 1 : -1);
    });

    rows.forEach(row => tbody.appendChild(row));
    table.dataset.sortIndex = index;
    table.dataset.sortDir = dir;

    qsa('th', table).forEach(th => th.classList.remove('sorted-asc', 'sorted-desc'));
    const activeHeader = table.tHead?.rows?.[0]?.cells?.[index];
    activeHeader?.classList.add(dir === 'asc' ? 'sorted-asc' : 'sorted-desc');
    applyTableFilters(table);
  }

  function applyTableFilters(table = byId('dataTable')) {
    if (!table) return;
    const searchInput = byId('searchInput');
    const yearFilter = byId('yearFilter');
    const query = normalizeText(searchInput?.value);
    const selectedYear = yearFilter?.value || 'all';

    qsa('tbody tr', table).forEach(row => {
      const matchesSearch = !query || normalizeText(row.innerText).includes(query);
      const matchesYear = selectedYear === 'all' || !row.dataset.year || row.dataset.year === selectedYear;
      row.style.display = (matchesSearch && matchesYear) ? '' : 'none';
    });
  }

  function initTableTools() {
    const table = byId('dataTable');
    if (!table) return;

    byId('searchInput')?.addEventListener('input', () => applyTableFilters(table));
    byId('yearFilter')?.addEventListener('change', () => applyTableFilters(table));

    qsa('.sortable-table th', table).forEach((th, index) => {
      if (th.dataset.sortable === 'false') return;
      th.addEventListener('click', () => sortTable(table, index, th.dataset.type === 'number'));
    });
  }

  function resetFilters() {
    const searchInput = byId('searchInput');
    const yearFilter = byId('yearFilter');
    if (searchInput) searchInput.value = '';
    if (yearFilter) yearFilter.value = 'all';
    applyTableFilters();
  }

  function setFieldValue(key, value) {
    const ids = [key, 'edit_' + key];
    for (const id of ids) {
      const el = byId(id);
      if (el) {
        el.value = value ?? '';
        return;
      }
    }
  }

  function openModal(modal) {
    if (!modal) return;
    modal.classList.add('is-open');
    modal.style.display = 'flex';
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.style.display = 'none';
  }

  function fillDatasetModal(button) {
    const modal = byId('modal') || byId('editModal');
    Object.entries(button.dataset).forEach(([key, value]) => {
      if (!['confirm', 'exportTable', 'toggleDetails', 'filterCardLevel', 'exportCards'].includes(key)) {
        setFieldValue(key, value);
      }
    });
    openModal(modal);
  }

  function openEditModal(row) {
    const modal = byId('editModal') || byId('modal');
    if (!row || typeof row !== 'object') return;
    setFieldValue('id', row.disease_id || row.id || row.plant_id || '');
    setFieldValue('name', row.name || row.plant_name || row.local_name_ar || '');
    setFieldValue('type', row.disease_type || row.type || '');
    setFieldValue('severity', row.severity || 'low');
    setFieldValue('crops', row.affected_crops || '');
    setFieldValue('transmission', row.transmission_mode || '');
    setFieldValue('region', row.preferred_region || row.region_name || row.region_name_ar || '');
    openModal(modal);
  }

  function initModals() {
    qsa('.open-edit').forEach(button => {
      button.addEventListener('click', () => fillDatasetModal(button));
    });

    qsa('.open-edit-row').forEach(button => {
      button.addEventListener('click', () => {
        try { openEditModal(JSON.parse(button.dataset.row || '{}')); }
        catch (error) { console.error('Invalid row JSON', error); }
      });
    });

    qsa('.modal, .modal-box').forEach(modal => {
      qsa('.close, [data-close-modal]', modal).forEach(btn => btn.addEventListener('click', () => closeModal(modal)));
      modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(modal); });
    });
  }

  let currentCardLevel = 'all';

  function applyCardFilters() {
    const searchBox = byId('searchBox');
    const query = normalizeText(searchBox?.value);
    qsa('.widget-box').forEach(card => {
      const levelOk = currentCardLevel === 'all' || card.dataset.level === currentCardLevel;
      const textOk = !query || normalizeText(card.dataset.search || card.innerText).includes(query);
      card.style.display = (levelOk && textOk) ? '' : 'none';
    });
  }

  function exportCardsCSV(filename = 'risk_areas.csv') {
    const visible = qsa('.widget-box').filter(card => card.style.display !== 'none');
    const rows = ['"المنطقة","المستوى","الخطر","الكثافة","النباتات السامة","النباتات النادرة"'];
    visible.forEach(card => {
      rows.push((card.dataset.export || '').split('|').map(value => '"' + value.replace(/"/g, '""') + '"').join(','));
    });
    const blob = new Blob(['\ufeff' + rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(link.href);
  }

  function initCards() {
    byId('searchBox')?.addEventListener('input', applyCardFilters);

    qsa('[data-filter-card-level]').forEach(btn => {
      btn.addEventListener('click', () => {
        currentCardLevel = btn.dataset.filterCardLevel || 'all';
        qsa('[data-filter-card-level]').forEach(item => item.classList.remove('active'));
        btn.classList.add('active');
        applyCardFilters();
      });
    });

    qsa('[data-toggle-details]').forEach(btn => {
      btn.addEventListener('click', () => {
        const box = byId(btn.dataset.toggleDetails);
        if (!box) return;
        box.classList.toggle('open');
        btn.textContent = box.classList.contains('open') ? 'إخفاء التفاصيل' : 'تفاصيل المنطقة';
      });
    });

    qsa('[data-export-cards]').forEach(btn => {
      btn.addEventListener('click', () => exportCardsCSV(btn.dataset.exportCards || 'risk_areas.csv'));
    });
  }

  function initActions() {
    qsa('[data-export-table]').forEach(btn => {
      btn.addEventListener('click', () => exportTableToCSV(btn.dataset.exportTable));
    });

    qsa('[data-confirm]').forEach(item => {
      item.addEventListener('click', (event) => {
        if (!confirm(item.dataset.confirm || 'هل أنت متأكد؟')) event.preventDefault();
      });
    });

    qsa('[data-print-page]').forEach(btn => btn.addEventListener('click', () => window.print()));
    qsa('[data-reload-page]').forEach(btn => btn.addEventListener('click', () => window.location.reload()));
    qsa('[data-reset-filters]').forEach(btn => btn.addEventListener('click', resetFilters));
    qsa('[data-navigate]').forEach(card => {
      card.addEventListener('click', () => { window.location.href = card.dataset.navigate; });
      card.setAttribute('role', 'button');
      card.tabIndex = 0;
      card.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') window.location.href = card.dataset.navigate;
      });
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    initTableTools();
    initModals();
    initCards();
    initActions();
  });

  window.exportTableToCSV = exportTableToCSV;
  window.resetFilters = resetFilters;
  window.openEditModal = openEditModal;
  window.closeModal = function () { closeModal(byId('editModal') || byId('modal')); };
  window.exportCardsCSV = exportCardsCSV;
})();
