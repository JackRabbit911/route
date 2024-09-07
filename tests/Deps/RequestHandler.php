<?php declare(strict_types=1);

namespace Tests\Az\Route\Deps;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use HttpSoft\Response\TextResponse;

final class RequestHandler implements RequestHandlerInterface
{
    private $callable;

    public function __construct(?\Closure $callable = null) {
        $this->callable = $callable;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $str = call_user_func($this->callable, $request);
        return (is_string($str)) ? new TextResponse($str) : $str;
    }
}
