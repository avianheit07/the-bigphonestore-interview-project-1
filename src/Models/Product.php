<?php

namespace App\Models;

class Product
{
    private $row;
    private $headers;
    private $total;
    private $key;

    public function __construct(array $data, array $headers)
    {
        $this->row     = array_combine($headers, $data);
        $this->headers = $headers;
        $this->total   = 1;
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
        
        $this->key = hash('sha256', $concatenated);
        return $this->key;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getOriginalRow()
    {
        return $this->row;
    }

    public function incrementCount()
    {
        $this->total++;
        return $this;
    }
    
    public function getTotal()
    {
        return $this->total;
    }
    
    public function getKey()
    {
        return $this->key;
    }
}