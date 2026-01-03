<?php
$id = $id ?? '';
$title = $title ?? '';
$content = $content ?? '';
$footer = $footer ?? '';
$size = $size ?? 'md';
$variant = $variant ?? 'default';
$closable = $closable ?? true;
$escapeClose = $escapeClose ?? true;
$backdropClose = $backdropClose ?? true;
$zIndex = $zIndex ?? 'z-50';

if ($variant === 'loading') {
    $closable = false;
    $escapeClose = false;
    $backdropClose = false;
}

$sizeClasses = match ($size) {
    'sm' => 'sm:max-w-sm',
    'lg' => 'sm:max-w-2xl',
    'xl' => 'sm:max-w-4xl',
    'full' => 'sm:max-w-full sm:m-6',
    default => 'sm:max-w-lg',
};

$dataAttributes = [
    'data-modal' => 'true',
    'data-close-on-esc' => $escapeClose ? 'true' : 'false',
    'data-close-on-backdrop' => $backdropClose ? 'true' : 'false',
];

if ($variant === 'loading') {
    $dataAttributes['data-loading'] = 'true';
}

$dataString = implode(' ', array_map(
    fn($k, $v) => "{$k}=\"{$v}\"",
    array_keys($dataAttributes),
    $dataAttributes
));
?>

<div id="<?= htmlspecialchars($id) ?>" 
     class="modal hidden fixed inset-0 <?= $zIndex ?> overflow-y-auto" 
     aria-labelledby="<?= htmlspecialchars($id) ?>-title" 
     role="dialog" 
     aria-modal="true"
     <?= $dataString ?>>
    
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" 
             aria-hidden="true"
             <?php if ($backdropClose): ?>data-modal-close<?php endif; ?>></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block relative z-10 align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl border border-gray-200 transform transition-all sm:my-8 sm:align-middle w-full <?= $sizeClasses ?>"
             data-modal-panel
             role="document">
            
            <?php if ($title || $closable): ?>
            <div class="bg-white px-4 py-4 sm:px-6 border-b border-gray-200 flex justify-between items-center">
                <?php if ($title): ?>
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="<?= htmlspecialchars($id) ?>-title">
                    <?= htmlspecialchars($title) ?>
                </h3>
                <?php endif; ?>
                
                <?php if ($closable): ?>
                <button type="button" 
                        class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        data-modal-close>
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="bg-white p-4 sm:p-6">
                <?= $content ?>
            </div>

            <?php if ($footer): ?>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:py-4 sm:flex sm:flex-row-reverse gap-3 border-t border-gray-200">
                <?= $footer ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
