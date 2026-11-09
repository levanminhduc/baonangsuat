<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\ChartService;

// Đăng nhập kiểm ở middleware nhóm `auth`; quyền xem LINE kiểm theo tham số nên nằm ở đây.
class BieuDoController
{
    // GET /bieu-do/so-sanh — so sánh năng suất
    public function soSanh(Request $request)
    {
        $denied = $this->comparisonGuard($request, 0);
        if ($denied !== null) {
            return $denied;
        }

        return new JsonResponse((new ChartService())->getProductivityComparison(...$this->comparisonArgs($request, 0)));
    }

    // GET /bieu-do/ma-hang-list — mã hàng có báo cáo của một LINE trong ngày
    public function maHangList(Request $request)
    {
        $line_id = $request->hasQuery('line_id') ? intval($request->query('line_id')) : 0;
        $ngay = $request->query('ngay', date('Y-m-d'));

        if (!$line_id) {
            return JsonResponse::error('Thiếu tham số line_id', 400);
        }

        $denied = $this->lineGuard($line_id);
        if ($denied !== null) {
            return $denied;
        }

        return new JsonResponse(['success' => true, 'data' => (new ChartService())->getMaHangListForChart($line_id, $ngay)]);
    }

    // GET /bieu-do/lines — danh sách LINE đang hoạt động (admin)
    public function lines(Request $request)
    {
        if (!Auth::checkRole(['admin'])) {
            return JsonResponse::error('Không có quyền', 403);
        }

        return new JsonResponse(['success' => true, 'data' => (new ChartService())->getLineList()]);
    }

    // GET /bieu-do/ca-list — danh sách ca
    public function caList(Request $request)
    {
        return new JsonResponse(['success' => true, 'data' => (new ChartService())->getCaList()]);
    }

    // GET /bieu-do/so-sanh-chi-tiet — so sánh chi tiết theo công đoạn
    public function soSanhChiTiet(Request $request)
    {
        $denied = $this->comparisonGuard($request, null);
        if ($denied !== null) {
            return $denied;
        }

        return new JsonResponse((new ChartService())->getProductivityComparisonDetailed(...$this->comparisonArgs($request, null)));
    }

    // GET /bieu-do/so-sanh-matrix — ma trận công đoạn × mốc giờ (heatmap)
    public function soSanhMatrix(Request $request)
    {
        $denied = $this->comparisonGuard($request, null);
        if ($denied !== null) {
            return $denied;
        }

        return new JsonResponse((new ChartService())->getProductivityComparisonMatrix(...$this->comparisonArgs($request, null)));
    }

    // [line_id, ma_hang_id, ngay, ca_id]; $missing = giá trị khi thiếu tham số (endpoint cũ dùng 0 hoặc null).
    private function comparisonArgs(Request $request, $missing)
    {
        return [
            $request->hasQuery('line_id') ? intval($request->query('line_id')) : $missing,
            $request->hasQuery('ma_hang_id') ? intval($request->query('ma_hang_id')) : $missing,
            $request->query('ngay', date('Y-m-d')),
            $request->hasQuery('ca_id') ? intval($request->query('ca_id')) : $missing,
        ];
    }

    private function comparisonGuard(Request $request, $missing)
    {
        [$line_id, $ma_hang_id, , $ca_id] = $this->comparisonArgs($request, $missing);
        if (!$line_id || !$ma_hang_id || !$ca_id) {
            return JsonResponse::error('Thiếu tham số bắt buộc (line_id, ma_hang_id, ca_id)', 400);
        }

        return $this->lineGuard($line_id);
    }

    // Không phải admin chỉ xem được LINE đang chọn.
    private function lineGuard($line_id)
    {
        if (!Auth::checkRole(['admin']) && Auth::getSession()['line_id'] != $line_id) {
            return JsonResponse::error('Không có quyền xem LINE này', 403);
        }

        return null;
    }
}
