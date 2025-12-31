<?php
$status = $status ?? 'default'; // draft, pending, approved, locked, rejected, success, warning, danger, info
$label = $label ?? ucfirst($status);
$class = $class ?? '';
$size = $size ?? 'md'; // sm, md

$baseClasses = 'inline-flex items-center justify-center font-medium rounded-full';

$variantClasses = match ($status) {
    'approved', 'success' => 'bg-[#E8F5E9] text-[#2E7D32]', // Green-100 bg, Green-800 text
    'pending', 'warning' => 'bg-[#FFF3E0] text-[#EF6C00]', // Orange-100 bg, Orange-800 text
    'locked', 'danger', 'rejected' => 'bg-[#FFEBEE] text-[#C62828]', // Red-100 bg, Red-800 text
    'info' => 'bg-[#E3F2FD] text-[#1565C0]', // Blue-100 bg, Blue-800 text
    'primary' => 'bg-[#E8EAF6] text-[#143583]', // Primary light bg, Primary text
    default => 'bg-gray-100 text-gray-800',
};

$sizeClasses = match ($size) {
    'sm' => 'px-2.5 py-0.5 text-xs',
    default => 'px-3 py-1 text-sm',
};

$finalClass = "{$baseClasses} {$variantClasses} {$sizeClasses} {$class}";
?>

<span class="<?= $finalClass ?>">
    <?= htmlspecialchars($label) ?>
</span>
