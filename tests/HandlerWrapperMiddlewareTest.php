<?php declare(strict_types=1);

namespace Tests\Az\Route;

use Az\Route\Middleware\HandlerWrapperMiddleware;
use Az\Route\Route;
use Az\Route\RouteCollection;
use Az\Route\Middleware\RouteDispatchMiddleware;
use Tests\Az\Route\Deps\RequestHandler;
use Tests\Az\Route\Deps\Middleware;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\UriFactory;
use HttpSoft\Runner\MiddlewareResolver;
use HttpSoft\Response\TextResponse;
use HttpSoft\Response\HtmlResponse;
use HttpSoft\Response\JsonResponse;
use HttpSoft\Response\XmlResponse;
use Psr\Http\Server\MiddlewareInterface;

use PHPUnit\Framework\TestCase;

final class HandlerWrapperMiddlewareTest extends TestCase
{
    private ServerRequest $request;
    private Route $route;
    private RequestHandler $handler;
    private MiddlewareInterface $sut;

    public function setUp(): void
    {
        $uri = (new UriFactory)->createUri('/foo');
        $this->request = new ServerRequest();
        $this->request = $this->request->withUri($uri);

        $router = new RouteCollection();

        $this->route = $router->get('/foo', function () {
            return 'Hello';
        });

        $this->request = $this->request->withAttribute(Route::class, $this->route);

        $this->handler = new RequestHandler(function ($request) {
            return new TextResponse('Not Found!', 404);
        });

        $this->sut = new HandlerWrapperMiddleware(null, $this->route->getHandler());
    }

    public function testHtmlHeader()
    {
        $request = $this->request->withHeader('Accept', 'text/html');

        $response = $this->sut->process($request, $this->handler);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertNotInstanceOf(TextResponse::class, $response);
        $this->assertEquals('Hello', $response->getBody()->getContents());
        $this->assertEquals('200', $response->getStatusCode());
    }

    public function testTextHeader()
    {
        $request = $this->request->withHeader('Accept', 'text/plain');

        $response = $this->sut->process($request, $this->handler);

        $this->assertNotInstanceOf(HtmlResponse::class, $response);
        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertEquals('Hello', $response->getBody()->getContents());
    }

    public function testJsonHeader()
    {
        $request = $this->request->withHeader('Accept', 'application/json');

        $response = $this->sut->process($request, $this->handler);

        $this->assertNotInstanceOf(HtmlResponse::class, $response);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals('"Hello"', $response->getBody()->getContents());
    }

    public function testXMLHeader()
    {
        $request = $this->request->withHeader('Accept', 'application/xml');

        $response = $this->sut->process($request, $this->handler);

        $this->assertNotInstanceOf(HtmlResponse::class, $response);
        $this->assertInstanceOf(XmlResponse::class, $response);
        $this->assertEquals('Hello', $response->getBody()->getContents());
    }
}
