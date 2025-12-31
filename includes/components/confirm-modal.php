<style>
    .bg-navbar-theme {
        background-color: #143583 !important;
    }
    .bg-navbar-theme:hover {
        background-color: #0e255c !important;
    }
    .border-navbar-theme {
        border-color: #143583 !important;
    }
</style>
<div id="confirmModal" class="modal hidden fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" onclick="closeModal('confirmModal')">
    <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-sm p-0 overflow-hidden" onclick="event.stopPropagation()">
        <div class="px-6 py-4 border-b border-navbar-theme bg-navbar-theme flex justify-between items-center">
            <h2 id="confirmModalTitle" class="text-xl font-bold text-white m-0">Xác nhận</h2>
            <button type="button" class="w-8 h-8 flex items-center justify-center bg-white/20 hover:bg-white/30 rounded text-white transition-colors" onclick="closeModal('confirmModal')">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <div class="p-6">
            <p id="confirmMessage" class="text-gray-600 text-base leading-relaxed"></p>
            
            <div class="modal-actions flex justify-end gap-3 pt-6 mt-2">
                <button type="button" class="btn px-4 py-2 rounded-lg bg-gray-500 hover:bg-gray-700 text-white transition-colors font-medium" onclick="closeModal('confirmModal')">Hủy</button>
                <button type="button" id="confirmBtn" class="btn px-4 py-2 rounded-lg bg-navbar-theme text-white shadow-md hover:shadow-lg transition-all font-medium">Xác nhận</button>
            </div>
        </div>
    </div>
</div>
