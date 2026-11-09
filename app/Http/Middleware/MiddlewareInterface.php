<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\JsonResponse;
use App\Http\Request;

interface MiddlewareInterface
{
    // null = cho qua; JsonResponse = chặn và trả luôn response này.
    public function check(Request $request, array $arguments): ?JsonResponse;
}
