<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Middleware\MiddlewareInterface;
use App\Http\Middleware\RequireLine;
use App\Http\Middleware\RequireLogin;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\VerifyCsrfToken;
use Exception;

/**
 * Chép từ nhansuhtdb rồi chỉnh theo ADR 0001: khớp theo METHOD + đường dẫn (có tham số
 * `{name}` cho một đoạn) thay cho key `?action=`. Khi đồng bộ với nhansuhtdb, đừng đổi
 * phần khớp đường dẫn về `?action=`.
 *
 * Route: [METHODS, path, Controller::class, method, [middleware...]], METHODS dạng 'GET|POST'
 * hoặc '*' (mọi method, cho endpoint cũ không kiểm method).
 * Route khai trước được ưu tiên: đường dẫn khớp route đầu tiên nhưng sai method → 405.
 */
final class Router
{
    public const MIDDLEWARE = [
        'auth' => RequireLogin::class,
        'line' => RequireLine::class,
        'role' => RequireRole::class,
        'csrf' => VerifyCsrfToken::class,
    ];

    public function __construct(private readonly array $config)
    {
    }

    // Nhóm `/api/<group>` đã chuyển sang Router; nhóm khác front controller nhường router cũ.
    public function owns(Request $request): bool
    {
        return in_array($request->apiGroup(), $this->config['groups'] ?? [], true);
    }

    /**
     * @return array{0: int, 1: ?array, 2: array<string, string>} [200|404|405, route, params]
     */
    public function match(string $method, string $path): array
    {
        foreach ($this->config['routes'] ?? [] as $route) {
            $params = self::matchPath($route[1], $path);
            if ($params === null) {
                continue;
            }
            if ($route[0] !== '*' && !in_array($method, explode('|', $route[0]), true)) {
                return [405, null, []];
            }

            return [200, $route, $params];
        }

        return [404, null, []];
    }

    public function dispatch(Request $request): ResponseInterface
    {
        [$status, $route, $params] = $this->match($request->method(), $request->path());
        if ($status === 404) {
            return JsonResponse::error('API không tồn tại', 404);
        }
        if ($status === 405) {
            return JsonResponse::error('Method not allowed', 405);
        }

        $request = $request->withParams($params);
        // Router cũ bọc mọi handler trong try/catch (Exception) → 500 kèm message.
        try {
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
        foreach ($patternParts as $i => $part) {
            if (preg_match('/^\{(\w+)\}$/', $part, $m) === 1) {
                if ($pathParts[$i] === '') {
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
