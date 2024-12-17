<?php

namespace App\Services;

use App\Models\Product;
use App\Interfaces\FileParserInterface;
use App\Config\Config;
use App\Interfaces\TempFileManagerInterface;

class ProductAggregator
{
    private array $headers;
    private int $chunkLimit;
    private int $tempFileCounter;
    private FileParserInterface $parser;
    private string $currentTempFile;
    private string $outputDirectory;
    private string $tempFileDirectory;
    private string $tempFileName;
    private TempFileManagerInterface $tempFileProcessor;

    public function __construct(FileParserInterface $parser, TempFileManagerInterface $tempFileManager, int $chunkLimit = 5000)
    {
        $this->headers           = [];
        $this->chunkLimit        = $chunkLimit;
        $this->outputDirectory   = __DIR__ . '/../../files/results/';
        $this->parser            = $parser;
        $this->tempFileCounter   = 1;
        $this->tempFileDirectory = $tempFileManager->getDirectory();
        $this->tempFileName      = 'temp';
        $this->tempFileProcessor = $tempFileManager;
        
        $this->tempFileProcessor->initializeTempDirectory($this->tempFileDirectory);
    }

    public function aggregate($inputFile, $outputFile)
    {
        $this->headers = $this->parser->getHeaders($inputFile);

        try {
            $this->processRows($inputFile); // processing rows by chunk
        } catch (\Exception $e) {
            die($e->getMessage());
        }

        $this->finalizeAggregation($outputFile);
    }

    private function processRows($inputFile)
    {
        echo "Processing row: " . PHP_EOL;
        $rowIterator  = $this->parser->getRowIterator($inputFile);
        $chunk        = [];

        foreach ($rowIterator as $row) {  
            $product = new Product($row, $this->headers);
            $key     = $product->generateKey();

            if (isset($chunk[$key])) {
                $chunk[$key]->incrementCount(); // get product if set and increment total
            } else {
                $chunk[$key] = $product;
            }
                      
            if (count($chunk) === $this->chunkLimit) {
                echo 'Chunk limit reached, processing chunk data.' . PHP_EOL;
                $this->processChunkData($chunk);
                $chunk = [];
            }
        }

        if (count($chunk) > 0) {
            echo 'Processing remaining chunk data.' . PHP_EOL;
            $this->processChunkData($chunk);
        }
    }

    /**
     * This function will process the chunk data by reading through the existing temp files
     *    - if the 
     * 
     * here is the main logic of the aggregation
     * @param array $batch
     * @param integer $count
     * @return void
     */
    private function processChunkData(array $batch)
    {
        echo "Processing chunk data, current file counter {$this->tempFileCounter}" . PHP_EOL;
        if ($this->tempFileCounter === 1) {
            $this->writeTempData($batch);
            return;
        }

        // fetch existing temp files json
        $currentFileCount = $this->tempFileCounter;
        $count            = 1;
        while ($count <= $currentFileCount) {
            $currentTempFile = $this->composeTempFileName($count);
            echo "Looping batch rows through currentTempFile: {$currentTempFile} " . PHP_EOL;
            $count++;

            if (!file_exists($currentTempFile)) {
                continue;
            }

            $handle = fopen($currentTempFile, 'r');
            if ($handle === false) {
                throw new \Exception('Could not open file: ' . $currentTempFile);
            }

            while (($line = fgets($handle)) !== false) {
                $products = json_decode($line, true);
                $products = array_map(
                    fn($product) => unserialize($product),
                    $products
                );

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Error parsing JSON: ' . json_last_error_msg());
                }

                foreach ($batch as $key => $product) {
                    if (isset($products[$key])) {
                        $products[$key]->incrementCount();
                        echo "Incrementing count for key: {$key}, count: {$products[$key]->getTotal()}" . PHP_EOL;
                        unset($batch[$key]);
                    }
                }
            }

            if (count($batch) === 0) {
                echo "All rows in batch array are unset" . PHP_EOL;
                break;
            }
        }
        
        echo "Done looping through temp files, writing remaining batch data to temp file" . PHP_EOL;
        $this->writeTempData($batch);
    }

    private function writeTempData(array $data)
    {
        $this->currentTempFile = $this->composeTempFileName($this->tempFileCounter);
        $this->tempFileCounter++;
        foreach ($data as $key => $product) {
            $data[$key] = serialize($product);
        }

        file_put_contents($this->currentTempFile, json_encode($data));
    } 

    private function composeTempFileName($counter = 0)
    {
        return  "{$this->tempFileDirectory}{$this->tempFileName}_{$counter}.json";
    }

    private function finalizeAggregation($outputFile)
    {
        $outputFileHandle = fopen($outputFile, 'w');
        $headers          = Config::get('settings')['headers'];

        if ($outputFileHandle === false) {
            throw new \Exception('Could not open file: ' . $outputFileHandle);
        }

        $count = 1;
        fputcsv($outputFileHandle, array_keys($headers));

        while ($count <= $this->tempFileCounter) {
            $currentTempFile = $this->composeTempFileName($count);
            $count++;

            if (!file_exists($currentTempFile)) {
                continue;
            }

            echo "Processing temp file: {$currentTempFile}" . PHP_EOL;
            $handle = fopen($currentTempFile, 'r');
            if ($handle === false) {
                throw new \Exception('Could not open file: ' . $currentTempFile);
            }

            while (($line = fgets($handle)) !== false) {
                $products = json_decode($line, true);

                $products = array_map(
                    fn($product) => unserialize($product),
                    $products
                );

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Error parsing JSON: ' . json_last_error_msg());
                }

                foreach ($products as $product) {
                    $this->writeProduct($product, $headers, $outputFileHandle);
                }
            }
        }
    }

    private function writeProduct($product, $headers, $file)
    {
        $row          = $product->getOriginalRow();
        $row['count'] = $product->getTotal();

        $row = array_map(
            fn($header) => $row[$header] ?? null,
            $headers
        );

        fputcsv($file, $row);
    }
}