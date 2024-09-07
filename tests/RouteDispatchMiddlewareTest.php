<?php declare(strict_types=1);

namespace Tests\Az\Route;

use Az\Route\Route;
use Az\Route\RouteCollection;
use Az\Route\Middleware\RouteDispatchMiddleware;
use Tests\Az\Route\Deps\RequestHandler;
use Tests\Az\Route\Deps\Middleware;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\UriFactory;
use HttpSoft\Runner\MiddlewareResolver;
use HttpSoft\Response\TextResponse;
use PHPUnit\Framework\TestCase;

final class RouteDispatchMiddlewareTest extends TestCase
{
    private ServerRequest $request;
    private Route $route;
    private RequestHandler $handler;

    public function setUp(): void
    {
        $this->request = new ServerRequest();
        $router = new RouteCollection();

        $this->route = $router->get('/bar/{slug?}', function ($slug = 'Hello!') {
            return $slug;
        }, 'my.route');

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
        $uri = (new UriFactory)->createUri('/bar');
        $request = $this->request->withUri($uri)->withAttribute(Route::class, $this->route);

        $resolver = new MiddlewareResolver();
        $sut = new RouteDispatchMiddleware($resolver, null);
        $response = $sut->process($request, $this->handler);

        $this->assertEquals('Hello!', $response->getBody()->getContents());
        $this->assertEquals('200', $response->getStatusCode());
    }

    public function testNotMatch()
    {
        $uri = (new UriFactory)->createUri('/foo');
        $request = $this->request->withUri($uri);

        $resolver = new MiddlewareResolver();
        $sut = new RouteDispatchMiddleware($resolver, null);
        $response = $sut->process($request, $this->handler);

        $this->assertEquals('Not Found!', $response->getBody()->getContents());
        $this->assertEquals(404, $response->getStatusCode());
    }
}
