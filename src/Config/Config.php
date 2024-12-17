<?php 

namespace App\Config;

class Config
{
    protected static $config = [];

    public static function load(string $file)
    {
        $filePath = __DIR__ . '/' . $file . '.php';
        if (!file_exists($filePath)) {
            throw new \Exception('Config file not found: ' . $filePath);
        }

        self::$config[$file] = include $filePath;
    }

    public static function get(string $file, string $key = null)
    {
        if (!isset(self::$config[$file])) {
            self::load($file);
        }

        if ($key === null) {
            return self::$config[$file];
        }

        return self::$config[$file][$key] ?? null;
    }
}