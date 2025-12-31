<?php
/**
 * Component: Line Preset Table
 * Hiển thị danh sách LINE đã gán vào Preset Mốc Giờ
 * 
 * @param array $assignedLines Danh sách LINE đã gán (từ API/Service)
 * @param array $unassignedLines Danh sách LINE chưa gán (để hiển thị modal thêm)
 * @param bool $readOnly Chế độ chỉ xem (không edit/delete)
 */

// Đảm bảo biến đầu vào tồn tại
$assignedLines = $assignedLines ?? [];
$unassignedLines = $unassignedLines ?? [];
$readOnly = $readOnly ?? false;
?>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" id="line-preset-table-container">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Danh sách LINE áp dụng
                <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 ml-2">
                    <?php echo count($assignedLines); ?>
                </span>
            </h3>
            <p class="text-sm text-gray-500 mt-1">Các LINE này sẽ sử dụng mốc giờ trong Preset hiện tại</p>
        </div>
        
        <?php if (!$readOnly): ?>
        <button onclick="openAddLineModal()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Thêm LINE
        </button>
        <?php endif; ?>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                        #
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Tên LINE
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Mã LINE
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Trạng thái
                    </th>
                    <?php if (!$readOnly): ?>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                        Thao tác
                    </th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($assignedLines)): ?>
                <tr>
                    <td colspan="<?php echo $readOnly ? 4 : 5; ?>" class="px-6 py-10 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <p class="text-base font-medium">Chưa có LINE nào được gán</p>
                            <p class="text-sm mt-1">Các LINE chưa gán sẽ sử dụng Preset mặc định của Ca</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($assignedLines as $index => $line): ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-150 group">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $index + 1; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($line['ten_line']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 font-mono">
                                <?php echo htmlspecialchars($line['ma_line']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                Đang áp dụng
                            </span>
                        </td>
                        <?php if (!$readOnly): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="removeLine(<?php echo $line['line_id']; ?>, '<?php echo htmlspecialchars($line['ten_line']); ?>')" 
                                    class="text-red-400 hover:text-red-600 transition-colors duration-200 opacity-0 group-hover:opacity-100 focus:opacity-100"
                                    title="Gỡ bỏ khỏi preset này">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Footer/Pagination info if needed -->
    <?php if (!empty($assignedLines)): ?>
    <div class="bg-gray-50 px-6 py-3 border-t border-gray-200">
        <p class="text-xs text-gray-500 italic">
            * Các LINE này sẽ được ưu tiên sử dụng preset này thay vì preset mặc định.
        </p>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Thêm LINE -->
<?php if (!$readOnly): ?>
<div id="add-line-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeAddLineModal()"></div>

        <!-- Modal Panel -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Thêm LINE vào Preset
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 mb-4">
                                Chọn các LINE bạn muốn áp dụng bộ mốc giờ này. Các LINE đã có preset khác trong ca này sẽ được chuyển sang preset này.
                            </p>
                            
                            <?php if (empty($unassignedLines)): ?>
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-yellow-700">
                                                Không còn LINE nào khả dụng để thêm.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-md">
                                    <div class="divide-y divide-gray-200">
                                        <?php foreach ($unassignedLines as $line): ?>
                                        <label class="flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer">
                                            <input type="checkbox" name="selected_lines[]" value="<?php echo $line['id']; ?>" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                            <span class="ml-3 block text-sm font-medium text-gray-700">
                                                <?php echo htmlspecialchars($line['ten_line']); ?> 
                                                <span class="text-gray-400 font-normal">(<?php echo htmlspecialchars($line['ma_line']); ?>)</span>
                                            </span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="mt-2 flex justify-between items-center text-xs text-gray-500">
                                    <span>Đã chọn: <span id="selected-count" class="font-bold text-indigo-600">0</span> LINE</span>
                                    <button type="button" onclick="selectAllLines()" class="text-indigo-600 hover:text-indigo-800">Chọn tất cả</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="submitAddLines()" <?php echo empty($unassignedLines) ? 'disabled' : ''; ?> class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    Thêm vào Preset
                </button>
                <button type="button" onclick="closeAddLineModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Hủy bỏ
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Modal functions
    function openAddLineModal() {
        document.getElementById('add-line-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeAddLineModal() {
        document.getElementById('add-line-modal').classList.add('hidden');
        document.body.style.overflow = '';
        // Reset selection
        document.querySelectorAll('input[name="selected_lines[]"]').forEach(cb => cb.checked = false);
        updateSelectedCount();
    }

    // Selection logic
    function updateSelectedCount() {
        const count = document.querySelectorAll('input[name="selected_lines[]"]:checked').length;
        const el = document.getElementById('selected-count');
        if (el) el.innerText = count;
    }

    function selectAllLines() {
        const checkboxes = document.querySelectorAll('input[name="selected_lines[]"]');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
        updateSelectedCount();
    }
    
    // Add event listeners for checkboxes
    document.querySelectorAll('input[name="selected_lines[]"]').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    // API interactions (Placeholders - to be implemented by parent page)
    function submitAddLines() {
        const selected = Array.from(document.querySelectorAll('input[name="selected_lines[]"]:checked'))
            .map(cb => cb.value);
            
        if (selected.length === 0) {
            alert('Vui lòng chọn ít nhất một LINE');
            return;
        }

        if (typeof window.onAddLines === 'function') {
            window.onAddLines(selected);
        } else {
            console.warn('window.onAddLines is not defined');
        }
    }

    function removeLine(lineId, lineName) {
        if (confirm(`Bạn có chắc chắn muốn gỡ bỏ LINE "${lineName}" khỏi preset này?\nLINE này sẽ quay về sử dụng preset mặc định của Ca.`)) {
            if (typeof window.onRemoveLine === 'function') {
                window.onRemoveLine(lineId);
            } else {
                console.warn('window.onRemoveLine is not defined');
            }
        }
    }
</script>
<?php endif; ?>
