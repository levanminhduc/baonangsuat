<!-- Ma Hang Modal -->
<div id="maHangModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" data-action="close-modal" data-target="maHangModal">
    <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-md p-0 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h2 id="maHangModalTitle" class="text-xl font-bold text-gray-800 m-0">Thêm Mã hàng mới</h2>
            <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" data-action="close-modal" data-target="maHangModal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="maHangForm" class="p-6 space-y-4">
            <input type="hidden" id="maHangId" name="id">
            <div class="form-group space-y-1">
                <label for="maHangCode" class="block text-sm font-medium text-gray-700">Mã hàng</label>
                <input type="text" id="maHangCode" name="ma_hang" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
            </div>
            <div class="form-group space-y-1">
                <label for="tenHang" class="block text-sm font-medium text-gray-700">Tên hàng</label>
                <input type="text" id="tenHang" name="ten_hang" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
            </div>
            <div class="form-group" id="maHangIsActiveGroup" style="display:none">
                <div class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="maHangIsActive" name="is_active" checked class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                    <label for="maHangIsActive" class="text-sm font-medium text-gray-700 cursor-pointer select-none">Đang hoạt động</label>
                </div>
            </div>
            <div class="modal-actions flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" data-action="close-modal" data-target="maHangModal">Hủy</button>
                <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">Lưu</button>
            </div>
        </form>
    </div>
</div>
