<?php

namespace Az\Route\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use HttpSoft\Runner\MiddlewarePipeline;
use Az\Route\Route;
use Az\Route\RouteCollection;
use Az\Route\RouteCollectionInterface;
use HttpSoft\Runner\MiddlewareResolverInterface;

final class RouteMiddleware implements MiddlewareInterface
{
    private RouteCollection $collection;
    private MiddlewareResolverInterface $resolver;

    public function __construct(
        RouteCollectionInterface $collection,
        MiddlewareResolverInterface $resolver
    )
    {
        $this->collection = $collection;
        $this->resolver = $resolver;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pipeline = new MiddlewarePipeline();
        $route = $request->getAttribute(Route::class);

        if (!$route) {
            return $handler->handle($request);
        }

        $routePrefix = $route->getGroupPrefix();

        foreach ($this->collection->getPipeline() as $item) {
            $middleware = $item[1];
            $groupPrefix = $item[0];

            if (strpos($routePrefix, $groupPrefix) === 0) {
                $pipeline->pipe($this->resolver->resolve($middleware));
            }
        }

        foreach ($route->getPipeline() as $middleware) {
            $pipeline->pipe($this->resolver->resolve($middleware));
        }

        return $pipeline->process($request, $handler);
    }
}
