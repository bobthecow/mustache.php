<?php

class Mustache_Cache_FilesystemCache implements Mustache_Cache
{
    private $directory;
    private $fileMode;

    public function __construct($directory, $fileMode = null)
    {
        $this->directory = $directory;
        $this->fileMode = $fileMode;
    }

    public function get($key)
    {
        $fileName = $this->getCacheFilename($key);
        return (is_file($fileName))
            ? file_get_contents($fileName)
            : null;
    }

    public function put($key, $value)
    {
        $fileName = $this->getCacheFilename($key);
        $dirName = dirname($fileName);
        if (!is_dir($dirName)) {
            @mkdir($dirName, 0777, true);
            if (!is_dir($dirName)) {
                throw new Mustache_Exception_RuntimeException(sprintf('Failed to create cache directory "%s".', $dirName));
            }

        }

        $tempFile = tempnam($dirName, basename($fileName));
        if (false !== @file_put_contents($tempFile, $value)) {
            if (@rename($tempFile, $fileName)) {
                $mode = isset($this->fileMode) ? $this->fileMode : (0666 & ~umask());
                @chmod($fileName, $mode);

                return;
            }
        }

        throw new Mustache_Exception_RuntimeException(sprintf('Failed to write cache file "%s".', $fileName));
    }

    protected function getCacheFilename($name)
    {
        return sprintf('%s/%s.php', $this->directory, md5($name));
    }
}
