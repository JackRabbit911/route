<?php

namespace Az\Route;

use HttpSoft\Response\HtmlResponse;
use HttpSoft\Response\JsonResponse;
use HttpSoft\Response\TextResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use HttpSoft\Response\XmlResponse;

trait NormalizeResponse
{
    private function normalizeResponse(ServerRequestInterface $request, mixed $response): ResponseInterface
    {
        if ($response instanceof ResponseInterface) {
            return $response;
        }
        
        $accept_header = $request->getHeaderLine('Accept');
        $mimeNegotiator = new MimeNegotiator($accept_header);
        $response_type = $mimeNegotiator->getResponseType();

        return match ($response_type) {
            'xml' => new XmlResponse($response),
            'text' => new TextResponse($response),
            'json' => new JsonResponse($response),
            default => new HtmlResponse($response),
        };
    }
}
