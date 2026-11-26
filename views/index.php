<?php use App\Auth\Auth; ?>
<?php
$page = [
    'title' => 'Chọn LINE - Hệ thống Báo Năng Suất',
    'nav' => false,
    'modules' => ['assets/js/login.js'],
];
?>
<div class="absolute inset-0 flex items-center justify-center bg-linear-to-br from-[#5b73e8] to-[#44c4fa] p-2.5 max-[576px]:bg-none max-[576px]:bg-white max-[576px]:p-0">
    <div class="w-full max-w-[400px] overflow-hidden rounded-[10px] bg-white shadow-[0_5px_20px_rgba(0,0,0,0.1)] max-[576px]:min-h-screen max-[576px]:max-w-full max-[576px]:rounded-none max-[576px]:shadow-none">
        <div class="px-5 pb-5 pt-[30px] text-center">
            <div class="mb-[15px]">
                <img src="img/logoht.svg" alt="Hoa Tho Logo" width="100" height="100" class="mx-auto">
            </div>
            <h3 class="m-0 text-[1.3rem] font-semibold text-[#3d4465]">HỆ THỐNG BÁO NĂNG SUẤT</h3>
        </div>

        <div class="px-[30px] pb-5 text-center max-[576px]:px-5">
            <p class="mb-3 text-gray-700">Vui lòng chọn LINE làm việc</p>
            <a href="<?php echo htmlspecialchars(Auth::logoutUrl()); ?>" id="logoutLink" class="inline-flex items-center gap-1.5 text-[#5b73e8] hover:underline">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Đăng xuất
            </a>
        </div>

        <footer class="border-t border-[#eee] bg-[#f8f9fa] p-[15px] text-center">
            <p class="m-0 text-xs text-[#888]">© 2025 - HỆ THỐNG BÁO NĂNG SUẤT - CÔNG TY MAY HOÀ THỌ ĐIỆN BÀN</p>
        </footer>
    </div>
</div>

<div id="lineSelectModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" aria-hidden="true">
    <div class="w-full max-w-[500px] overflow-hidden rounded-[10px] bg-white shadow-[0_5px_15px_rgba(0,0,0,0.2)]" role="dialog" aria-modal="true" aria-labelledby="lineSelectTitle">
        <div class="border-b border-gray-200 px-4 py-3">
            <h5 id="lineSelectTitle" class="m-0 text-xl font-medium text-gray-800">Chọn LINE làm việc</h5>
        </div>
        <ul id="lineList" class="m-0 max-h-[300px] list-none overflow-y-auto p-0"></ul>
    </div>
</div>

<script id="allowedLines" type="application/json"><?php echo json_encode($allowedLines, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?></script>

