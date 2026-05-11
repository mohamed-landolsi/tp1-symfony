<?php
namespace App\Service;

class TextFormatter
{
    private array $forbiddenWords = ['mauvais', 'nul', 'spam'];

    public function filter(string $text): string
    {
        return str_ireplace($this->forbiddenWords, '****', $text);
    }
}