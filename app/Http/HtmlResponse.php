<?php

declare(strict_types=1);

namespace App\Http;

final class HtmlResponse implements ResponseInterface
{
    public function __construct(
        private readonly string $html,
        private readonly int $status = 200,
    ) {
    }

    public function html(): string
    {
        return $this->html;
    }

    public function status(): int
    {
        return $this->status;
    }

    // Không tự đặt Content-Type: trang cũ dùng mặc định của PHP (default_charset), giữ y vậy.
    public function send(): void
    {
        http_response_code($this->status);
        echo $this->html;
    }
}
