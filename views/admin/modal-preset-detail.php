<!-- Preset Detail Modal -->
<div id="presetDetailModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" data-action="close-modal" data-target="presetDetailModal">
    <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-2xl p-0 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center flex-shrink-0">
            <h2 id="presetDetailTitle" class="text-xl font-bold text-gray-800 m-0">Chi tiết Preset</h2>
            <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" data-action="close-modal" data-target="presetDetailModal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <div class="p-6 overflow-y-auto flex-grow">
            <div class="mb-6 border-b border-gray-100 pb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-700">Mốc giờ thiết lập</h3>
                </div>
                <div id="presetMocGioList" class="flex flex-wrap gap-2">
                    <!-- Mốc giờ sẽ được render ở đây -->
                </div>
            </div>

            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-700">Danh sách LINE áp dụng</h3>
                    <button type="button" class="btn btn-sm btn-primary" data-action="show-assign-lines">+ Gán thêm LINE</button>
                </div>
                <div id="presetAssignedLines" class="border rounded-lg overflow-hidden">
                    <!-- Table content populated by JS -->
                </div>
            </div>
        </div>

        <div class="modal-actions flex justify-end gap-3 p-4 border-t border-gray-100 bg-gray-50 flex-shrink-0">
            <button type="button" class="btn px-4 py-2 rounded-lg bg-gray-500 hover:bg-gray-700 text-white transition-colors font-medium" data-action="close-modal" data-target="presetDetailModal">Đóng</button>
        </div>
    </div>
</div>
