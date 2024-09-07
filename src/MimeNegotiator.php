<?php

namespace Az\Route;

class MimeNegotiator
{
    private string $acceptHeader;

    public function __construct(string $accept_header)
    {
        $this->acceptHeader = $accept_header;
    }

    public function getResponseType()
    {
        $mimeTypes = $this->getSortedMimeTypesByHeader();

        foreach ($mimeTypes as $mimeType) {
            if ($mimeType === 'text/html' || $mimeType === '*/*') {
                return 'html';
            }

            if ($mimeType === 'text/plain') {
                return 'text';
            }

            if ($mimeType === 'application/json') {
                return 'json';
            }

            if ($mimeType === 'application/xml' || $mimeType === 'text/xml') {
                return 'xml';
            }
        }

        return 'html';
    }

    private function getSortedMimeTypesByHeader(): array
    {
        if (!$this->acceptHeader) {
            return [];
        }

        $mimeTypes = [];

        foreach (explode(',', $this->acceptHeader) as $acceptParameter) {
            $parts = explode(';', $acceptParameter);

            if (!isset($parts[0]) || isset($mimeTypes[$parts[0]]) || !($mimeType = strtolower(trim($parts[0])))) {
                continue;
            }

            if (!isset($parts[1])) {
                $mimeTypes[$mimeType] = 1.0;
                continue;
            }

            if (preg_match('/^\s*q=\s*(0(?:\.\d{1,3})?|1(?:\.0{1,3})?)\s*$/i', $parts[1], $matches)) {
                $mimeTypes[$mimeType] = (float) ($matches[1] ?? 1.0);
            }
        }

        uasort($mimeTypes, static fn(float $a, float $b) => ($a === $b) ? 0 : ($a > $b ? -1 : 1));
        return array_keys($mimeTypes);
    }
}
