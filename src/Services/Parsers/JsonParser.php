<?php

namespace App\Services\Parsers;

use App\Interfaces\FileParserInterface;

class JsonParser implements FileParserInterface
{
    /** NOTE: This method is not used in the current implementation
     * 
    */
    public function getRowIterator($file)
    {
        $json = file_get_contents($file);
        return json_decode($json, true);
    }

    public function getHeaders($file)
    {
        return [];
    }
}