<?php

namespace App\Http;

/**
 * CSRF token theo phiên: sinh một lần, dùng lại tới khi phiên hết hạn.
 * Client gửi lại qua header X-CSRF-Token.
 */
class CsrfToken
{
    private const SESSION_KEY = 'bns_csrf_token';

    public static function get()
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function validate($token)
    {
        if (empty($_SESSION[self::SESSION_KEY]) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }
}
