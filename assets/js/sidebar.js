(function(){
    'use strict';
    const body = document.body;
    const savedTheme = localStorage.getItem('veg-theme');
    if(savedTheme === 'dark') body.classList.add('dark-mode');

    document.addEventListener('click', function(e){
        if(e.target.closest('[data-sidebar-toggle]')){
            body.classList.toggle('sidebar-open');
        }
        if(e.target.closest('[data-theme-toggle]')){
            body.classList.toggle('dark-mode');
            localStorage.setItem('veg-theme', body.classList.contains('dark-mode') ? 'dark' : 'light');
        }
    });

    const current = location.pathname.split('/').pop() || 'user_dashboard.php';
    document.querySelectorAll('.sidebar-link').forEach(link=>{
        const href = link.getAttribute('href') || '';
        if(href.split('/').pop() === current){
            link.classList.add('active');
        }
    });
})();
