<?php

namespace Core;

class Cache
{
    private static $cacheDir = __DIR__ . '/../storage/cache';

    public static function get($key)
    {
        $file = self::getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }

        $data = unserialize(file_get_contents($file));
        
        if ($data['expires'] !== null && time() > $data['expires']) {
            unlink($file);
            return null;
        }

        return $data['value'];
    }

    public static function set($key, $value, $ttl = 3600)
    {
        $file = self::getCacheFile($key);
        
        $data = [
            'value' => $value,
            'expires' => $ttl === null ? null : time() + $ttl
        ];

        $cacheDir = dirname($file);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        file_put_contents($file, serialize($data));
    }

    public static function has($key)
    {
        return self::get($key) !== null;
    }

    public static function forget($key)
    {
        $file = self::getCacheFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public static function flush()
    {
        $files = glob(self::$cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private static function getCacheFile($key)
    {
        $hash = md5($key);
        return self::$cacheDir . '/' . substr($hash, 0, 2) . '/' . $hash;
    }
}