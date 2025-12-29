<?php
$navTitle = $navTitle ?? 'HỆ THỐNG NHẬP NĂNG SUẤT';
$showAddBtn = $showAddBtn ?? false;
$addBtnUrl = $addBtnUrl ?? '#';
$addBtnId = $addBtnId ?? '';
$showHomeBtn = $showHomeBtn ?? true;
?>
<style>
.navbar-desktop-table {
    display: table;
    width: 100%;
    margin-bottom: 0;
}
.navbar-mobile-container {
    display: none;
}
@media (max-width: 768px) {
    .navbar-desktop-table {
        display: none;
    }
    .navbar-mobile-container {
        display: block;
    }
}
</style>

<table width="100%" class="navbar-desktop-table">
    <tr style="text-align:center;color:white;">
        <td bgcolor="143583" style="text-align:center;border-right-color:#143583;width:60px;">
            <?php if ($showHomeBtn): ?>
            <a href="./"><img width="45px" src="img/logoht.png"/></a>
            <?php else: ?>
            <img width="45px" src="img/logoht.png"/>
            <?php endif; ?>
        </td>
        <td colspan="6" bgcolor="143583" style="font-size:2em;font-weight:bold;text-align:center;line-height: 1.0em;padding:10px 5px;">
            <?php echo htmlspecialchars($navTitle); ?>
        </td>
        <td bgcolor="143583" style="width:60px;">
            <?php if ($showAddBtn): ?>
            <a href="<?php echo htmlspecialchars($addBtnUrl); ?>"<?php echo $addBtnId ? ' id="' . htmlspecialchars($addBtnId) . '"' : ''; ?>><img style="border-radius:5px;" src="img/add.png" width="55px"/></a>
            <?php endif; ?>
        </td>
    </tr>
</table>

<div class="navbar-mobile-container">
    <div style="text-align:center; background:#143583; color:white; padding:15px; border-radius:8px; margin-bottom:16px;">
        <div style="display:flex; align-items:center; justify-content:space-between;">
            <?php if ($showHomeBtn): ?>
            <a href="./"><img width="35px" src="img/logoht.png" /></a>
            <?php else: ?>
            <img width="35px" src="img/logoht.png" />
            <?php endif; ?>
            <div style="flex:1; font-size:1.2em; font-weight:bold; margin:0 10px;">
                <?php echo htmlspecialchars($navTitle); ?>
            </div>
            <div>
                <?php if ($showAddBtn): ?>
                <a href="<?php echo htmlspecialchars($addBtnUrl); ?>"<?php echo $addBtnId ? ' class="' . htmlspecialchars($addBtnId) . '-mobile"' : ''; ?>><img style="border-radius:5px;" src="img/add.png" width="30px" /></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
