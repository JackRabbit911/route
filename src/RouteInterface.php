<?php

namespace Az\Route;

use Psr\Http\Message\ServerRequestInterface;

interface RouteInterface
{
    public function getName(): string;

    public function getHandler(): mixed;

    public function getParameters(): array;

    public function match(ServerRequestInterface &$request): bool;

    public function path(array $params): string;
}
