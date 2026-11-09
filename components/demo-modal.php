<?php
// Mock $id for demo
$id = 'demoModal';
$title = 'Demo Modal with Offline CSS';
$closable = true;
$size = 'md';
$footer = '
    <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm" onclick="Modal.close(\'demoModal\')">
        Close
    </button>
    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="Modal.close(\'demoModal\')">
        Cancel
    </button>
';

ob_start();
?>
<div class="space-y-4">
    <p class="text-gray-700">
        This is a demo of the modal component using offline Tailwind CSS.
    </p>
    <ul class="list-disc pl-5 space-y-2 text-gray-600">
        <li>Backdrop blur effect</li>
        <li>Smooth transitions</li>
        <li>Responsive layout</li>
        <li>Accessible (ARIA support)</li>
    </ul>
</div>
<?php
$content = ob_get_clean();

include 'includes/components/modal-base.php';
?>
