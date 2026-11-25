<?php
// Khung chung của mọi trang. View khai `$page`:
//   title, bodyClass, navTitle, navActive, showAddBtn, addBtnUrl, addBtnId, showHomeBtn,
//   nav (false = không có navbar), scripts (JS thường), modules (JS type="module"), head / foot (HTML thô).
$asset = static function (string $path): string {
    $file = BNS_ROOT . '/public/' . $path;

    return htmlspecialchars($path . (is_file($file) ? '?v=' . filemtime($file) : ''));
};
$navTitle = $page['navTitle'] ?? null;
$navActive = $page['navActive'] ?? '';
$showAddBtn = $page['showAddBtn'] ?? false;
$addBtnUrl = $page['addBtnUrl'] ?? '#';
$addBtnId = $page['addBtnId'] ?? '';
$showHomeBtn = $page['showHomeBtn'] ?? true;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="bns-base" content="<?php echo htmlspecialchars($basePath); ?>">
    <title><?php echo htmlspecialchars($page['title'] ?? 'Hệ thống nhập năng suất'); ?></title>
    <link rel="stylesheet" href="<?php echo $asset('assets/tailwind/dist/app.css'); ?>">
<?php echo $page['head'] ?? ''; ?>
</head>
<body<?php echo empty($page['bodyClass']) ? '' : ' class="' . htmlspecialchars($page['bodyClass']) . '"'; ?>>
<?php if (($page['nav'] ?? true) !== false) {
    include BNS_ROOT . '/views/partials/navbar.php';
} ?>
<?php echo $content; ?>
<?php echo $page['foot'] ?? ''; ?>
<?php foreach ($page['scripts'] ?? [] as $src) { ?>
    <script src="<?php echo $asset($src); ?>"></script>
<?php } ?>
<?php foreach ($page['modules'] ?? [] as $src) { ?>
    <script type="module" src="<?php echo $asset($src); ?>"></script>
<?php } ?>
</body>
</html>
