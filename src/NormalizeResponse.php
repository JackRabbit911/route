<?php

namespace Az\Route;

use HttpSoft\Response\HtmlResponse;
use HttpSoft\Response\JsonResponse;
use HttpSoft\Response\TextResponse;
use Sys\Exception\MimeNegotiator;
use Sys\Helper\ResponseType;
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
        $response_type = ResponseType::from($response_type);

        return match ($response_type) {
            ResponseType::xml => new XmlResponse($response),
            ResponseType::text => new TextResponse($response),
            ResponseType::json => new JsonResponse($response),
            default => new HtmlResponse($response),
        };
    }
}
