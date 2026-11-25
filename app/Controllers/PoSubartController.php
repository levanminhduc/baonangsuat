<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\PoSubartService;

// Vai trò admin và CSRF khai ở route.
class PoSubartController
{
    public function __construct(private PoSubartService $poSubart)
    {
    }

    public function index(Request $request)
    {
        $result = $this->poSubart->list(
            $request->query('po_number'),
            $request->hasQuery('ma_hang_id') ? intval($request->query('ma_hang_id')) : 0,
        );

        return self::toResponse($result);
    }

    public function save(Request $request)
    {
        $input = $request->jsonBody();
        $result = $this->poSubart->save(
            $input['po_number'] ?? '',
            intval($input['ma_hang_id'] ?? 0),
            $input['sub_art_code'] ?? '',
            $input['so_luong'] ?? null,
            Auth::getSession()['ma_nv'],
        );

        return self::toResponse($result);
    }

    public function destroy(Request $request)
    {
        $input = $request->jsonBody();
        $result = $this->poSubart->delete(
            $input['po_number'] ?? '',
            intval($input['ma_hang_id'] ?? 0),
            $input['sub_art_code'] ?? '',
        );

        return self::toResponse($result);
    }

    public static function toResponse(array $result)
    {
        if ($result['success']) {
            return new JsonResponse($result);
        }

        return JsonResponse::error($result['message'], $result['status'] ?? 400);
    }
}
