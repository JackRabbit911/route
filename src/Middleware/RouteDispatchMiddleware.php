<?php

declare(strict_types=1);

namespace Az\Route\Middleware;

use Az\Route\NormalizeResponse;
use Az\Route\Route;
use HttpSoft\Runner\MiddlewareResolverInterface;
use Invoker\InvokerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\ContainerInterface;
use DI\FactoryInterface;

final class RouteDispatchMiddleware implements MiddlewareInterface
{
    use NormalizeResponse;

    private MiddlewareResolverInterface $resolver;
    private ContainerInterface|InvokerInterface|FactoryInterface|null $container;

    public function __construct(
        MiddlewareResolverInterface $resolver, 
        ?ContainerInterface $container = null)
    {
        $this->resolver = $resolver;
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$route = $request->getAttribute(Route::class)) {
            return $handler->handle($request);
        }

        $GLOBALS['request'] = &$request;

        $routeHandler = $this->resolve($request, $route->getHandler());
        $middleware = $this->resolver->resolve($routeHandler);

        return $middleware->process($request, $handler);
    }

    private function resolve(&$request, $handler)
    {
        if (defined('STRICT_MODE') && STRICT_MODE === true) {
            return $handler;
        }

        if (is_array($handler)) {
            $controller = $handler[0];
            $action = $handler[1] ?? '__invoke';

            if (method_exists($controller, $action)) {
                if (is_a($controller, MiddlewareInterface::class, true) 
                || is_a($controller, RequestHandlerInterface::class, true)) {
                    $request = $request->withAttribute('action', $action);
                } else {
                    $handler = new HandlerWrapperMiddleware($this->container, [$controller, $action]);
                }
            }
        } elseif (is_callable($handler)) {
            $handler = new HandlerWrapperMiddleware($this->container, $handler);
        }
        
        return $handler;
    }
}
