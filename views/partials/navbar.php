<?php
use App\Auth\Auth;

// Set timezone to Asia/Bangkok
date_default_timezone_set('Asia/Bangkok');

// Biến view truyền vào: $navTitle, $navActive ('nhap' | 'bieu-do' | 'huong-dan' | 'admin'),
// $showAddBtn + $addBtnId (nút "+" trên thanh trên), $showHomeBtn (logo là link về trang chính).
$navTitle = $navTitle ?? 'Hệ thống nhập năng suất';
$navActive = $navActive ?? '';
$showAddBtn = $showAddBtn ?? false;
$addBtnUrl = $addBtnUrl ?? '#';
$addBtnId = $addBtnId ?? '';
$showHomeBtn = $showHomeBtn ?? true;
$homeUrl = Auth::isLoggedIn() ? Auth::getDefaultPage() : 'index.php';

$isLoggedIn = Auth::isLoggedIn();
$serverTimeStr = date('H:i:s');

// Icon Lucide (ISC) nhúng sẵn, khỏi tải thêm thư viện
$bnsIcons = [
    'square-pen' => '<path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z"/>',
    'history' => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>',
    'chart-line' => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="m19 9-5 5-4-4-3 3"/>',
    'factory' => '<path d="M2 20a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8l-7 5V8l-7 5V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M17 18h1"/><path d="M12 18h1"/><path d="M7 18h1"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'shield-check' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
    'shirt' => '<path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/>',
    'list-ordered' => '<path d="M10 12h11"/><path d="M10 18h11"/><path d="M10 6h11"/><path d="M4 10h2"/><path d="M4 6h1v4"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/>',
    'route' => '<circle cx="6" cy="19" r="3"/><path d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15"/><circle cx="18" cy="5" r="3"/>',
    'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    'clock-alert' => '<path d="M12 6v6l4 2"/><path d="M16 21.16a10 10 0 1 1 5-13.516"/><path d="M20 11.5v6"/><path d="M20 21.5h.01"/>',
    'file-plus-2' => '<path d="M4 22h14a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v4"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M3 15h6"/><path d="M6 12v6"/>',
    'file-spreadsheet' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 13h2"/><path d="M14 13h2"/><path d="M8 17h2"/><path d="M14 17h2"/>',
    'file-clock' => '<path d="M16 22h2a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v3"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><circle cx="8" cy="16" r="6"/><path d="M9.5 17.5 8 16.25V14"/>',
    'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
    'log-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
    'menu' => '<path d="M4 12h16"/><path d="M4 6h16"/><path d="M4 18h16"/>',
    'clock-3' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6h4.5"/>',
    'plus' => '<path d="M5 12h14"/><path d="M12 5v14"/>',
    'panel-left-close' => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/><path d="m16 15-3-3 3-3"/>',
    'panel-left-open' => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/><path d="m14 9 3 3-3 3"/>',
    'x' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
];
$bnsIcon = function (string $name, string $class = '') use ($bnsIcons): string {
    $cls = $class !== '' ? ' class="' . $class . '"' : '';
    return '<svg' . $cls . ' xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $bnsIcons[$name] . '</svg>';
};

