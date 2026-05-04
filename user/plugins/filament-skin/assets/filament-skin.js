(function () {
    'use strict';

    function initSidebar() {
        var shell = document.querySelector('[data-fs-shell]');
        if (!shell) return;

        var openBtn = shell.querySelector('[data-fs-sidebar-open]');
        var closeBtn = shell.querySelector('[data-fs-sidebar-close]');
        var backdrop = shell.querySelector('[data-fs-sidebar-backdrop]');

        if (openBtn) openBtn.addEventListener('click', function () {
            shell.setAttribute('data-fs-sidebar-open', '');
        });
        var close = function () { shell.removeAttribute('data-fs-sidebar-open'); };
        if (closeBtn) closeBtn.addEventListener('click', close);
        if (backdrop) backdrop.addEventListener('click', close);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') close();
        });
    }

    function initThemeToggle() {
        var btn = document.querySelector('[data-fs-theme-toggle]');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var current = document.documentElement.getAttribute('data-fs-theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var isDark = current === 'dark' || (!current && prefersDark);
            var next = isDark ? 'light' : 'dark';
            document.documentElement.setAttribute('data-fs-theme', next);
            try { localStorage.setItem('fs-theme', next); } catch (e) {}
        });
    }

    /**
     * The native YOURLS feedback function uses jQuery. We hook into it
     * to add classes that match our CSS (success/fail).
     * Already handled by core in common.js — just ensure our CSS hooks work.
     */

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initSidebar();
            initThemeToggle();
        });
    } else {
        initSidebar();
        initThemeToggle();
    }
})();
