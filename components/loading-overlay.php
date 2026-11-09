<div id="loadingOverlay" class="fixed inset-0 z-[100] bg-gray-900/50 backdrop-blur-sm hidden flex items-center justify-center transition-all duration-300 opacity-0" style="pointer-events: none;">
    <div class="bg-white p-8 rounded-2xl shadow-2xl flex flex-col items-center gap-5 transform scale-95 transition-all duration-300">
        <div class="relative w-16 h-16">
            <div class="absolute inset-0 border-[6px] border-gray-100 rounded-full"></div>
            <div class="absolute inset-0 border-[6px] border-primary border-t-transparent rounded-full animate-spin"></div>
            
            <!-- Logo icon centered (optional) -->
            <div class="absolute inset-0 flex items-center justify-center">
                <svg class="w-6 h-6 text-primary opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
        </div>
        <div class="flex flex-col items-center gap-1">
            <span class="text-gray-800 font-bold text-lg tracking-wide">Đang xử lý</span>
            <span class="text-gray-500 text-sm">Vui lòng đợi trong giây lát...</span>
        </div>
    </div>
</div>

<script>
    // Include minimal inline script to handle visibility toggle to avoid FOUC
    // Full logic will be in admin.js
</script>