<?php
$type = $type ?? 'button';
$variant = $variant ?? 'primary';
$size = $size ?? 'md';
$disabled = $disabled ?? false;
$id = $id ?? '';
$class = $class ?? '';
$onClick = $onClick ?? '';
$label = $label ?? '';

$baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

$variantClasses = match ($variant) {
    'primary' => 'bg-[#143583] hover:bg-[#0e2a6e] text-white focus:ring-[#143583]',
    'success' => 'bg-[#4CAF50] hover:bg-[#43a047] text-white focus:ring-[#4CAF50]',
    'warning' => 'bg-[#ff9800] hover:bg-[#f57c00] text-white focus:ring-[#ff9800]',
    'danger' => 'bg-[#f44336] hover:bg-[#d32f2f] text-white focus:ring-[#f44336]',
    'secondary' => 'bg-gray-500 hover:bg-gray-600 text-white focus:ring-gray-500',
    default => 'bg-[#143583] hover:bg-[#0e2a6e] text-white focus:ring-[#143583]',
};

$sizeClasses = match ($size) {
    'sm' => 'px-3 py-1.5 text-sm',
    'lg' => 'px-6 py-3 text-lg',
    default => 'px-4 py-2 text-base',
};

$attributes = [];
if ($id) $attributes[] = "id=\"{$id}\"";
if ($onClick) $attributes[] = "onclick=\"{$onClick}\"";
if ($disabled) $attributes[] = "disabled";

$finalClass = "{$baseClasses} {$variantClasses} {$sizeClasses} {$class}";
?>

<button type="<?= htmlspecialchars($type) ?>" class="<?= $finalClass ?>" <?= implode(' ', $attributes) ?>>
    <?= htmlspecialchars($label) ?>
</button>
