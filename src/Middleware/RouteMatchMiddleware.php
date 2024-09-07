<?php

declare(strict_types=1);

namespace Az\Route\Middleware;

use Az\Route\Route;
use Az\Route\RouteCollection;
use Az\Route\RouteCollectionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RouteMatchMiddleware implements MiddlewareInterface
{
    private RouteCollection $router;

    public function __construct(RouteCollectionInterface $route)
    {
        $this->router = $route;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$route = $this->router->match($request)) {
            if (!empty($this->router::$methodNotAllowed)) {
                $request = $request->withAttribute('status_code', 405)
                    ->withAttribute('headers', ['Allow' => $this->router::$methodNotAllowed]);
            }

            return $handler->handle($request);
        }

        return $handler->handle($request->withAttribute(Route::class, $route));
    }
}
