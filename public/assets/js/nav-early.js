document.body.classList.add('bns-has-sidebar');
try { if (localStorage.getItem('bns-sidebar-collapsed') === '1') document.documentElement.setAttribute('data-bns-collapsed', ''); } catch (e) {}
