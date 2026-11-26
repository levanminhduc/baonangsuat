const ACTIVE = ['border-[#143583]', 'text-[#143583]'];
const INACTIVE = ['border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300'];

function bindTabs(container) {
    const nav = container.querySelector('nav');
    if (!nav) return;

    nav.addEventListener('click', (e) => {
        const button = e.target.closest('button[data-tabs-target]');
        if (!button) return;

        container.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));

        container.querySelectorAll('nav button').forEach(btn => {
            btn.classList.remove(...ACTIVE);
            btn.classList.add(...INACTIVE);
            btn.setAttribute('aria-current', 'false');
        });

        document.getElementById(button.dataset.tabsTarget).classList.remove('hidden');

        button.classList.remove(...INACTIVE);
        button.classList.add(...ACTIVE);
        button.setAttribute('aria-current', 'page');
    });
}

document.querySelectorAll('[data-tabs]').forEach(bindTabs);
