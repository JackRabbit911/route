<?php declare(strict_types=1);

namespace Tests\Az\Route;

use Az\Route\Route;
use Az\Route\RouteCollection;
use Az\Route\Middleware\RouteMatchMiddleware;
use Tests\Az\Route\Deps\RequestHandler;
use Tests\Az\Route\Deps\Middleware;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\UriFactory;
use HttpSoft\Runner\MiddlewareResolver;
use HttpSoft\Response\TextResponse;
use PHPUnit\Framework\TestCase;

final class RouteMatchMiddlewareTest extends TestCase
{
    private ServerRequest $request;
    private RouteCollection $router;
    private RequestHandler $handler;

    public function setUp(): void
    {
        $this->request = new ServerRequest();
        $this->router = new RouteCollection();
        $this->handler = new RequestHandler(function ($request) {
            $route = $request->getAttribute(Route::class);

            if (!$route) {
                return new TextResponse('Not Found!', 404);
            }

            return $route->getName();
        });
    }

    public function testMatch()
    {
        $uri = (new UriFactory)->createUri('/foo/bar');
        $request = $this->request->withUri($uri);

        $this->router->group('/foo', function () {
            $this->router->get('/bar', function () {}, 'my.route');
        });

        $sut = new RouteMatchMiddleware($this->router);
        $response = $sut->process($request, $this->handler);

        $this->assertEquals('my.route', $response->getBody()->getContents());
        $this->assertEquals('200', $response->getStatusCode());
    }

    public function testNotMatch()
    {
        $uri = (new UriFactory)->createUri('/foo/baz');
        $request = $this->request->withUri($uri);

        $this->router->group('/foo', function () {
            $this->router->get('/bar', function () {}, 'my.route');
        });

        $sut = new RouteMatchMiddleware($this->router);
        $response = $sut->process($request, $this->handler);

        $this->assertEquals('Not Found!', $response->getBody()->getContents());
        $this->assertEquals(404, $response->getStatusCode());
    }
}    