if ($isLoggedIn) {
    $session = Auth::getSession();
    // Họ tên trong DB viết hoa toàn bộ; sidebar hẹp nên hiện dạng "Lê Văn Minh Đức"
    $userDisplayName = mb_convert_case(trim($session['ho_ten'] ?? ($session['username'] ?? '')), MB_CASE_TITLE, 'UTF-8');
    $isAdmin = Auth::checkRole(['admin']);
    $userRole = $isAdmin ? 'Admin' : ($session['line_ten'] ?? '');

    // Chữ viết tắt: chữ đầu của hai từ cuối trong họ tên ("Lê Văn Minh Đức" → "MĐ")
    $nameWords = preg_split('/\s+/u', trim($userDisplayName), -1, PREG_SPLIT_NO_EMPTY);
    $initials = '';
    foreach (array_slice($nameWords, -2) as $word) {
        $initials .= mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8');
    }

    $mainLinks = [
        ['key' => 'nhap', 'label' => 'Nhập năng suất', 'url' => 'nhap-nang-suat.php#/nhap-bao-cao', 'icon' => 'square-pen', 'show' => true],
        ['key' => 'lich-su', 'label' => 'Lịch sử', 'url' => 'nhap-nang-suat.php#/lich-su', 'icon' => 'history', 'show' => Auth::canViewHistory()],
        ['key' => 'bieu-do', 'label' => 'Biểu đồ', 'url' => 'bieu-do.php', 'icon' => 'chart-line', 'show' => Auth::hasLine() || $isAdmin],
    ];

    // 11 màn quản trị (trước là tab ngang trong admin.php), chia 4 nhóm
    $adminGroups = !$isAdmin ? [] : [
        ['label' => 'Người dùng & LINE', 'items' => [
            ['tab' => 'lines', 'label' => 'LINE', 'title' => 'Quản lý LINE', 'icon' => 'factory'],
            ['tab' => 'user-lines', 'label' => 'User - LINE', 'title' => 'Quản lý User-LINE', 'icon' => 'users'],
            ['tab' => 'permissions', 'label' => 'Quyền', 'title' => 'Quản lý Quyền', 'icon' => 'shield-check'],
        ]],
        ['label' => 'Mã hàng & Công đoạn', 'items' => [
            ['tab' => 'ma-hang', 'label' => 'Mã hàng', 'title' => 'Quản lý Mã hàng', 'icon' => 'shirt'],
            ['tab' => 'cong-doan', 'label' => 'Công đoạn', 'title' => 'Quản lý Công đoạn', 'icon' => 'list-ordered'],
            ['tab' => 'routing', 'label' => 'Routing', 'title' => 'Quản lý Routing', 'icon' => 'route'],
        ]],
        ['label' => 'Mốc giờ', 'items' => [
            ['tab' => 'presets', 'label' => 'Preset mốc giờ', 'title' => 'Quản lý Preset Mốc Giờ', 'icon' => 'clock'],
            ['tab' => 'moc-gio', 'label' => 'Mốc giờ (cũ)', 'title' => 'Quản lý Mốc giờ (Cũ)', 'icon' => 'clock-alert'],
        ]],
        ['label' => 'Nhập liệu hàng loạt', 'items' => [
            ['tab' => 'bulk-create', 'label' => 'Tạo báo cáo hàng loạt', 'title' => 'Tạo Báo Cáo', 'icon' => 'file-plus-2'],
            ['tab' => 'import', 'label' => 'Import Excel', 'title' => 'Import Excel', 'icon' => 'file-spreadsheet'],
            ['tab' => 'import-history', 'label' => 'Lịch sử import', 'title' => 'Lịch sử Import', 'icon' => 'file-clock'],
        ]],
    ];
}
?>
<?php if ($isLoggedIn): ?>
<script>
// Chạy trước khi nội dung vẽ: chừa chỗ cho sidebar và nhớ trạng thái thu gọn
document.body.classList.add('bns-has-sidebar');
try { if (localStorage.getItem('bns-sidebar-collapsed') === '1') document.documentElement.setAttribute('data-bns-collapsed', ''); } catch (e) {}
</script>
<div class="bns-overlay" data-bns-close></div>
<aside class="bns-sidebar" id="bnsSidebar" aria-label="Menu">
    <div class="bns-brand">
        <?php if ($showHomeBtn): ?>
        <a href="<?php echo htmlspecialchars($homeUrl); ?>" class="bns-brand-home">
        <?php else: ?>
        <div class="bns-brand-home">
        <?php endif; ?>
            <img src="img/logoht.svg" alt="">
            <div class="bns-label" style="min-width:0">
                <div class="bns-brand-name">Báo năng suất</div>
                <div class="bns-brand-sub">Hòa Thọ Điện Bàn</div>
            </div>
        <?php echo $showHomeBtn ? '</a>' : '</div>'; ?>
        <button type="button" class="bns-icon-btn bns-collapse-btn" data-bns-collapse aria-label="Thu gọn / mở rộng menu" title="Thu gọn / mở rộng menu">
            <?php echo $bnsIcon('panel-left-close', 'bns-ico-close') . $bnsIcon('panel-left-open', 'bns-ico-open'); ?>
        </button>
        <button type="button" class="bns-icon-btn bns-drawer-close" data-bns-close aria-label="Đóng menu" title="Đóng menu">
            <?php echo $bnsIcon('x'); ?>
        </button>
    </div>

    <nav class="bns-nav" aria-label="Điều hướng chính">
        <?php foreach ($mainLinks as $link): ?>
            <?php if (!$link['show']) {
                continue;
            } ?>
        <a href="<?php echo htmlspecialchars($link['url']); ?>" class="bns-nav-link" data-nav="<?php echo $link['key']; ?>" data-title="<?php echo htmlspecialchars($link['label']); ?>" title="<?php echo htmlspecialchars($link['label']); ?>"<?php echo $navActive === $link['key'] ? ' aria-current="page"' : ''; ?>>
            <?php echo $bnsIcon($link['icon']); ?><span class="bns-label"><?php echo htmlspecialchars($link['label']); ?></span>
        </a>
        <?php endforeach; ?>

        <?php foreach ($adminGroups as $i => $group): ?>
        <div class="bns-group">
            <button type="button" class="bns-group-btn" aria-expanded="true" aria-controls="bnsGroup<?php echo $i; ?>" data-bns-group>
                <span class="bns-label"><?php echo htmlspecialchars($group['label']); ?></span><?php echo $bnsIcon('chevron-down'); ?>
            </button>
            <div class="bns-group-body" id="bnsGroup<?php echo $i; ?>"><div>
                <?php foreach ($group['items'] as $item): ?>
                <a href="admin.php#/<?php echo $item['tab']; ?>" class="bns-nav-link" data-nav="admin:<?php echo $item['tab']; ?>" data-title="<?php echo htmlspecialchars($item['title']); ?>" title="<?php echo htmlspecialchars($item['title']); ?>">
                    <?php echo $bnsIcon($item['icon']); ?><span class="bns-label"><?php echo htmlspecialchars($item['label']); ?></span>
                </a>
                <?php endforeach; ?>
            </div></div>
        </div>
        <?php endforeach; ?>
    </nav>

    <div class="bns-user">
        <span class="bns-avatar" title="<?php echo htmlspecialchars($userDisplayName . ($userRole !== '' ? ' · ' . $userRole : '')); ?>"><?php echo htmlspecialchars($initials); ?></span>
        <div class="bns-user-text bns-label">
            <div class="bns-user-name" title="<?php echo htmlspecialchars($userDisplayName); ?>"><?php echo htmlspecialchars($userDisplayName); ?></div>
            <?php if ($userRole !== ''): ?>
            <div class="bns-user-role"><?php echo htmlspecialchars($userRole); ?></div>
            <?php endif; ?>
        </div>
        <a href="<?php echo htmlspecialchars(Auth::logoutUrl()); ?>" id="logoutBtn" class="bns-icon-btn" aria-label="Đăng xuất" title="Đăng xuất"><?php echo $bnsIcon('log-out'); ?></a>
    </div>
