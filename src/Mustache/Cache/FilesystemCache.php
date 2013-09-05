<?php

class Mustache_Cache_FilesystemCache extends Mustache_Cache_AbstractCache
{
    private $directory;
    private $fileMode;

    public function __construct($directory, $fileMode = null)
    {
        $this->directory = $directory;
        $this->fileMode = $fileMode;
    }

    public function load($key)
    {
        $fileName = $this->getCacheFilename($key);
        if (!is_file($fileName)) {
            return false;
        }

        require_once $fileName;

        return true;
    }

    public function cache($key, $value)
    {
        $fileName = $this->getCacheFilename($key);

        $this->log(
            Mustache_Logger::DEBUG,
            'Writing to template cache: "{fileName}"',
            array('fileName' => $fileName)
        );

        $this->writeFile($fileName, $value);
        $this->load($key);
    }

    protected function getCacheFilename($name)
    {
        return sprintf('%s/%s.php', $this->directory, md5($name));
    }

    private function buildDirectoryForFilename($fileName)
    {
        $dirName = dirname($fileName);
        if (!is_dir($dirName)) {
            $this->log(
                Mustache_Logger::INFO,
                'Creating Mustache template cache directory: "{dirName}"',
                array('dirName' => $dirName)
            );

            @mkdir($dirName, 0777, true);
            if (!is_dir($dirName)) {
                throw new Mustache_Exception_RuntimeException(sprintf('Failed to create cache directory "%s".', $dirName));
            }
        }
        return $dirName;
    }

    private function writeFile($fileName, $value)
    {
        $dirName = $this->buildDirectoryForFilename($fileName);

        $this->log(
            Mustache_Logger::DEBUG,
            'Caching compiled template to "{fileName}"',
            array('fileName' => $fileName)
        );

        $tempFile = tempnam($dirName, basename($fileName));
        if (false !== @file_put_contents($tempFile, $value)) {
            if (@rename($tempFile, $fileName)) {
                $mode = isset($this->fileMode) ? $this->fileMode : (0666 & ~umask());
                @chmod($fileName, $mode);

                return $fileName;
            }

            $this->log(
                Mustache_Logger::ERROR,
                'Unable to rename Mustache temp cache file: "{tempName}" -> "{fileName}"',
                array('tempName' => $tempFile, 'fileName' => $fileName)
            );
        }

        throw new Mustache_Exception_RuntimeException(sprintf('Failed to write cache file "%s".', $fileName));
    }
}
