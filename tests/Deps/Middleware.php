<?php

namespace Tests\Az\Route\Deps;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use HttpSoft\Response\TextResponse;

class Middleware implements MiddlewareInterface
{
    private string $str;
    private bool $abort;

    public function __construct(string $str, bool $abort = false)
    {
        $this->str = $str;
        $this->abort = $abort;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->abort) {
            return new TextResponse('Abort');
        }

        $str = $request->getAttribute('str', '');
        $str .= $this->str;

        return $handler->handle($request->withAttribute('str', $str));
    }
}
