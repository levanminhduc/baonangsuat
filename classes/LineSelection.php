<?php

/**
 * Logic chọn/đối chiếu LINE thuần (không đụng DB/session) để test được.
 * $line = ['id' => int, 'ma_line' => string, 'ten_line' => string]; $stored = $line + ['owner' => int].
 */
final class LineSelection
{
    public static function reconcile(?array $stored, int $userId, array $allowed): ?array
    {
        if ($stored !== null && isset($stored['id'], $stored['owner']) && (int)$stored['owner'] === $userId) {
            $line = self::find((int)$stored['id'], $allowed);
            if ($line !== null) {
                return $line + ['owner' => (int)$stored['owner']];
            }
        }

        if (count($allowed) === 1) {
            return self::normalize(reset($allowed)) + ['owner' => $userId];
        }

        return null;
    }

    public static function pick(int $lineId, int $userId, array $allowed): ?array
    {
        $line = self::find($lineId, $allowed);
        return $line !== null ? $line + ['owner' => $userId] : null;
    }

    private static function find(int $lineId, array $allowed): ?array
    {
        foreach ($allowed as $line) {
            if ((int)$line['id'] === $lineId) {
                return self::normalize($line);
            }
        }
        return null;
    }

    private static function normalize(array $line): array
    {
        return [
            'id' => (int)$line['id'],
            'ma_line' => $line['ma_line'],
            'ten_line' => $line['ten_line'],
        ];
    }
}
