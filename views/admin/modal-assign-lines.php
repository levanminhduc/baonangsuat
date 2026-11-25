<!-- Assign Lines Modal (Multi-select) -->
<div id="assignLinesModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" data-action="close-modal" data-target="assignLinesModal">
    <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-2xl p-0 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center flex-shrink-0">
            <h2 id="assignLinesTitle" class="text-xl font-bold text-gray-800 m-0">Gán LINE vào Preset</h2>
            <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" data-action="close-modal" data-target="assignLinesModal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="assignLinesForm" class="flex flex-col flex-grow overflow-hidden">
            <input type="hidden" id="assignLinesPresetId" name="preset_id">
            
            <div class="p-4 border-b bg-gray-50 flex-shrink-0">
                <input type="text" id="assignLinesSearch" placeholder="Tìm kiếm LINE..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
            </div>

            <div class="p-6 overflow-y-auto flex-grow">
                <div id="unassignedLinesContainer" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <!-- Checkboxes populated by JS -->
                </div>
            </div>

            <div class="modal-actions flex justify-end gap-3 p-4 border-t border-gray-100 bg-gray-50 flex-shrink-0">
                <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" data-action="close-modal" data-target="assignLinesModal">Hủy</button>
                <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">Gán đã chọn</button>
            </div>
        </form>
    </div>
</div>
