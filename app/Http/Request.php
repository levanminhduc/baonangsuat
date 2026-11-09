<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Chép từ nhansuhtdb rồi chỉnh theo ADR 0001: request mang đường dẫn tính từ gốc app
 * (`/api/context`), không dùng `?page=` / `?action=`.
 */
final class Request
{
    private function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $server,
        private readonly ?string $rawBody = null,
        private readonly array $params = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $uriPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');

        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            self::appPath($uriPath, self::basePath((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'))),
            $_GET,
            $_SERVER,
        );
    }

    public static function fake(
        string $method,
        string $path,
        array $query = [],
        array $server = [],
        ?string $rawBody = null,
    ): self {
        return new self(strtoupper($method), $path, $query, $server, $rawBody);
    }

    // Tiền tố URL của app, suy từ vị trí front controller nên chạy được cả dưới
    // `localhost/baonangsuat/` ('/baonangsuat') lẫn vhost ('').
    public static function basePath(string $scriptName): string
    {
        $dir = rtrim(str_replace(chr(92), '/', dirname($scriptName)), '/');
        if (str_ends_with($dir, '/public')) {
            $dir = substr($dir, 0, -strlen('/public'));
        }

        return $dir;
    }

    // Đường dẫn bỏ tiền tố app và dấu `/` thừa ở cuối (router cũ cũng trim `/`).
    public static function appPath(string $uriPath, string $basePath): string
    {
        if ($basePath !== '' && ($uriPath === $basePath || str_starts_with($uriPath, $basePath . '/'))) {
            $uriPath = substr($uriPath, strlen($basePath));
        }

        return '/' . trim($uriPath, '/');
    }

    public function withParams(array $params): self
    {
        return new self($this->method, $this->path, $this->query, $this->server, $this->rawBody, $params);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isApi(): bool
    {
        return $this->path === '/api' || str_starts_with($this->path, '/api/');
    }

    // Đoạn đầu sau `/api/` (vd. `auth` của `/api/auth/session`), dùng để biết nhóm route.
    public function apiGroup(): string
    {
        if (!$this->isApi()) {
            return '';
        }

        return explode('/', substr($this->path, strlen('/api/')))[0] ?? '';
    }

    public function param(string $name, ?string $default = null): ?string
    {
        return $this->params[$name] ?? $default;
    }

    public function hasQuery(string $key): bool
    {
        return isset($this->query[$key]);
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return (string) ($this->server[$key] ?? '');
    }

    // Thân JSON như router cũ (`json_decode(...) ?? []`): thân hỏng hoặc không phải object → [].
    public function jsonBody(): array
    {
        $raw = $this->rawBody ?? (string) file_get_contents('php://input');
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
