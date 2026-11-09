<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\HistoryService;

// Quyền xem lịch sử kiểm ở middleware nhóm `history`.
class BaoCaoHistoryController
{
    public function index(Request $request)
    {
        $session = Auth::getSession();
        $filters = [
            'ngay_tu' => $request->query('ngay_tu'),
            'ngay_den' => $request->query('ngay_den'),
            'ca_id' => $request->hasQuery('ca_id') ? intval($request->query('ca_id')) : null,
            'ma_hang_id' => $request->hasQuery('ma_hang_id') ? intval($request->query('ma_hang_id')) : null,
            'trang_thai' => $request->query('trang_thai'),
            'page' => $request->hasQuery('page') ? intval($request->query('page')) : 1,
            'page_size' => $request->hasQuery('page_size') ? intval($request->query('page_size')) : 20,
        ];

        if (Auth::checkRole(['admin'])) {
            $filters['line_id'] = $request->hasQuery('line_id') ? intval($request->query('line_id')) : null;
        } else {
            $filters['line_id'] = $session['line_id'];
        }

        $result = (new HistoryService())->getReportList($filters);

        return new JsonResponse(['success' => true, 'data' => $result['data'], 'pagination' => $result['pagination']]);
    }

    public function show(Request $request)
    {
        $reportId = intval($request->param('id'));
        $session = Auth::getSession();
        $historyService = new HistoryService();
        $reportLineId = $historyService->getReportLineId($reportId);

        if ($reportLineId === null) {
            return JsonResponse::error('Báo cáo không tồn tại', 404);
        }

        if (!Auth::checkRole(['admin']) && $reportLineId !== $session['line_id']) {
            return JsonResponse::error('Không có quyền xem báo cáo này', 403);
        }

        $detail = $historyService->getReportDetail($reportId);
        if (!$detail) {
            return JsonResponse::error('Báo cáo không tồn tại', 404);
        }

        return new JsonResponse(['success' => true, 'data' => $detail]);
    }
}
