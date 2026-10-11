(function(){
    'use strict';

    function toNumber(value){
        const n = parseFloat(String(value || '').replace(/,/g,'').replace(/[^0-9.\-]/g,''));
        return Number.isFinite(n) ? n : 0;
    }

    function normalize(value){
        return String(value || '').trim().toLowerCase();
    }

    function animateCounters(root){
        const scope = root || document;
        scope.querySelectorAll('.counter').forEach(counter=>{
            const target = parseFloat(counter.dataset.target || '0');
            const isInt = Number.isInteger(target);
            const steps = 70;
            const increment = target / steps;
            let count = 0;
            function update(){
                if(count < target){
                    count += increment;
                    counter.textContent = isInt ? Math.ceil(count) : count.toFixed(2);
                    requestAnimationFrame(update);
                }else{
                    counter.textContent = isInt ? String(target) : target.toFixed(2);
                }
            }
            update();
        });
    }

    function sortTable(table, index, direction, type){
        const tbody = table.tBodies[0];
        if(!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => !row.classList.contains('empty-row') && row.style.display !== 'none');
        rows.sort((a,b)=>{
            let av = a.children[index] ? a.children[index].innerText.trim() : '';
            let bv = b.children[index] ? b.children[index].innerText.trim() : '';
            if(type === 'number'){
                av = toNumber(av); bv = toNumber(bv);
            }else{
                av = normalize(av); bv = normalize(bv);
            }
            if(av < bv) return direction === 'asc' ? -1 : 1;
            if(av > bv) return direction === 'asc' ? 1 : -1;
            return 0;
        });
        rows.forEach(row => tbody.appendChild(row));
    }

    function initSortableTables(root){
        const scope = root || document;
        scope.querySelectorAll('[data-sortable-table]').forEach(table=>{
            const state = { index:null, direction:'asc', type:'text' };
            table.querySelectorAll('thead th[data-index]').forEach(th=>{
                th.addEventListener('click', ()=>{
                    const index = Number(th.dataset.index);
                    const type = th.dataset.type || 'text';
                    const direction = (state.index === index && state.direction === 'asc') ? 'desc' : 'asc';
                    state.index = index; state.direction = direction; state.type = type;
                    table.querySelectorAll('thead th').forEach(h=>h.classList.remove('active-sort'));
                    th.classList.add('active-sort');
                    sortTable(table, index, direction, type);
                });
            });
        });
    }

    function filterRows(table, term, extraPredicate){
        const tbody = table.tBodies[0];
        if(!tbody) return 0;
        const q = normalize(term);
        let visible = 0;
        Array.from(tbody.querySelectorAll('tr')).forEach(row=>{
            if(row.classList.contains('empty-row')) return;
            const textMatch = !q || normalize(row.innerText).includes(q);
            const extraMatch = typeof extraPredicate === 'function' ? extraPredicate(row) : true;
            const show = textMatch && extraMatch;
            row.style.display = show ? '' : 'none';
            if(show) visible++;
        });
        return visible;
    }

    function debounce(fn, delay){
        let timer;
        return function(){
            const args = arguments;
            clearTimeout(timer);
            timer = setTimeout(()=>fn.apply(this,args), delay || 250);
        };
    }

    window.VegApp = { toNumber, normalize, animateCounters, sortTable, initSortableTables, filterRows, debounce };
})();
