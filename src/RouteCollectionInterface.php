<?php

namespace Az\Route;

use Psr\Http\Message\ServerRequestInterface;

interface RouteCollectionInterface
{
    public function group(string $prefix, callable $callback): self;

    public function add(string $pattern, $handler, ?string $name = null): Route;

    public function get(string $pattern, $handler, ?string $name = null): Route;

    public function post(string $pattern, $handler, ?string $name = null): Route;

    public function put(string $pattern, $handler, ?string $name = null): Route;

    public function patch(string $pattern, $handler, ?string $name = null): Route;

    public function delete(string $pattern, $handler, ?string $name = null): Route;

    public function options(string $pattern, $handler, ?string $name = null): Route;

    public function head(string $pattern, $handler, ?string $name = null): Route;

    public function any(string $pattern, $handler, ?string $name = null): Route;

    public function getRoute(string $name): Route;

    public function getAll(): array;

    public function has(string $name): bool;

    public function addRoute(Route $route): void;

    public function remove(string $name): Route;

    public function clear(): void;

    public function match(ServerRequestInterface $request);

    public function path(string $name, array $params): string;
}
