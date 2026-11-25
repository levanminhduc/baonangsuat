<?php

namespace App\Support;

use Exception;
use mysqli;
use PDO;
use PDOException;

class Database
{
    private static $instances = [];

    private static function getConfig()
    {
        static $config = null;
        if ($config === null) {
            $configFile = getenv('BNS_DB_CONFIG') ?: 'C:/xampp/config/db.php';
            if (!file_exists($configFile)) {
                throw new Exception('Database configuration file not found');
            }
            $config = require $configFile;
            $requiredKeys = ['host', 'username', 'password', 'database', 'database_nang_suat', 'database_nhan_su'];
            if (!is_array($config)) {
                throw new Exception('Invalid database configuration');
            }
            foreach ($requiredKeys as $key) {
                if (!isset($config[$key])) {
                    throw new Exception('Invalid database configuration');
                }
            }
        }
        return $config;
    }

    public static function getMysqli()
    {
        if (!isset(self::$instances['mysqli'])) {
            $config = self::getConfig();
            self::$instances['mysqli'] = mysqli_connect(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['database'],
            );
            if (!self::$instances['mysqli']) {
                throw new Exception('Database connection failed');
            }
            mysqli_set_charset(self::$instances['mysqli'], 'utf8mb4');
        }
        return self::$instances['mysqli'];
    }

    public static function getNangSuat()
    {
        if (!isset(self::$instances['nang_suat'])) {
            $config = self::getConfig();
            self::$instances['nang_suat'] = mysqli_connect(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['database_nang_suat'],
            );
            if (!self::$instances['nang_suat']) {
                throw new Exception('Database connection failed');
            }
            mysqli_set_charset(self::$instances['nang_suat'], 'utf8mb4');
        }
        return self::$instances['nang_suat'];
    }

    public static function getNhanSu()
    {
        if (!isset(self::$instances['nhan_su'])) {
            $config = self::getConfig();
            self::$instances['nhan_su'] = mysqli_connect(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['database_nhan_su'],
            );
            if (!self::$instances['nhan_su']) {
                throw new Exception('Database connection failed');
            }
            mysqli_set_charset(self::$instances['nhan_su'], 'utf8mb4');
        }
        return self::$instances['nhan_su'];
    }

    public static function getOptionalConfig($key)
    {
        try {
            return self::getConfig()[$key] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    // Postgres master data datchi (PO/Style/Subart), chỉ đọc. Thiếu cấu hình/ext/lỗi kết nối → null để màn nhập chạy như cũ.
    public static function getDatchi()
    {
        if (!array_key_exists('datchi', self::$instances)) {
            self::$instances['datchi'] = self::connectDatchi();
        }
        return self::$instances['datchi'];
    }

    private static function connectDatchi()
    {
        $pg = self::getOptionalConfig('datchi_pg');
        if (!extension_loaded('pdo_pgsql') || !is_array($pg) || empty($pg['host']) || empty($pg['dbname'])) {
            return null;
        }
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;connect_timeout=3',
            $pg['host'],
            intval($pg['port'] ?? 5432),
            $pg['dbname'],
        );
        try {
            $pdo = new PDO($dsn, $pg['user'] ?? '', $pg['password'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // pdo_pgsql tự nối connect_timeout từ ATTR_TIMEOUT (mặc định 30s), đè tham số trong DSN
                PDO::ATTR_TIMEOUT => 3,
            ]);
            $pdo->exec('SET statement_timeout = 3000');
            $pdo->exec('SET default_transaction_read_only = on');
            return $pdo;
        } catch (PDOException $e) {
            error_log('datchi: kết nối thất bại (SQLSTATE ' . $e->getCode() . ')');
            return null;
        }
    }

    public static function closeAll()
    {
        foreach (self::$instances as $conn) {
            if ($conn instanceof mysqli) {
                mysqli_close($conn);
            }
        }
        self::$instances = [];
    }
}
