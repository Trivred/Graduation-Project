(function(){
    'use strict';
    const table = document.getElementById('diseasesTable');
    const searchInput = document.getElementById('diseaseSearch');
    const typeButtons = document.querySelectorAll('[data-disease-type]');
    const visibleCount = document.getElementById('visibleDiseases');
    const activeTypeLabel = document.getElementById('activeDiseaseType');
    if(!table) return;
    let currentType = 'all';

    function typePredicate(row){
        return currentType === 'all' || (row.dataset.type || '') === currentType;
    }

    function applyFilters(){
        const visible = VegApp.filterRows(table, searchInput ? searchInput.value : '', typePredicate);
        let empty = table.querySelector('tbody .empty-row');
        if(visible === 0){
            if(!empty){
                empty = document.createElement('tr');
                empty.className = 'empty-row';
                empty.innerHTML = '<td colspan="5">لا توجد بيانات مطابقة</td>';
                table.tBodies[0].appendChild(empty);
            }
        }else if(empty){
            empty.remove();
        }
        if(visibleCount) visibleCount.textContent = visible;
    }

    typeButtons.forEach(btn=>{
        btn.addEventListener('click', function(){
            typeButtons.forEach(x=>x.classList.remove('active'));
            this.classList.add('active');
            currentType = this.dataset.diseaseType || 'all';
            if(activeTypeLabel) activeTypeLabel.textContent = this.textContent.trim();
            applyFilters();
        });
    });

    searchInput?.addEventListener('input', VegApp.debounce(applyFilters, 180));
    document.querySelector('[data-reset-diseases]')?.addEventListener('click', function(){
        currentType = 'all';
        if(searchInput) searchInput.value = '';
        typeButtons.forEach(x=>x.classList.remove('active'));
        document.querySelector('[data-disease-type="all"]')?.classList.add('active');
        if(activeTypeLabel) activeTypeLabel.textContent = 'كل الأنواع';
        applyFilters();
    });

    VegApp.initSortableTables(document);
    applyFilters();
})();
