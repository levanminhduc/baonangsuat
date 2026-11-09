<?php

namespace App\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\NangSuatService;

class DanhMucController
{
    public function ca(Request $request)
    {
        return new JsonResponse(['success' => true, 'data' => (new NangSuatService())->getCaList()]);
    }

    public function maHang(Request $request)
    {
        return new JsonResponse(['success' => true, 'data' => (new NangSuatService())->getMaHangList()]);
    }

    public function mocGio(Request $request)
    {
        $ca_id = $request->hasQuery('ca_id') ? intval($request->query('ca_id')) : 0;

        return new JsonResponse(['success' => true, 'data' => (new NangSuatService())->getMocGioList($ca_id)]);
    }

    public function routing(Request $request)
    {
        $ma_hang_id = $request->hasQuery('ma_hang_id') ? intval($request->query('ma_hang_id')) : 0;
        $line_id = $request->hasQuery('line_id') ? intval($request->query('line_id')) : null;

        return new JsonResponse(['success' => true, 'data' => (new NangSuatService())->getRouting($ma_hang_id, $line_id)]);
    }
}
