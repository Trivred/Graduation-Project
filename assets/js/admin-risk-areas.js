(function () {
    'use strict';

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }
        callback();
    }

    function normalize(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[أإآ]/g, 'ا')
            .replace(/ة/g, 'ه')
            .replace(/ى/g, 'ي')
            .replace(/ؤ/g, 'و')
            .replace(/ئ/g, 'ي')
            .replace(/[ـًٌٍَُِّْ]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function toFilter(value) {
        var text = normalize(value);

        if (!text || text === 'all' || text === 'الكل') {
            return 'all';
        }

        if (text === 'high' || text === 'مرتفع' || text === 'مرتفعه' || text === 'حرج' || text === 'حرجه') {
            return 'high';
        }

        if (text === 'medium' || text === 'متوسط' || text === 'متوسطه') {
            return 'medium';
        }

        return text;
    }

    function getCardGroup(card) {
        var group = toFilter(card.dataset.riskGroup || card.dataset.level || '');

        if (group === 'high' || group === 'medium') {
            return group;
        }

        var text = normalize(card.textContent || '');
        if (text.indexOf('مرتفع') !== -1 || text.indexOf('حرج') !== -1 || text.indexOf('high') !== -1) {
            return 'high';
        }

        return 'medium';
    }

    function setCardVisible(card, visible) {
        card.classList.toggle('risk-card-hidden', !visible);
        card.setAttribute('aria-hidden', visible ? 'false' : 'true');

        if (visible) {
            card.hidden = false;
            card.style.setProperty('display', 'flex', 'important');
        } else {
            closeDetails(card);
            card.hidden = true;
            card.style.setProperty('display', 'none', 'important');
        }
    }

    function filterLabel(value) {
        if (value === 'high') {
            return 'مرتفع';
        }
        if (value === 'medium') {
            return 'متوسط';
        }
        return 'الكل';
    }

    function closeDetails(card) {
        var box = card.querySelector('.details-box');
        var btn = card.querySelector('[data-details-target]');

        if (box) {
            box.hidden = true;
            box.classList.remove('is-open');
        }

        card.classList.remove('is-expanded');

        if (btn) {
            btn.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
            btn.textContent = 'تفاصيل المنطقة';
        }
    }

    function toggleDetails(button) {
        var targetId = button.getAttribute('data-details-target');
        var box = targetId ? document.getElementById(targetId) : null;
        var card = button.closest('[data-risk-card], .widget-box');

        if (!box || !card) {
            return;
        }

        var isOpen = !box.hidden && box.classList.contains('is-open');

        if (isOpen) {
            closeDetails(card);
            return;
        }

        box.hidden = false;
        box.classList.add('is-open');
        card.classList.add('is-expanded');
        button.classList.add('is-open');
        button.setAttribute('aria-expanded', 'true');
        button.textContent = 'إخفاء التفاصيل';
    }

    function initRiskFilters() {
        var page = document.querySelector('.admin-risk-areas-page');
        if (!page) {
            return;
        }

        var cards = Array.prototype.slice.call(page.querySelectorAll('[data-risk-card], .widget-box')).filter(function (card) {
            return !card.classList.contains('empty') && !card.id;
        });

        var filterButtons = Array.prototype.slice.call(page.querySelectorAll('[data-risk-filter], [data-filter-risk], [data-filter-card-level]'));
        var searchInput = page.querySelector('#riskSearchInput');
        var searchBtn = page.querySelector('#riskSearchBtn');
        var resetBtn = page.querySelector('#riskResetBtn');
        var visibleCount = page.querySelector('#visibleAreasCount');
        var resultBox = page.querySelector('#riskFilterResult');
        var noResults = page.querySelector('#riskNoResults');
        var activeFilter = 'all';

        function applyFilters() {
            var term = normalize(searchInput ? searchInput.value : '');
            var shown = 0;

            cards.forEach(function (card) {
                var group = getCardGroup(card);
                var searchable = normalize(card.getAttribute('data-search') || card.textContent || '');
                var matchesFilter = activeFilter === 'all' || group === activeFilter;
                var matchesSearch = !term || searchable.indexOf(term) !== -1;
                var visible = matchesFilter && matchesSearch;

                setCardVisible(card, visible);

                if (visible) {
                    shown += 1;
                }
            });

            if (visibleCount) {
                visibleCount.textContent = String(shown);
            }

            if (resultBox) {
                var searchText = searchInput && searchInput.value.trim() ? ' — البحث: ' + searchInput.value.trim() : '';
                resultBox.textContent = 'يتم عرض ' + shown + ' منطقة — الفلتر الحالي: ' + filterLabel(activeFilter) + searchText;
            }

            if (noResults) {
                noResults.hidden = shown > 0;
                noResults.style.setProperty('display', shown > 0 ? 'none' : 'block', 'important');
            }
        }

        filterButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                activeFilter = toFilter(
                    button.getAttribute('data-risk-filter') ||
                    button.getAttribute('data-filter-risk') ||
                    button.getAttribute('data-filter-card-level') ||
                    button.textContent
                );

                filterButtons.forEach(function (item) {
                    item.classList.toggle('active', item === button);
                });

                applyFilters();
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', applyFilters);
            searchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    applyFilters();
                }
            });
        }

        if (searchBtn) {
            searchBtn.addEventListener('click', applyFilters);
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                activeFilter = 'all';

                if (searchInput) {
                    searchInput.value = '';
                }

                filterButtons.forEach(function (button) {
                    button.classList.toggle('active', toFilter(button.getAttribute('data-risk-filter') || button.getAttribute('data-filter-risk') || button.textContent) === 'all');
                });

                applyFilters();
            });
        }

        page.addEventListener('click', function (event) {
            var button = event.target.closest('[data-details-target]');
            if (!button) {
                return;
            }
            event.preventDefault();
            toggleDetails(button);
        });

        cards.forEach(closeDetails);
        applyFilters();
    }

    window.applyRiskAreasFilter = function () {
        initRiskFilters();
    };

    onReady(initRiskFilters);
})();
