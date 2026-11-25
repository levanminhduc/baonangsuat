<!-- Preset Moc Gio Modal (Reusing structure but specific IDs for Preset context) -->
<div id="presetMocGioModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" data-action="close-modal" data-target="presetMocGioModal">
    <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-lg p-0 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h2 id="presetMocGioModalTitle" class="text-xl font-bold text-gray-800 m-0">Thêm Mốc giờ vào Preset</h2>
            <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" data-action="close-modal" data-target="presetMocGioModal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="presetMocGioForm" class="p-6 space-y-4">
            <input type="hidden" id="presetMocGioId" name="id">
            <input type="hidden" id="presetMocGioPresetId" name="preset_id">
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group space-y-1">
                    <label for="presetMocGioGio" class="block text-sm font-medium text-gray-700">Giờ (HH:MM)</label>
                    <input type="time" id="presetMocGioGio" name="gio" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                </div>
                <div class="form-group space-y-1">
                    <label for="presetMocGioThuTu" class="block text-sm font-medium text-gray-700">Thứ tự</label>
                    <input type="number" id="presetMocGioThuTu" name="thu_tu" value="1" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                </div>
            </div>
            <div class="form-group space-y-1">
                <label for="presetMocGioPhutLuyKe" class="block text-sm font-medium text-gray-700">Số phút hiệu dụng lũy kế</label>
                <input type="number" id="presetMocGioPhutLuyKe" name="so_phut_hieu_dung_luy_ke" value="0" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
            </div>
            <div class="modal-actions flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" data-action="close-modal" data-target="presetMocGioModal">Hủy</button>
                <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">Lưu</button>
            </div>
        </form>
    </div>
</div>
