<?php

namespace App\Services\Parsers;

use App\Interfaces\FileParserInterface;

class CsvParser implements FileParserInterface
{
    public function getRowIterator($file)
    {
        $file = fopen($file, 'r');
        echo "Opening file: " . $file . PHP_EOL;
        fgetcsv($file); // Skip headers
        while (($row = fgetcsv($file)) !== false) {
            yield $row;
        }

        echo "Closing file: " . $file . PHP_EOL;
        fclose($file);
    }

    public function getHeaders($file)
    {
        if (($handle = fopen($file, 'r')) !== false) {
            $headers = fgetcsv($handle);
            fclose($handle);

            return $headers;
        }

        return [];
    }
}