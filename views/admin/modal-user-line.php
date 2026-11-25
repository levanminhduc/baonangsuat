<!-- User Line Modal -->
<div id="userLineModal" class="modal hidden fixed inset-0 z-[1000] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" data-action="close-modal" data-target="userLineModal">
    <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-md p-0 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800 m-0">Thêm User-LINE Mapping</h2>
            <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" data-action="close-modal" data-target="userLineModal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="userLineForm" class="p-6 space-y-4">
            <div class="form-group space-y-1">
                <label for="userSelect" class="block text-sm font-medium text-gray-700">Chọn User</label>
                <select id="userSelect" name="ma_nv" required class="custom-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none bg-white">
                    <option value="">-- Chọn User --</option>
                </select>
                <input type="text" id="userSearchInput" placeholder="Tìm kiếm user..." class="w-full mt-2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none text-sm">
            </div>
            <div class="form-group space-y-1">
                <label for="lineSelect" class="block text-sm font-medium text-gray-700">Chọn LINE</label>
                <select id="lineSelect" name="line_id" required class="custom-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none bg-white">
                    <option value="">-- Chọn LINE --</option>
                </select>
            </div>
            <div class="modal-actions flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" data-action="close-modal" data-target="userLineModal">Hủy</button>
                <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">Thêm</button>
            </div>
        </form>
    </div>
</div>
