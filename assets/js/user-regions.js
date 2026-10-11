(function(){
    'use strict';

    const table = document.getElementById('regionsTable');
    const searchInput = document.getElementById('regionSearch');
    const statusSelect = document.getElementById('statusFilter');
    const counter = document.getElementById('visibleCount');

    if(!table) return;

    function statusPredicate(row){
        if(!statusSelect || !statusSelect.value) return true;
        return (row.dataset.status || '').trim() === statusSelect.value.trim();
    }

    function applyFilters(){
        const visible = VegApp.filterRows(table, searchInput ? searchInput.value : '', statusPredicate);
        let empty = table.querySelector('tbody .empty-row.generated-empty');

        if(visible === 0){
            if(!empty){
                empty = document.createElement('tr');
                empty.className = 'empty-row generated-empty';
                empty.innerHTML = '<td colspan="6">لا توجد بيانات مطابقة</td>';
                table.tBodies[0].appendChild(empty);
            }
        }else if(empty){
            empty.remove();
        }

        if(counter) counter.textContent = visible;
    }

    searchInput?.addEventListener('input', VegApp.debounce(applyFilters, 180));
    statusSelect?.addEventListener('change', applyFilters);

    document.querySelector('[data-reset-regions]')?.addEventListener('click', function(){
        if(searchInput) searchInput.value = '';
        if(statusSelect) statusSelect.value = '';
        applyFilters();
    });

    VegApp.initSortableTables(document);
    applyFilters();
})();
