<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;

class AuthController
{
    public function selectLine(Request $request)
    {
        $input = $request->jsonBody();
        $line_id = intval($input['line_id'] ?? 0);
        $result = Auth::selectLine($line_id);
        if ($result['success']) {
            $result['redirect_url'] = Auth::getDefaultPage();
        }

        return new JsonResponse($result, $result['success'] ? 200 : 403);
    }

    public function session(Request $request)
    {
        $sessionData = Auth::getSession();
        $sessionData['can_view_history'] = Auth::canViewHistory();

        return new JsonResponse(['success' => true, 'data' => $sessionData]);
    }

    // `/api/auth/<khác>`: router cũ trả 404 với message riêng của nhóm auth.
    public function unknownAction(Request $request)
    {
        return JsonResponse::error('Action không hợp lệ', 404);
    }
}
