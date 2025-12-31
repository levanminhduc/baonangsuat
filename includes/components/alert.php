<?php
$type = $type ?? 'info';
$message = $message ?? '';
$dismissible = $dismissible ?? false;
$id = $id ?? uniqid('alert_');
$class = $class ?? '';

$colors = match ($type) {
    'success' => [
        'bg' => 'bg-green-50',
        'text' => 'text-green-800',
        'border' => 'border-green-200',
        'icon' => 'text-green-400',
        'icon_path' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' // Check circle
    ],
    'warning' => [
        'bg' => 'bg-orange-50',
        'text' => 'text-orange-800',
        'border' => 'border-orange-200',
        'icon' => 'text-orange-400',
        'icon_path' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' // Exclamation triangle
    ],
    'error' => [
        'bg' => 'bg-red-50',
        'text' => 'text-red-800',
        'border' => 'border-red-200',
        'icon' => 'text-red-400',
        'icon_path' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z' // X circle
    ],
    default => [ // info
        'bg' => 'bg-blue-50',
        'text' => 'text-blue-800',
        'border' => 'border-blue-200',
        'icon' => 'text-blue-400',
        'icon_path' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' // Information circle
    ],
};

$baseClasses = "rounded-md p-4 mb-4 border flex gap-3";
$finalClass = "{$baseClasses} {$colors['bg']} {$colors['border']} {$class}";
?>

<div id="<?= htmlspecialchars($id) ?>" class="<?= $finalClass ?>" role="alert">
    <div class="flex-shrink-0">
        <svg class="h-5 w-5 <?= $colors['icon'] ?>" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="<?= $colors['icon_path'] ?>" clip-rule="evenodd" />
        </svg>
    </div>
    <div class="flex-1 text-sm <?= $colors['text'] ?>">
        <?= $message ?>
    </div>
    <?php if ($dismissible): ?>
        <div class="flex-shrink-0">
            <button type="button" 
                    onclick="document.getElementById('<?= htmlspecialchars($id) ?>').remove()"
                    class="inline-flex rounded-md p-1.5 <?= $colors['text'] ?> hover:bg-white hover:bg-opacity-20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-<?= str_replace('bg-', '', $colors['bg']) ?> focus:ring-<?= str_replace('text-', '', $colors['text']) ?>">
                <span class="sr-only">Dismiss</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                </svg>
            </button>
        </div>
    <?php endif; ?>
</div>
