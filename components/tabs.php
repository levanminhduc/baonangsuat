<?php
// Expected structure for $tabs:
// [
//     ['id' => 'tab1', 'label' => 'Tab 1', 'content' => 'Content 1', 'active' => true],
//     ['id' => 'tab2', 'label' => 'Tab 2', 'content' => 'Content 2'],
// ]
$tabs = $tabs ?? [];
$id = $id ?? 'tabs-' . uniqid();
$class = $class ?? '';

// Find active tab or default to first
$hasActive = false;
foreach ($tabs as $tab) {
    if (!empty($tab['active'])) {
        $hasActive = true;
        break;
    }
}
if (!$hasActive && !empty($tabs)) {
    $tabs[0]['active'] = true;
}
?>

<div class="<?= $class ?>" id="<?= $id ?>">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <?php foreach ($tabs as $index => $tab): 
                $isActive = !empty($tab['active']);
                $tabId = $tab['id'] ?? "tab-{$index}";
                $contentId = "content-{$tabId}";
                
                $linkClass = $isActive 
                    ? 'border-[#143583] text-[#143583]' 
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
            ?>
                <button 
                    onclick="switchTab('<?= $id ?>', '<?= $contentId ?>', this)"
                    class="<?= $linkClass ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors focus:outline-none"
                    aria-current="<?= $isActive ? 'page' : 'false' ?>"
                >
                    <?= htmlspecialchars($tab['label']) ?>
                </button>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="mt-4">
        <?php foreach ($tabs as $index => $tab): 
            $isActive = !empty($tab['active']);
            $tabId = $tab['id'] ?? "tab-{$index}";
            $contentId = "content-{$tabId}";
        ?>
            <div id="<?= $contentId ?>" class="tab-content <?= $isActive ? '' : 'hidden' ?>">
                <?= $tab['content'] ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
if (typeof switchTab !== 'function') {
    function switchTab(containerId, contentId, button) {
        const container = document.getElementById(containerId);
        if (!container) return;

        // Hide all contents in this container
        const contents = container.querySelectorAll('.tab-content');
        contents.forEach(el => el.classList.add('hidden'));

        // Reset all buttons in this container
        const buttons = container.querySelectorAll('nav button');
        buttons.forEach(btn => {
            btn.classList.remove('border-[#143583]', 'text-[#143583]');
            btn.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            btn.setAttribute('aria-current', 'false');
        });

        // Show selected content
        document.getElementById(contentId).classList.remove('hidden');

        // Activate selected button
        button.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
        button.classList.add('border-[#143583]', 'text-[#143583]');
        button.setAttribute('aria-current', 'page');
    }
}
</script>
