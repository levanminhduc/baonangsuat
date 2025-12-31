<?php
$header = $header ?? null;
$body = $body ?? '';
$footer = $footer ?? null;
$variant = $variant ?? 'default'; // default (shadow), bordered, flat
$class = $class ?? '';
$id = $id ?? '';
$noPadding = $noPadding ?? false; // Option to remove body padding

$baseClasses = 'bg-white rounded-lg overflow-hidden';

$variantClasses = match ($variant) {
    'bordered' => 'border border-gray-200',
    'flat' => '',
    default => 'shadow-md',
};

$attributes = [];
if ($id) $attributes[] = "id=\"{$id}\"";

$finalClass = "{$baseClasses} {$variantClasses} {$class}";
?>

<div class="<?= $finalClass ?>" <?= implode(' ', $attributes) ?>>
    <?php if ($header): ?>
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <?php if (strip_tags($header) === $header): ?>
                <h3 class="text-lg font-medium text-gray-900"><?= $header ?></h3>
            <?php else: ?>
                <?= $header ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="<?= $noPadding ? '' : 'p-6' ?>">
        <?= $body ?>
    </div>

    <?php if ($footer): ?>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <?= $footer ?>
        </div>
    <?php endif; ?>
</div>
