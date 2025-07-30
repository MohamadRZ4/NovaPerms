<?php

namespace MohamadRZ\NovaPerms\model\cache\providers;

use MohamadRZ\NovaPerms\model\cache\providers\AbstractCacheProvider;
use MohamadRZ\NovaPerms\NovaPermsPlugin;

class FileCacheProvider extends AbstractCacheProvider
{
    private string $cacheDir;

    protected function initialize(): void
    {
        $this->cacheDir = NovaPermsPlugin::getDatePath() . '/nova_cache';

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    protected function doGet(string $key): mixed
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        $data = json_decode($content, true);

        if ($this->isExpiryEnabled() && $data['expire'] !== null && $data['expire'] < time()) {
            unlink($file);
            return null;
        }

        return $data['value'];
    }

    protected function doSet(string $key, mixed $value, ?int $ttl): bool
    {
        $file = $this->getFilePath($key);

        $expire = null;
        if ($this->isExpiryEnabled() && $ttl !== null) {
            $expire = time() + $ttl;
        }

        $data = json_encode([
            'value' => $value,
            'expire' => $expire,
            'created' => time(),
            'expiry_enabled' => $this->isExpiryEnabled()
        ]);

        return file_put_contents($file, $data, LOCK_EX) !== false;
    }

    protected function doDelete(string $key): bool
    {
        $file = $this->getFilePath($key);
        return !file_exists($file) || unlink($file);
    }

    protected function doClear(): bool
    {
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }

    protected function doExists(string $key): bool
    {
        return $this->doGet($key) !== null;
    }

    private function getFilePath(string $key): string
    {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }

    /**
     * @return array
     */
    #[\Override] public function getAll(): array
    {
        $result = [];
        $files = glob($this->cacheDir . '/*.cache');

        if ($files === false) {
            return [];
        }

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $data = json_decode($content, true);
            if ($data === null) {
                continue;
            }

            if ($this->isExpiryEnabled() && $data['expire'] !== null && $data['expire'] < time()) {
                unlink($file);
                continue;
            }

            $filename = basename($file, '.cache');
            $result[$filename] = $data['value'];
        }

        return $result;
    }

}