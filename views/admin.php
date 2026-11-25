<?php
$page = [
    'title' => 'Quản lý Admin - Hệ thống Năng suất',
    'navTitle' => 'Quản trị',
    'navActive' => 'admin',
    'showAddBtn' => false,
    'showHomeBtn' => true,
    'modules' => ['assets/js/admin.js'],
];
?>
<div class="app-container">
    <div class="admin-content">
        <?php include __DIR__ . '/admin/tab-lines.php'; ?>
        <?php include __DIR__ . '/admin/tab-user-lines.php'; ?>
        <?php include __DIR__ . '/admin/tab-permissions.php'; ?>
        <?php include __DIR__ . '/admin/tab-bulk-create.php'; ?>
        <?php include __DIR__ . '/admin/tab-ma-hang.php'; ?>
        <?php include __DIR__ . '/admin/tab-cong-doan.php'; ?>
        <?php include __DIR__ . '/admin/tab-routing.php'; ?>
        <?php include __DIR__ . '/admin/tab-po-subart.php'; ?>
        <?php include __DIR__ . '/admin/tab-presets.php'; ?>
        <?php include __DIR__ . '/admin/tab-moc-gio.php'; ?>
        <?php include __DIR__ . '/admin/tab-import.php'; ?>
        <?php include __DIR__ . '/admin/tab-import-history.php'; ?>
    </div>
</div>
<?php include __DIR__ . '/admin/modal-line.php'; ?>
<?php include __DIR__ . '/admin/modal-user-line.php'; ?>
<?php include BNS_ROOT . '/components/confirm-modal.php'; ?>
<?php include BNS_ROOT . '/components/loading-overlay.php'; ?>
<?php include __DIR__ . '/admin/modal-ma-hang.php'; ?>
<?php include __DIR__ . '/admin/modal-cong-doan.php'; ?>
<?php include __DIR__ . '/admin/modal-routing.php'; ?>
<?php include __DIR__ . '/admin/modal-moc-gio.php'; ?>
<?php include __DIR__ . '/admin/modal-preset-detail.php'; ?>
<?php include __DIR__ . '/admin/modal-preset.php'; ?>
<?php include __DIR__ . '/admin/modal-assign-lines.php'; ?>
<?php include __DIR__ . '/admin/modal-copy-preset.php'; ?>
<?php include __DIR__ . '/admin/modal-preset-moc-gio.php'; ?>
<?php include __DIR__ . '/admin/modal-import-history-detail.php'; ?>
