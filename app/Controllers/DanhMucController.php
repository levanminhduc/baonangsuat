<?php

namespace App\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\DanhMucLookup;
use App\Services\PoSubartService;

class DanhMucController
{
    public function __construct(
        private DanhMucLookup $danhMuc,
        private PoSubartService $poSubart,
    ) {
    }

    public function ca(Request $request)
    {
        return new JsonResponse(['success' => true, 'data' => $this->danhMuc->getCaList()]);
    }

    public function maHang(Request $request)
    {
        return new JsonResponse(['success' => true, 'data' => $this->danhMuc->getMaHangList()]);
    }

    public function mocGio(Request $request)
    {
        $ca_id = $request->hasQuery('ca_id') ? intval($request->query('ca_id')) : 0;

        return new JsonResponse(['success' => true, 'data' => $this->danhMuc->getMocGioList($ca_id)]);
    }

    public function routing(Request $request)
    {
        $ma_hang_id = $request->hasQuery('ma_hang_id') ? intval($request->query('ma_hang_id')) : 0;
        $line_id = $request->hasQuery('line_id') ? intval($request->query('line_id')) : null;

        return new JsonResponse(['success' => true, 'data' => $this->danhMuc->getRouting($ma_hang_id, $line_id)]);
    }

    public function po(Request $request)
    {
        $ma_hang_id = $request->hasQuery('ma_hang_id') ? intval($request->query('ma_hang_id')) : 0;

        return PoSubartController::toResponse($this->poSubart->danhMucPo($ma_hang_id));
    }

    public function poSubart(Request $request)
    {
        $ma_hang_id = $request->hasQuery('ma_hang_id') ? intval($request->query('ma_hang_id')) : 0;
        $po_number = (string)$request->query('po_number');

        return PoSubartController::toResponse($this->poSubart->danhMucSubart($po_number, $ma_hang_id));
    }
}
