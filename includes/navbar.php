<?php
require_once __DIR__ . '/../classes/Auth.php';

// Set timezone to Asia/Bangkok
date_default_timezone_set('Asia/Bangkok');

$navTitle = $navTitle ?? 'HỆ THỐNG NHẬP NĂNG SUẤT';
$showAddBtn = $showAddBtn ?? false;
$addBtnUrl = $addBtnUrl ?? '#';
$addBtnId = $addBtnId ?? '';
$showHomeBtn = $showHomeBtn ?? true;
$homeUrl = Auth::isLoggedIn() ? Auth::getDefaultPage() : 'index.php';

// Get user info and server time if logged in
$isLoggedIn = Auth::isLoggedIn();
$userDisplayName = '';
$userInfo = '';
$serverH = date('H');
$serverM = date('i');
$serverS = date('s');
$serverTimeStr = date('H:i:s');

if ($isLoggedIn) {
    $session = Auth::getSession();
    $userDisplayName = $session['ho_ten'] ?? ($session['username'] ?? '');
    
    // Construct extra info (Role or Line)
    $extraInfo = '';
    if (Auth::checkRole(['admin'])) {
        $extraInfo = ' (Admin)';
    } elseif (isset($session['line_ten'])) {
        $extraInfo = ' (' . $session['line_ten'] . ')';
    }
    
    $userInfo = $userDisplayName . $extraInfo;
}
?>

<div id="navbar-sticky-wrapper" style="position: sticky; top: 0; z-index: 900; width: 100%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
<table width="100%" class="navbar-desktop-table">
    <tr style="text-align:center;color:white;">
        <td bgcolor="143583" style="text-align:center;border-right-color:#143583;width:60px;">
            <?php if ($showHomeBtn): ?>
            <a href="<?php echo htmlspecialchars($homeUrl); ?>"><img width="45px" src="img/logoht.svg"/></a>
            <?php else: ?>
            <img width="45px" src="img/logoht.svg"/>
            <?php endif; ?>
        </td>
        <td bgcolor="143583" style="font-size:2em;font-weight:bold;text-align:center;line-height: 1.0em;padding:10px 5px;">
            <?php echo htmlspecialchars($navTitle); ?>
        </td>

        <td bgcolor="143583" style="width:60px;">
            <?php if ($showAddBtn): ?>
            <a href="<?php echo htmlspecialchars($addBtnUrl); ?>"<?php echo $addBtnId ? ' id="' . htmlspecialchars($addBtnId) . '"' : ''; ?>><img style="border-radius:5px;" src="img/add.svg" width="55px"/></a>
            <?php endif; ?>
        </td>
    </tr>
</table>

<div class="navbar-mobile-container">
    <div style="text-align:center; background:#143583; color:white; padding:15px;">
        <div style="display:flex; align-items:center; justify-content:space-between;">
            <?php if ($showHomeBtn): ?>
            <a href="<?php echo htmlspecialchars($homeUrl); ?>"><img width="35px" src="img/logoht.svg" /></a>
            <?php else: ?>
            <img width="35px" src="img/logoht.svg" />
            <?php endif; ?>
            <div style="flex:1; font-size:1.2em; font-weight:bold; margin:0 10px;">
                <?php echo htmlspecialchars($navTitle); ?>
            </div>
            <div>
                <?php if ($showAddBtn): ?>
                <a href="<?php echo htmlspecialchars($addBtnUrl); ?>"<?php echo $addBtnId ? ' class="' . htmlspecialchars($addBtnId) . '-mobile"' : ''; ?>><img style="border-radius:5px;" src="img/add.svg" width="30px" /></a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php if ($isLoggedIn): ?>
<div class="navbar-sub-bar">
    <span id="server-clock" class="sub-bar-clock">[<?php echo $serverTimeStr; ?>]</span>
    <span class="sub-bar-item"><?php echo htmlspecialchars($userInfo); ?></span>
    
    <?php
    // Navigation Links
    $navLinks = [
        ['label' => 'Nhập năng suất', 'url' => 'nhap-nang-suat.php#/nhap-bao-cao', 'show' => true],
        ['label' => 'Lịch sử', 'url' => 'nhap-nang-suat.php#/lich-su', 'show' => Auth::canViewHistory()],
        ['label' => 'Quản trị', 'url' => 'admin.php', 'show' => Auth::checkRole(['admin'])],
        // ['label' => 'Hướng dẫn', 'url' => 'huong-dan.php', 'show' => true]
    ];

    foreach ($navLinks as $navLink) {
        if ($navLink['show']) {
            echo '<span class="sub-bar-separator">|</span>';
            echo '<a href="' . htmlspecialchars($navLink['url']) . '" class="sub-bar-link" data-href="' . htmlspecialchars($navLink['url']) . '">' . htmlspecialchars($navLink['label']) . '</a>';
        }
    }
    ?>
    
    <span class="sub-bar-separator">|</span>
    <a href="#" id="logoutBtn" class="sub-bar-link">Đăng xuất</a>
</div>

<script>
(function() {
    function highlightActiveLink() {
        const currentPath = window.location.pathname.split('/').pop();
        const currentHash = window.location.hash;
        
        document.querySelectorAll('.sub-bar-link').forEach(link => {
            link.classList.remove('active');
            const href = link.getAttribute('data-href');
            if (!href) return;
            
            if (href === 'admin.php' && currentPath === 'admin.php') {
                link.classList.add('active');
            }
            else if (href === 'huong-dan.php' && currentPath === 'huong-dan.php') {
                link.classList.add('active');
            }
            else if (currentPath === 'nhap-nang-suat.php') {
                if (href.includes('#')) {
                    const hrefHash = href.split('#')[1];
                    const currentHashVal = currentHash.replace('#', '');
                    
                    if ((currentHashVal === hrefHash) ||
                        (currentHashVal === '' && hrefHash === '/nhap-bao-cao') ||
                        (currentHashVal.startsWith('/lich-su') && hrefHash === '/lich-su')) {
                        link.classList.add('active');
                    }
                }
            }
        });
    }

    highlightActiveLink();
    window.addEventListener('hashchange', highlightActiveLink);
})();
</script>
<script type="module">
import realtimeService from '/baonangsuat/assets/js/modules/realtime-service.js';
realtimeService.init(<?php echo time(); ?>);
realtimeService.startClock('server-clock');
</script>
<?php endif; ?>
</div>

<?php include __DIR__ . '/components/toast.php'; ?>
