<?php

namespace App\Controllers;

use App\Http\CsrfToken;
use App\Http\JsonResponse;
use App\Http\Request;

class CsrfTokenController
{
    public function show(Request $request)
    {
        return new JsonResponse(['success' => true, 'token' => CsrfToken::get()]);
    }
}
