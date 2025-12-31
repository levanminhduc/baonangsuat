<?php
$name = $name ?? '';
$id = $id ?? $name;
$label = $label ?? '';
$value = $value ?? '';
$options = $options ?? []; // Array of ['value' => '...', 'label' => '...'] or plain associative array
$placeholder = $placeholder ?? 'Chọn một tùy chọn';
$required = $required ?? false;
$disabled = $disabled ?? false;
$error = $error ?? '';
$helperText = $helperText ?? '';
$class = $class ?? '';
$multiple = $multiple ?? false;

$baseSelectClasses = "block w-full rounded-md shadow-sm sm:text-sm transition-colors duration-200 focus:ring-opacity-50";

if ($error) {
    $borderClasses = "border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500";
} else {
    $borderClasses = "border-gray-300 focus:border-[#143583] focus:ring-[#143583]";
}

if ($disabled) {
    $stateClasses = "bg-gray-100 cursor-not-allowed opacity-75";
} else {
    $stateClasses = "bg-white";
}

$finalSelectClass = "{$baseSelectClasses} {$borderClasses} {$stateClasses} {$class}";

$attributes = [];
if ($required) $attributes[] = 'required';
if ($disabled) $attributes[] = 'disabled';
if ($multiple) $attributes[] = 'multiple';

?>

<div class="form-group mb-4">
    <?php if ($label): ?>
        <label for="<?= htmlspecialchars($id) ?>" class="block text-sm font-medium text-gray-700 mb-1">
            <?= htmlspecialchars($label) ?>
            <?php if ($required): ?>
                <span class="text-red-500">*</span>
            <?php endif; ?>
        </label>
    <?php endif; ?>

    <div class="relative">
        <select name="<?= htmlspecialchars($name) ?><?= $multiple ? '[]' : '' ?>"
                id="<?= htmlspecialchars($id) ?>"
                class="<?= $finalSelectClass ?>"
                <?= implode(' ', $attributes) ?>>
            
            <?php if (!$multiple && $placeholder): ?>
                <option value="" disabled <?= empty($value) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($placeholder) ?>
                </option>
            <?php endif; ?>

            <?php foreach ($options as $optValue => $optLabel): ?>
                <?php 
                    // Handle both simple associative array and array of arrays structure
                    if (is_array($optLabel) && isset($optLabel['value']) && isset($optLabel['label'])) {
                        $val = $optLabel['value'];
                        $txt = $optLabel['label'];
                    } else {
                        $val = $optValue;
                        $txt = $optLabel;
                    }
                    
                    $isSelected = false;
                    if (is_array($value)) {
                        $isSelected = in_array($val, $value);
                    } else {
                        // Loose comparison for strings/numbers
                        $isSelected = ((string)$val === (string)$value);
                    }
                ?>
                <option value="<?= htmlspecialchars($val) ?>" <?= $isSelected ? 'selected' : '' ?>>
                    <?= htmlspecialchars($txt) ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <?php if ($error): ?>
            <div class="absolute inset-y-0 right-8 pr-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
        <p class="mt-1 text-sm text-red-600" id="<?= htmlspecialchars($id) ?>-error">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php elseif ($helperText): ?>
        <p class="mt-1 text-sm text-gray-500" id="<?= htmlspecialchars($id) ?>-description">
            <?= htmlspecialchars($helperText) ?>
        </p>
    <?php endif; ?>
</div>
