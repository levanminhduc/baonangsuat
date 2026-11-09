<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Khác nhansuhtdb: giữ nguyên shape JSON của baonangsuat (`{success, message, data}`)
 * và mã HTTP thật (401/403/404/405/500), vì JS hiện tại đọc đúng shape này.
 */
final class JsonResponse implements ResponseInterface
{
    public function __construct(
        private readonly array $body,
        private readonly int $status = 200,
    ) {
    }

    public static function error(string $message, int $status, array $extra = []): self
    {
        return new self(['success' => false, 'message' => $message] + $extra, $status);
    }

    public function body(): array
    {
        return $this->body;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->body, JSON_UNESCAPED_UNICODE);
    }
}
