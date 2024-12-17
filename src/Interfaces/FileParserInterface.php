<?php

namespace App\Interfaces;

interface FileParserInterface
{
    public function getRowIterator($file);

    public function getHeaders($file);
}