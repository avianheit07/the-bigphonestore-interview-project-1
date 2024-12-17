<?php

namespace App\Interfaces;

interface TempFileManagerInterface
{
    public function initializeTempDirectory($directory);

    public function deleteTempFiles($directory);

    public function getDirectory();
}