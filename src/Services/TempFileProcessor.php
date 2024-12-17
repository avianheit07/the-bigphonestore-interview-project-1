<?php

namespace App\Services;

use App\Interfaces\TempFileManagerInterface;

class TempFileProcessor implements TempFileManagerInterface
{
    private $directory;
    
    public function __construct($tempDirectory)
    {
        $this->directory = $tempDirectory;
        $this->initializeTempDirectory($tempDirectory);
    }
    /** TODO: know what is the purpose of this class */
    public function initializeTempDirectory($directory)
    {
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
            return;
        }

        $this->deleteTempFiles($directory);
        return;
        
    }

    public function deleteTempFiles($directory)
    {
        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $file) {
            unlink($directory . DIRECTORY_SEPARATOR . $file);
        }
    }

    public function getDirectory()
    {
        return $this->directory;
    }
}