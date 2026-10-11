(function(){
    'use strict';
    let currentYear = 'all';
    const yearSelect = document.getElementById('yearSelect');
    const searchInput = document.getElementById('search');
    const table = document.getElementById('plantsTable');
    const tableBody = document.getElementById('tableBody');
    if(!yearSelect || !searchInput || !table || !tableBody) return;

    function setEmpty(message){
        tableBody.innerHTML = '<tr class="empty-row"><td colspan="4">' + message + '</td></tr>';
    }

    function decorateCounts(){
        Array.from(tableBody.querySelectorAll('tr')).forEach(row => {
            const cell = row.children[3];
            if(cell && !cell.querySelector('.count-badge') && !row.classList.contains('empty-row')){
                cell.innerHTML = '<span class="count-badge">' + cell.textContent.trim() + '</span>';
            }
        });
    }

    function fetchData(){
        const search = searchInput.value.trim();
        setEmpty('جاري التحميل...');
        fetch('fetch_user_plants.php?year=' + encodeURIComponent(currentYear) + '&search=' + encodeURIComponent(search), {cache:'no-store'})
            .then(res => {
                if(!res.ok) throw new Error('HTTP ' + res.status);
                return res.text();
            })
            .then(html => {
                const cleaned = html.trim();
                tableBody.innerHTML = cleaned || '<tr class="empty-row"><td colspan="4">لا توجد بيانات مطابقة</td></tr>';
                decorateCounts();
            })
            .catch(() => setEmpty('تعذر تحميل البيانات. تأكد من ملف fetch_user_plants.php.'));
    }

    function loadYears(){
        fetch('fetch_years.php', {cache:'no-store'})
            .then(res => {
                if(!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(years => {
                if(Array.isArray(years)){
                    years.forEach(y => {
                        const opt = document.createElement('option');
                        opt.value = y;
                        opt.textContent = y;
                        yearSelect.appendChild(opt);
                    });
                }
            })
            .catch(() => {
                const opt = document.createElement('option');
                opt.value = 'all';
                opt.textContent = 'تعذر تحميل السنوات';
                yearSelect.appendChild(opt);
            });
    }

    yearSelect.addEventListener('change', function(){
        currentYear = this.value || 'all';
        fetchData();
    });

    searchInput.addEventListener('input', VegApp.debounce(fetchData, 280));

    document.querySelector('[data-reset-plants]')?.addEventListener('click', function(){
        currentYear = 'all';
        yearSelect.value = 'all';
        searchInput.value = '';
        fetchData();
    });

    document.querySelector('[data-export-plants]')?.addEventListener('click', function(){
        const search = searchInput.value.trim();
        window.location.href = 'export_excel.php?year=' + encodeURIComponent(currentYear) + '&search=' + encodeURIComponent(search);
    });

    VegApp.initSortableTables(document);
    loadYears();
    fetchData();
})();
