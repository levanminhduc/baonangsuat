<?php

declare(strict_types=1);

namespace App\Http;

// Location giữ nguyên chuỗi tương đối như trang cũ (`admin.php`, `nhap-nang-suat.php`...):
// URL trang nằm ở gốc ứng dụng nên tương đối vẫn đúng cả dưới /baonangsuat/ lẫn vhost.
final class RedirectResponse implements ResponseInterface
{
    public function __construct(
        private readonly string $location,
        private readonly int $status = 302,
    ) {
    }

    public function location(): string
    {
        return $this->location;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function send(): void
    {
        header('Location: ' . $this->location, true, $this->status);
    }
}
