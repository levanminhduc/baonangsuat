<?php

class Database {
    private static $instances = [];
    
    private static function getConfig() {
        static $config = null;
        if ($config === null) {
            $configFile = 'C:/xampp/config/db.php';
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
    
    public static function getMysqli() {
        if (!isset(self::$instances['mysqli'])) {
            $config = self::getConfig();
            self::$instances['mysqli'] = mysqli_connect(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['database']
            );
            if (!self::$instances['mysqli']) {
                throw new Exception('Database connection failed');
            }
            mysqli_set_charset(self::$instances['mysqli'], 'utf8mb4');
        }
        return self::$instances['mysqli'];
    }
    
    public static function getNangSuat() {
        if (!isset(self::$instances['nang_suat'])) {
            $config = self::getConfig();
            self::$instances['nang_suat'] = mysqli_connect(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['database_nang_suat']
            );
            if (!self::$instances['nang_suat']) {
                throw new Exception('Database connection failed');
            }
            mysqli_set_charset(self::$instances['nang_suat'], 'utf8mb4');
        }
        return self::$instances['nang_suat'];
    }
    
    public static function getNhanSu() {
        if (!isset(self::$instances['nhan_su'])) {
            $config = self::getConfig();
            self::$instances['nhan_su'] = mysqli_connect(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['database_nhan_su']
            );
            if (!self::$instances['nhan_su']) {
                throw new Exception('Database connection failed');
            }
            mysqli_set_charset(self::$instances['nhan_su'], 'utf8mb4');
        }
        return self::$instances['nhan_su'];
    }
    
    public static function closeAll() {
        foreach (self::$instances as $conn) {
            if ($conn) {
                mysqli_close($conn);
            }
        }
        self::$instances = [];
    }
}
