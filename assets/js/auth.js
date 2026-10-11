(function(){
    'use strict';
    const tabs = document.querySelectorAll('[data-auth-tab]');
    const forms = document.querySelectorAll('[data-auth-form]');

    function showTab(name){
        tabs.forEach(tab=>tab.classList.toggle('active', tab.dataset.authTab === name));
        forms.forEach(form=>form.classList.toggle('active', form.dataset.authForm === name));
    }

    tabs.forEach(tab=>tab.addEventListener('click', ()=>showTab(tab.dataset.authTab)));

    document.querySelectorAll('[data-toggle-password]').forEach(button=>{
        button.addEventListener('click', function(){
            const target = document.getElementById(this.dataset.togglePassword);
            if(!target) return;
            target.type = target.type === 'password' ? 'text' : 'password';
            this.innerHTML = target.type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
            this.setAttribute('aria-label', target.type === 'password' ? 'Show password' : 'Hide password');
        });
    });

    const active = document.body.dataset.activeTab || 'login';
    showTab(active);
})();
