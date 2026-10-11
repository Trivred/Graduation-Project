(function () {
    'use strict';

    const body = document.body;

    function toggleClass(target, className) {
        if (!target) return;
        target.classList.toggle(className);
    }

    function closeOnOutsideClick(selector, closeClass) {
        document.addEventListener('click', function (event) {
            if (!body.classList.contains(closeClass)) return;
            if (event.target.closest(selector)) return;
            if (event.target.closest('[data-sidebar-toggle], [data-admin-sidebar-toggle]')) return;
            body.classList.remove(closeClass);
        });
    }

    document.addEventListener('click', function (event) {
        const publicMenuToggle = event.target.closest('[data-public-menu-toggle]');
        if (publicMenuToggle) {
            const nav = document.querySelector('[data-public-nav]');
            toggleClass(nav, 'active');
            return;
        }

        const userSidebarToggle = event.target.closest('[data-sidebar-toggle]');
        if (userSidebarToggle) {
            body.classList.toggle('sidebar-open');
            return;
        }

        const adminSidebarToggle = event.target.closest('[data-admin-sidebar-toggle]');
        if (adminSidebarToggle) {
            body.classList.toggle('admin-sidebar-open');
            return;
        }

        const themeToggle = event.target.closest('[data-theme-toggle]');
        if (themeToggle) {
            body.classList.toggle('dark-mode');
            localStorage.setItem('veg-theme', body.classList.contains('dark-mode') ? 'dark' : 'light');
        }
    });

    if (localStorage.getItem('veg-theme') === 'dark') {
        body.classList.add('dark-mode');
    }

    closeOnOutsideClick('.user-sidebar', 'sidebar-open');
    closeOnOutsideClick('.admin-sidebar', 'admin-sidebar-open');
})();
