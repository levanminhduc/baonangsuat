<?php

namespace App\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\ImportService;

// Chỉ admin (middleware nhóm `role:admin`).
class ImportHistoryController
{
    public function index(Request $request)
    {
        $page = intval($request->query('page', 1));
        $pageSize = intval($request->query('page_size', 20));

        $filters = [];
        foreach (['date_from', 'date_to', 'import_boi', 'trang_thai'] as $key) {
            if (!empty($request->query($key))) {
                $filters[$key] = $request->query($key);
            }
        }

        $result = (new ImportService())->getImportHistoryList($page, $pageSize, $filters);

        return new JsonResponse(['success' => true, 'data' => $result['data'], 'pagination' => $result['pagination']]);
    }

    public function show(Request $request)
    {
        $result = (new ImportService())->getImportHistoryDetail(intval($request->param('id')));
        if ($result === null) {
            return JsonResponse::error('Không tìm thấy lịch sử import', 404);
        }

        return new JsonResponse(['success' => true, 'data' => $result]);
    }
}
