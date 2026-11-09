<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\HtmlResponse;
use App\Http\RedirectResponse;
use App\Http\Request;
use App\Support\View;

// Trang chọn LINE (`/`, `/index.php`). Đăng nhập + security headers do middleware trang lo.
class IndexPageController
{
    public function show(Request $request)
    {
        if (Auth::checkRole(['admin'])) {
            return new RedirectResponse('admin.php');
        }

        if (Auth::hasLine()) {
            return new RedirectResponse('nhap-nang-suat.php');
        }

        $allowedLines = Auth::allowedLines();
        if (count($allowedLines) === 0) {
            return new RedirectResponse('no-line.php');
        }

        return new HtmlResponse(View::render('index', ['allowedLines' => $allowedLines]));
    }
}
