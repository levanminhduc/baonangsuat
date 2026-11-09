<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\ResponseInterface;

interface MiddlewareInterface
{
    // null = cho qua; response = chặn và trả luôn response này.
    public function check(Request $request, array $arguments): ?ResponseInterface;
}
