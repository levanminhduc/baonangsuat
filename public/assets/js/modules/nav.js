import realtimeService from './realtime-service.js';

const body = document.body;
const menuBtn = document.querySelector('[data-bns-menu]');
const titleEl = document.getElementById('bnsPageTitle');

function setMenuOpen(open) {
    body.toggleAttribute('data-bns-menu-open', open);
    if (menuBtn) menuBtn.setAttribute('aria-expanded', String(open));
}

export function setActiveNav(key, updateTitle) {
    let activeLink = null;
    document.querySelectorAll('.bns-nav-link').forEach(a => {
        const on = a.dataset.nav === key;
        if (on) {
            a.setAttribute('aria-current', 'page');
            activeLink = a;
        } else {
            a.removeAttribute('aria-current');
        }
    });
    document.querySelectorAll('[data-bns-group]').forEach(btn => {
        btn.classList.toggle('is-active', !!activeLink && btn.nextElementSibling.contains(activeLink));
    });
    if (updateTitle && activeLink && titleEl) {
        titleEl.textContent = activeLink.dataset.title;
    }
}

function bindSidebar() {
    if (menuBtn) menuBtn.addEventListener('click', () => setMenuOpen(true));
    document.querySelectorAll('[data-bns-close]').forEach(el => el.addEventListener('click', () => setMenuOpen(false)));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setMenuOpen(false); });
    document.querySelectorAll('.bns-nav-link').forEach(a => a.addEventListener('click', () => setMenuOpen(false)));

    document.querySelectorAll('[data-bns-group]').forEach(btn => {
        btn.addEventListener('click', () => {
            const groupBody = btn.nextElementSibling;
            const open = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', String(!open));
            groupBody.toggleAttribute('data-closed', open);
            groupBody.inert = open;
        });
    });

    const collapseBtn = document.querySelector('[data-bns-collapse]');
    if (collapseBtn) {
        collapseBtn.addEventListener('click', () => {
            const collapsed = !document.documentElement.hasAttribute('data-bns-collapsed');
            document.documentElement.toggleAttribute('data-bns-collapsed', collapsed);
            try { localStorage.setItem('bns-sidebar-collapsed', collapsed ? '1' : '0'); } catch (e) {}
        });
    }

    if (location.pathname.split('/').pop() === 'nhap-nang-suat.php') {
        const syncHash = () => setActiveNav(location.hash.startsWith('#/lich-su') ? 'lich-su' : 'nhap', false);
        syncHash();
        window.addEventListener('hashchange', syncHash);
    }
}

function startClock() {
    const clock = document.getElementById('server-clock');
    const serverTime = Number(clock && clock.dataset.serverTime);
    if (!serverTime) return;
    realtimeService.init(serverTime);
    realtimeService.startClock('server-clock');
}

function init() {
    if (!document.getElementById('bnsSidebar')) return;
    if (document.documentElement.dataset.bnsSidebarReady) return;
    document.documentElement.dataset.bnsSidebarReady = '1';
    bindSidebar();
    startClock();
}

init();
