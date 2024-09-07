<?php

declare(strict_types=1);

namespace Az\Route\Middleware;

use Az\Route\NormalizeResponse;
use Az\Route\Route;
use Invoker\InvokerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\ContainerInterface;

final class HandlerWrapperMiddleware implements MiddlewareInterface
{
    use NormalizeResponse;

    private ContainerInterface|InvokerInterface|null $container;
    private $handler;

    public function __construct(ContainerInterface|InvokerInterface|null $container, mixed $handler)
    {
        $this->container = $container;
        $this->handler = $handler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $request->getAttribute((Route::class));
        $params = $route->getParameters();
        $response = $this->call($this->handler, $params);
        return $this->normalizeResponse($request, $response);
    }

    private function call(mixed $action, array $attr = [])
    {
        if ($this->container && method_exists($this->container, 'call')) {
            $result = $this->container->call($action, $attr);
        } else {
            $args = [];

            if (is_array($action) && method_exists($action[0], $action[1])) {
                $reflect = new \ReflectionMethod($action[0], $action[1]);
            } elseif (is_callable($action)) {
                $reflect = new \ReflectionFunction($action);
            } else {
                throw new \InvalidArgumentException('Action to call must be callable or array(class, method)');
            }

            foreach ($reflect->getParameters() as $param) {
                $name = $param->getName();
                $args[$name] = $attr[$name] ?? $param->getDefaultValue() ?? null;
            }

            if (is_callable($action)) {
                $result = $reflect->invokeArgs($args);
            } else {
                $obj = ($this->container) ? $this->container->get($action[0]) : new $action[0];
                $result = $reflect->invokeArgs($obj, $args);
            }           
        }

        return $result;
    }
}
