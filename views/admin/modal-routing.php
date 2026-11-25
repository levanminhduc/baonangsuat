<!-- Routing Modal -->
<div id="routingModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" data-action="close-modal" data-target="routingModal">
    <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-lg p-0 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h2 id="routingModalTitle" class="text-xl font-bold text-gray-800 m-0">Thêm Công đoạn vào Routing</h2>
            <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" data-action="close-modal" data-target="routingModal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="routingForm" class="p-6 space-y-4">
            <input type="hidden" id="routingId" name="id">
            <input type="hidden" id="routingMaHangId" name="ma_hang_id">
            <div class="form-group space-y-1" id="routingCongDoanGroup">
                <label for="routingCongDoanSelect" class="block text-sm font-medium text-gray-700">Công đoạn</label>
                <select id="routingCongDoanSelect" name="cong_doan_id" required class="custom-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none bg-white">
                    <option value="">-- Chọn công đoạn --</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group space-y-1">
                    <label for="routingThuTu" class="block text-sm font-medium text-gray-700">Thứ tự</label>
                    <input type="number" id="routingThuTu" name="thu_tu" value="1" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                </div>
                <div class="form-group space-y-1">
                    <label for="routingLineSelect" class="block text-sm font-medium text-gray-700">LINE (tùy chọn)</label>
                    <select id="routingLineSelect" name="line_id" class="custom-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none bg-white">
                        <option value="">-- Tất cả LINE --</option>
                    </select>
                </div>
            </div>
            <div class="flex flex-col space-y-2">
                <div class="form-group">
                    <div class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="routingBatBuoc" name="bat_buoc" checked class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                        <label for="routingBatBuoc" class="text-sm font-medium text-gray-700 cursor-pointer select-none">Bắt buộc</label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="routingTinhLuyKe" name="la_cong_doan_tinh_luy_ke" class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                        <label for="routingTinhLuyKe" class="text-sm font-medium text-gray-700 cursor-pointer select-none">Tính lũy kế</label>
                    </div>
                </div>
            </div>
            <div class="form-group space-y-1">
                <label for="routingGhiChu" class="block text-sm font-medium text-gray-700">Ghi chú</label>
                <input type="text" id="routingGhiChu" name="ghi_chu" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
            </div>
            <div class="modal-actions flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" data-action="close-modal" data-target="routingModal">Hủy</button>
                <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">Lưu</button>
            </div>
        </form>
    </div>
</div>
