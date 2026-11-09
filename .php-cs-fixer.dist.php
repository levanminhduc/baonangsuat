<?php

// Theo cấu hình nhansuhtdb nhưng bỏ hai rule risky (declare_strict_types, strict_comparison)
// vì chúng đổi hành vi so sánh / ép kiểu. Thêm lại khi có test bao phủ.
$dirs = array_filter(
    [__DIR__ . '/app', __DIR__ . '/bootstrap', __DIR__ . '/config'],
    'is_dir'
);

$finder = PhpCsFixer\Finder::create()
    ->in($dirs)
    ->name('*.php')
    // Router API cũ giữ nguyên định dạng cho tới khi bị xoá (ticket 07).
    ->exclude(['Legacy']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'no_superfluous_phpdoc_tags' => true,
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
        'no_empty_statement' => true,
    ])
    ->setFinder($finder);
