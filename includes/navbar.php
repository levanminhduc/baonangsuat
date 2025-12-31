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
    
    
    <span class="sub-bar-separator">|</span>
    <a href="#" id="logoutBtn" class="sub-bar-link">Đăng xuất</a>
</div>

<script>
(function() {
    let h = <?php echo intval($serverH); ?>;
    let m = <?php echo intval($serverM); ?>;
    let s = <?php echo intval($serverS); ?>;
    
    function pad(num) {
        return num.toString().padStart(2, '0');
    }
    
    function updateClock() {
        s++;
        if (s >= 60) {
            s = 0;
            m++;
            if (m >= 60) {
                m = 0;
                h++;
                if (h >= 24) {
                    h = 0;
                }
            }
        }
        
        const timeStr = `${pad(h)}:${pad(m)}:${pad(s)}`;
        
        const clockEl = document.getElementById('server-clock');
        if (clockEl) clockEl.textContent = `[${timeStr}]`;
    }
    
    setInterval(updateClock, 1000);
})();
</script>
<?php endif; ?>
