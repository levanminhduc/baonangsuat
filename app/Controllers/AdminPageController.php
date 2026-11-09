<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\HtmlResponse;
use App\Http\RedirectResponse;
use App\Http\Request;
use App\Support\View;

class AdminPageController
{
    public function show(Request $request)
    {
        if (!Auth::checkRole(['admin'])) {
            return new RedirectResponse(Auth::getDefaultPage());
        }

        // Admin ngoài whitelist panel quản trị → về trang nhập.
        if (!Auth::canAccessAdminPanel()) {
            return new RedirectResponse('nhap-nang-suat.php');
        }

        return new HtmlResponse(View::render('admin', ['session' => Auth::getSession()]));
    }
}
