<?php declare(strict_types=1);

namespace Az\Route;

use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface
{
    public function match(ServerRequestInterface $request): mixed;
    public function path(string $name, array $params): string;
}
