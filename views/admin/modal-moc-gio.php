<div id="mocGioModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" data-action="close-modal" data-target="mocGioModal">
    <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-lg p-0 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h2 id="mocGioModalTitle" class="text-xl font-bold text-gray-800 m-0">Thêm Mốc giờ mới</h2>
            <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" data-action="close-modal" data-target="mocGioModal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="mocGioForm" class="p-6 space-y-4">
            <input type="hidden" id="mocGioId" name="id">
            <input type="hidden" id="mocGioFormCaId" name="ca_id">
            <input type="hidden" id="mocGioFormLineId" name="line_id">
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group space-y-1">
                    <label for="mocGioGio" class="block text-sm font-medium text-gray-700">Giờ (HH:MM)</label>
                    <input type="time" id="mocGioGio" name="gio" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                </div>
                <div class="form-group space-y-1">
                    <label for="mocGioThuTu" class="block text-sm font-medium text-gray-700">Thứ tự</label>
                    <input type="number" id="mocGioThuTu" name="thu_tu" value="1" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                </div>
            </div>
            <div class="form-group space-y-1">
                <label for="mocGioPhutLuyKe" class="block text-sm font-medium text-gray-700">Số phút hiệu dụng lũy kế</label>
                <input type="number" id="mocGioPhutLuyKe" name="so_phut_hieu_dung_luy_ke" value="0" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                <p class="text-xs text-gray-500">Tổng số phút làm việc hiệu dụng tính đến mốc giờ này</p>
            </div>
            <div class="form-group" id="mocGioIsActiveGroup" style="display:none">
                <div class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="mocGioIsActive" name="is_active" checked class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                    <label for="mocGioIsActive" class="text-sm font-medium text-gray-700 cursor-pointer select-none">Đang hoạt động</label>
                </div>
            </div>
            <div class="modal-actions flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" data-action="close-modal" data-target="mocGioModal">Hủy</button>
                <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">Lưu</button>
            </div>
        </form>
    </div>
</div>
