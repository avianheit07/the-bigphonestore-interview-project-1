<?php

use App\Services\Parsers\CsvParser;
use App\Services\ProductAggregator;

require_once __DIR__ . '/vendor/autoload.php';

$options       = getopt('', ['file:', 'unique-combinations:']);
$inputFile     = $options['file'] ?? null;
$outputFile    = $options['unique-combinations'] ?? null;
$filesDirectory = __DIR__ . '/files/examples/';
$parser        = null;
$fileExtension  = pathinfo($inputFile, PATHINFO_EXTENSION);

switch ($fileExtension) {
    case 'csv':
        $parser = new CsvParser();
        break;
    case 'json':
    case 'xml':
    default:
        die('Unsupported file format');
}

if (!$inputFile || !$outputFile) {
    die('Please specify both input and output files. \n');
}

if (!file_exists($filesDirectory . $inputFile)) {
    die('File not found');
}

$aggregator = new ProductAggregator($parser);
$aggregator->aggregate($filesDirectory . $inputFile, $filesDirectory . '/results/' . $outputFile);

echo "Done!";