</aside>
<?php endif; ?>

<header class="bns-appbar">
    <?php if ($isLoggedIn): ?>
    <button type="button" class="bns-icon-btn bns-menu-btn" data-bns-menu aria-label="Mở menu" aria-controls="bnsSidebar" aria-expanded="false"><?php echo $bnsIcon('menu'); ?></button>
    <?php endif; ?>
    <h1 class="bns-title" id="bnsPageTitle"><?php echo htmlspecialchars($navTitle); ?></h1>
    <?php if ($isLoggedIn): ?>
    <div class="bns-bar-slot" id="bnsBarSlot" hidden></div>
    <span class="bns-clock" title="Giờ máy chủ"><?php echo $bnsIcon('clock-3'); ?><span id="server-clock"><?php echo $serverTimeStr; ?></span></span>
    <?php endif; ?>
    <?php if ($showAddBtn): ?>
    <a href="<?php echo htmlspecialchars($addBtnUrl); ?>"<?php echo $addBtnId ? ' id="' . htmlspecialchars($addBtnId) . '"' : ''; ?> class="bns-icon-btn" aria-label="Tạo báo cáo" title="Tạo báo cáo"><?php echo $bnsIcon('plus'); ?></a>
    <?php endif; ?>
</header>

<?php if ($isLoggedIn): ?>
<script>
(function() {
    const body = document.body;
    const menuBtn = document.querySelector('[data-bns-menu]');
    const titleEl = document.getElementById('bnsPageTitle');

    function setMenuOpen(open) {
        body.toggleAttribute('data-bns-menu-open', open);
        if (menuBtn) menuBtn.setAttribute('aria-expanded', String(open));
    }
    if (menuBtn) menuBtn.addEventListener('click', () => setMenuOpen(true));
    document.querySelectorAll('[data-bns-close]').forEach(el => el.addEventListener('click', () => setMenuOpen(false)));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setMenuOpen(false); });
    // Chọn mục trong panel trượt (mobile) thì đóng panel; trang khác thì trình duyệt tự chuyển
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

    // Đánh dấu mục đang xem + nhóm cha; trang admin đổi tiêu đề theo màn (admin.js gọi khi đổi tab)
    window.bnsNav = {
        setActive(key, updateTitle) {
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
    };

    // Trang nhập: Nhập năng suất / Lịch sử theo hash (#/lich-su...)
    if (location.pathname.split('/').pop() === 'nhap-nang-suat.php') {
        const syncHash = () => window.bnsNav.setActive(location.hash.startsWith('#/lich-su') ? 'lich-su' : 'nhap', false);
        syncHash();
        window.addEventListener('hashchange', syncHash);
    }
})();
</script>
<script type="module">
import realtimeService from '/baonangsuat/assets/js/modules/realtime-service.js';
realtimeService.init(<?php echo time(); ?>);
realtimeService.startClock('server-clock');
</script>
<?php endif; ?>

<?php include BNS_ROOT . '/components/toast.php'; ?>
