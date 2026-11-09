<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Middleware\MiddlewareInterface;
use App\Http\Middleware\RequireHistoryAccess;
use App\Http\Middleware\RequireImportAccess;
use App\Http\Middleware\RequireLine;
use App\Http\Middleware\RequireLogin;
use App\Http\Middleware\RequireReportScope;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\VerifyCsrfToken;
use Exception;

/**
 * Chép từ nhansuhtdb rồi chỉnh theo ADR 0001: khớp theo METHOD + đường dẫn thay cho key
 * `?action=`. Khi đồng bộ với nhansuhtdb, đừng đổi phần khớp đường dẫn về `?action=`.
 *
 * Route: [METHODS, path, Controller::class, method, [middleware...]]
 *   - METHODS dạng 'GET|POST' hoặc '*' (mọi method, cho endpoint cũ không kiểm method).
 *   - path có tham số một đoạn `{name}` hoặc kèm regex `{id:\d+}`.
 *   - route khai trước được ưu tiên; đường dẫn khớp nhưng không route nào nhận method → 405.
 *
 * Nhóm (`/api/<group>`): middleware của nhóm chạy TRƯỚC khi khớp route — handler cũ kiểm
 * đăng nhập/quyền trước rồi mới xét đường dẫn, nên request sai đường dẫn khi chưa đăng nhập
 * vẫn nhận 401. `not_found` / `method_not_allowed` = [mã HTTP, message] của nhóm đó.
 */
final class Router
{
    public const MIDDLEWARE = [
        'auth' => RequireLogin::class,
        'line' => RequireLine::class,
        'role' => RequireRole::class,
        'csrf' => VerifyCsrfToken::class,
        'report' => RequireReportScope::class,
        'history' => RequireHistoryAccess::class,
        'import' => RequireImportAccess::class,
    ];

    private const NOT_FOUND = [404, 'API không tồn tại'];
    private const METHOD_NOT_ALLOWED = [405, 'Method not allowed'];

    public function __construct(private readonly array $config)
    {
    }

    /**
     * @return array{0: int, 1: ?array, 2: array<string, string>} [200|404|405, route, params]
     */
    public function match(string $method, string $path): array
    {
        $pathMatched = false;
        foreach ($this->config['routes'] ?? [] as $route) {
            $params = self::matchPath($route[1], $path);
            if ($params === null) {
                continue;
            }
            $pathMatched = true;
            if ($route[0] === '*' || in_array($method, explode('|', $route[0]), true)) {
                return [200, $route, $params];
            }
        }

        return [$pathMatched ? 405 : 404, null, []];
    }

    public function dispatch(Request $request): ResponseInterface
    {
        $group = $this->config['groups'][$request->apiGroup()] ?? [];
        // Router cũ bọc mọi handler trong try/catch (Exception) → 500 kèm message.
        try {
            foreach ($group['middleware'] ?? [] as $name) {
                $denied = $this->runMiddleware($name, $request);
                if ($denied !== null) {
                    return $denied;
                }
            }

            [$status, $route, $params] = $this->match($request->method(), $request->path());
            if ($status !== 200) {
                [$code, $message] = $status === 404
                    ? ($group['not_found'] ?? self::NOT_FOUND)
                    : ($group['method_not_allowed'] ?? self::METHOD_NOT_ALLOWED);

                return JsonResponse::error($message, $code);
            }

            $request = $request->withParams($params);
            foreach ($route[4] ?? [] as $name) {
                $denied = $this->runMiddleware($name, $request);
                if ($denied !== null) {
                    return $denied;
                }
            }

            [, , $class, $verb] = $route;

            return (new $class())->{$verb}($request);
        } catch (Exception $e) {
            return JsonResponse::error($e->getMessage(), 500);
        }
    }

    private static function matchPath(string $pattern, string $path): ?array
    {
        $patternParts = explode('/', trim($pattern, '/'));
        $pathParts = explode('/', trim($path, '/'));
        if (count($patternParts) !== count($pathParts)) {
            return null;
        }

        $params = [];
        // Regex của tham số khớp trong một đoạn, không được chứa `/` (pattern bị tách theo `/`).
        foreach ($patternParts as $i => $part) {
            if (preg_match('/^\{(\w+)(?::(.+))?\}$/', $part, $m) === 1) {
                $regex = $m[2] ?? '[^/]+';
                if (preg_match('#^(?:' . $regex . ')$#', $pathParts[$i]) !== 1) {
                    return null;
                }
                $params[$m[1]] = $pathParts[$i];
            } elseif ($part !== $pathParts[$i]) {
                return null;
            }
        }

        return $params;
    }

    private function runMiddleware(string $name, Request $request): ?JsonResponse
    {
        [$key, $rest] = array_pad(explode(':', $name, 2), 2, '');
        $class = self::MIDDLEWARE[$key] ?? null;
        if ($class === null) {
            return JsonResponse::error('Middleware không tồn tại: ' . $key, 500);
        }

        $arguments = $rest === '' ? [] : explode(',', $rest);
        /** @var MiddlewareInterface $middleware */
        $middleware = new $class();

        return $middleware->check($request, $arguments);
    }
}
