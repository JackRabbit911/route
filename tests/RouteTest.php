<?php declare(strict_types=1);

namespace Tests\Az\Route;

use Az\Route\Route;

use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\UriFactory;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class RouteTest extends TestCase
{
    private $request;

    public function setUp(): void
    {
        $this->request = new ServerRequest();
    }

    public function testGetName()
    {
        $route = new Route('/test', 'Class::method', 'test.route');
        $name = $route->getName();

        $this->assertSame('test.route', $name);
    }

    public function testGetHandler()
    {
        $route = new Route('/test', 'Class::method', 'test.route');
        $handler = $route->getHandler();
        $this->assertSame(['Class', 'method'], $handler);

        $route = new Route('/test', ['Class', 'method'], 'test.route');
        $handler = $route->getHandler();
        $this->assertSame(['Class', 'method'], $handler);

        $route = new Route('/test', 'handler', 'test.other.route');
        $handler = $route->getHandler();
        $this->assertSame(['handler', '__invoke'], $handler);

        $route = new Route('/test', function () {
            return true;
        });

        $handler = $route->getHandler();
        $this->assertIsCallable($handler);
    }

    public function testGetDefaults()
    {
        $defaults = [
            'foo' => 'Hello',
            'bar' => 'World',
        ];

        $route = new Route('/test/{foo?}/{bar?}', 'Class::method', 'test.route');
        $route->defaults($defaults);

        $this->assertSame($defaults, $route->getDefaults());
    }

    public function testGetMethods()
    {
        $route = new Route('/test/{foo?}/{bar?}', 'Class::method', 'test.route');
        $route->methods('get', 'post');

        $this->assertSame(['GET', 'POST'], $route->getMethods());
    }

    public function testMethod()
    {
        $route = (new Route('/', 'handler'))->methods('GET', 'post');
        $request = $this->request->withMethod('post');
        $match = $route->match($request);

        $this->assertTrue($match);
    }

    public function testMethodFailed()
    {
        $route = (new Route('/', 'handler'))->methods('GET', 'post');
        $request = $this->request->withMethod('delete');
        $match = $route->match($request);

        $this->assertFalse($match);
    }

    public function testGetTokens()
    {
        $tokens = ['foo' => '/[a-z]+/'];
        $route = new Route('/test/{foo}', 'Class::method', 'test.route');
        $route->tokens($tokens);

        $this->assertSame($tokens, $route->getTokens());
    }

    #[DataProviderExternal(RouteDataProvider::class ,'tokensProvider')]
    public function testTokens($uri, $regex, $result)
    {
        $uriInstance = (new UriFactory)->createUri($uri);
        $request = $this->request->withUri($uriInstance);
        $route = new Route('/{id}', 'handler');
        $route->tokens(['id' => $regex]);
        $match = $route->match($request);

        $this->assertEquals($result, $match);
    }

    public function testGetHost()
    {
        $route = new Route('/test/{foo?}/{bar?}', 'Class::method', 'test.route');
        $this->assertSame(null, $route->getHost());

        $route->host('localhost');
        $this->assertSame('localhost', $route->getHost());
    }

    public function testHost()
    {
        $uriInstance = (new UriFactory)->createUri('/foo/a/b/c');
        $request = $this->request->withUri($uriInstance->withHost('example.com'));
        $route = new Route('/foo/{a}/{b?}/{c?}', 'handler');
        $route->host('example.com');
        $match = $route->match($request);

        $this->assertTrue($match);
    }

    public function testHostFailed()
    {
        $uriInstance = (new UriFactory)->createUri('/foo/a/b/c');
        $request = $this->request->withUri($uriInstance->withHost('example.com'));
        $route = new Route('/foo/{a}/{b?}/{c?}', 'handler');
        $route->host('example.com.me');
        $match = $route->match($request);

        $this->assertFalse($match);
    }

    public function testGetPipeline()
    {
        $route = new Route('/test', 'Class::method', 'test.route');
        $route->pipe('Session', 'Validation');
        $this->assertSame(['Session', 'Validation'], $route->getPipeline());
    }

    public function testGetGroupPrefix()
    {
        $route = new Route('/test', 'Class::method', 'test.route');
        $route->groupPrefix('auth');
        $this->assertSame('auth', $route->getGroupPrefix());
    }


    #[DataProviderExternal(RouteDataProvider::class ,'matchProvider')]
    public function testMatch($pattern, $uri, $params)
    {
        $uriInstance = (new UriFactory)->createUri($uri);
        $request = $this->request->withUri($uriInstance);
        $route = new Route($pattern, 'handler', 'test.route');
        $match = $route->match($request);

        $this->assertTrue($match);
        $this->assertSame($params, $route->getParameters());
    }

    #[DataProviderExternal(RouteDataProvider::class ,'notMatchProvider')]
    public function testNotMatch($pattern, $uri)
    {
        $uriInstance = (new UriFactory)->createUri($uri);
        $request = $this->request->withUri($uriInstance);
        $route = new Route($pattern, 'handler', 'test.route');
        $match = $route->match($request);

        $this->assertFalse($match);
        $this->assertSame([], $route->getParameters());
    }

    #[DataProviderExternal(RouteDataProvider::class ,'pathProvider')]
    public function testPath($pattern, $params, $path)
    {
        $route = new Route($pattern, 'handler');
        $this->assertEquals($path, $route->path($params));
    }

    #[DataProviderExternal(RouteDataProvider::class ,'pathInvalidArgsProvider')]
    public function testPathInvalidArgs($pattern, $params)
    {
        $route = new Route($pattern, 'handler');
        $this->expectException(\InvalidArgumentException::class);
        $route->path($params);
    }
}
