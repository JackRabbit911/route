<?php

declare(strict_types=1);

namespace Az\Route\Middleware;

use HttpSoft\Message\UriFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UriTrimMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path_info = rawurldecode($request->getServerParams()['PATH_INFO'] ?? '/');
        $path = rawurldecode($request->getUri()->getPath());

        $GLOBALS['URI_PREFIX'] = str_replace($path_info, '/', $path);

        $uri = (new UriFactory)->createUri($path_info);

        return $handler->handle($request->withUri($uri));
    }
}
