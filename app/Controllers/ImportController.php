<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\ImportService;

// Quyền import kiểm ở middleware nhóm `import`; CSRF khai trong route.
class ImportController
{
    public function preview(Request $request)
    {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File vượt quá kích thước cho phép',
                UPLOAD_ERR_FORM_SIZE => 'File vượt quá kích thước cho phép',
                UPLOAD_ERR_PARTIAL => 'File chỉ được upload một phần',
                UPLOAD_ERR_NO_FILE => 'Không có file được upload',
                UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm',
                UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file',
                UPLOAD_ERR_EXTENSION => 'Upload bị chặn bởi extension',
            ];
            $errorCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
            $message = $errorMessages[$errorCode] ?? 'Lỗi upload file';

            return JsonResponse::error($message, 400, ['error_code' => 'NO_FILE_UPLOADED']);
        }

        $file = $_FILES['file'];
        $allowedMimes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
        ];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedMimes)) {
            @unlink($file['tmp_name']);

            return JsonResponse::error('File không đúng định dạng Excel', 400, ['error_code' => 'INVALID_MIME_TYPE']);
        }

        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            @unlink($file['tmp_name']);

            return JsonResponse::error('File vượt quá 10MB', 400, ['error_code' => 'FILE_TOO_LARGE']);
        }

        $importService = new ImportService();
        try {
            $result = $importService->preview($file['tmp_name']);
        } finally {
            @unlink($file['tmp_name']);
        }

        return new JsonResponse($result);
    }

    public function confirm(Request $request)
    {
        $input = $request->jsonBody();
        $maHangList = $input['ma_hang_list'] ?? [];
        $fileName = $input['file_name'] ?? '';
        $fileSize = intval($input['file_size'] ?? 0);
        $previewStats = $input['preview_stats'] ?? [];
        $previewErrors = $input['preview_errors'] ?? [];

        if (empty($maHangList)) {
            return JsonResponse::error('Danh sách mã hàng không được rỗng', 400, ['error_code' => 'VALIDATION_FAILED']);
        }

        $importService = new ImportService();

        // Dữ liệu preview để ghi lịch sử import
        $importService->setPreviewData([
            'stats' => $previewStats,
            'errors' => $previewErrors,
            'data' => $maHangList,
        ]);

        $session = Auth::getSession();
        $importedBy = $session['ma_nv'] ?? '';

        $result = $importService->confirm(
            $maHangList,
            $input['acknowledge_deletion'] ?? false,
            $fileName,
            $fileSize,
            $importedBy,
        );

        return new JsonResponse($result);
    }
}
