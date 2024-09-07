<?php declare(strict_types=1);

namespace Tests\Az\Route;

use Az\Route\Route;
use Az\Route\RouteCollection;
use Az\Route\Middleware\RouteMiddleware;
use Tests\Az\Route\Deps\RequestHandler;
use Tests\Az\Route\Deps\Middleware;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\UriFactory;
use HttpSoft\Runner\MiddlewareResolver;
use PHPUnit\Framework\TestCase;

final class RouteMiddlewareTest extends TestCase
{
    private ServerRequest $request;
    private RouteCollection $router;
    private RequestHandler $handler;

    public function setUp(): void
    {
        $this->request = new ServerRequest();
        $this->router = new RouteCollection();
        $this->handler = new RequestHandler(function ($request) {
            return $request->getAttribute('str');
        });
    }

    public function testProcess()
    {
        $group_mw = new Middleware('First ');
        $route_mw = new Middleware('Second');

        $resolver = new MiddlewareResolver();
        
        $uri = (new UriFactory)->createUri('/foo/bar');
        $request = $this->request->withUri($uri);

        $this->router->group('/foo', function () use ($route_mw, $group_mw) {
            $this->router->get('/bar', 'handler')
                ->pipe($route_mw);
            $this->router->pipe($group_mw);
        });

        $route = $this->router->match($request);
        $request = $request->withAttribute(Route::class, $route);

        $routeMiddleware = new RouteMiddleware($this->router, $resolver);
        $response = $routeMiddleware->process($request, $this->handler);
       
        $this->assertEquals('First Second', $response->getBody()->getContents());
        $this->assertEquals('200', $response->getStatusCode());
    }

    public function testAbort()
    {
        $group_mw = new Middleware('First ');
        $route_mw = new Middleware('Second', true);

        $resolver = new MiddlewareResolver();
        
        $uri = (new UriFactory)->createUri('/foo/bar');
        $request = $this->request->withUri($uri);

        $this->router->group('/foo', function () use ($route_mw, $group_mw) {
            $this->router->get('/bar', 'handler')
                ->pipe($route_mw);
            $this->router->pipe($group_mw);
        });

        $route = $this->router->match($request);
        $request = $request->withAttribute(Route::class, $route);

        $routeMiddleware = new RouteMiddleware($this->router, $resolver);
        $response = $routeMiddleware->process($request, $this->handler);
       
        $this->assertEquals('Abort', $response->getBody()->getContents());
        $this->assertEquals('200', $response->getStatusCode());
    }
}
