<?php

namespace App\Models;

class Product
{
    private $row;
    private $headers;

    public function __construct(array $data, array $headers)
    {
        $this->row     = array_combine($headers, $data);
        $this->headers = $headers;
    }

    public function get($property)
    {
        return $this->row[$property] ?? null;
    }

    public function generateKey()
    {
        $normalized = array_map(
            fn($value) => strtolower(trim($value)),
            $this->row
        );

        $concatenated = implode('|', $normalized);
        
        return hash('sha256', $concatenated);
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getOriginalRow()
    {
        return $this->row;
    }
}