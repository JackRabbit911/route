<?php declare(strict_types=1);

namespace Tests\Az\Route;

use Az\Route\Route;
use Az\Route\RouteCollection;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\UriFactory;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class RouteCollectionTest extends TestCase
{
    private $request;
    private $router;

    public function setUp(): void
    {
        $this->request = new ServerRequest();
        $this->router = new RouteCollection();
    }

    public function testAdd()
    {
        $route = $this->router->add('/foo', 'handler', 'foo');

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('foo', $route->getName());
        $this->assertSame([], $route->getMethods());
        $this->expectException(\RuntimeException::class);
        $route = $this->router->add('/foo', 'handler', 'foo');
    }

    public function testGet()
    {
        $route = $this->router->get('/foo', 'handler', 'foo');
        $this->assertEqualsCanonicalizing(['GET', 'HEAD', 'OPTIONS'], $route->getMethods());
    }

    public function testPost()
    {
        $route = $this->router->post('/foo', 'handler', 'foo');
        $this->assertContains('POST', $route->getMethods());
        $this->assertNotContains('GET', $route->getMethods());
    }

    public function testPut()
    {
        $route = $this->router->put('/foo', 'handler', 'foo');
        $this->assertContains('PUT', $route->getMethods());
        $this->assertNotContains('GET', $route->getMethods());
    }

    public function testPatch()
    {
        $route = $this->router->patch('/foo', 'handler', 'foo');
        $this->assertContains('PATCH', $route->getMethods());
        $this->assertNotContains('GET', $route->getMethods());
    }

    public function testDelete()
    {
        $route = $this->router->delete('/foo', 'handler', 'foo');
        $this->assertContains('DELETE', $route->getMethods());
        $this->assertNotContains('POST', $route->getMethods());
    }

    public function testAny()
    {
        $route = $this->router->any('/foo', 'handler', 'foo');
        $this->assertSame([], $route->getMethods());
    }

    public function testController()
    {
        $route = $this->router->controller('/foo/{action?}', 'handler', 'foo');
        $this->assertSame([], $route->getMethods());
    }

    public function testGetAll()
    {
        $foo_get = $this->router->get('/foo', 'handler', 'foo.get');
        $foo_post = $this->router->post('/foo', 'handler', 'foo.post');
        $all = $this->router->getAll();
        $this->assertSame(['foo.get' => $foo_get, 'foo.post' => $foo_post], $all);
    }

    #[DataProviderExternal(RouteDataProvider::class ,'tokensProvider')]
    public function testTokens($uri, $regex, $result)
    {
        $uriInstance = (new UriFactory)->createUri('/foo' . $uri);
        $request = $this->request->withUri($uriInstance);

        $this->router->group('/foo', function () use ($regex) {
            $this->router->get('/{id}', 'handler');
            $this->router->tokens(['id' => $regex]);
        });

        $match = $this->router->match($request);

        if ($match instanceof Route) {
            $match = true;
        }

        $this->assertEquals($result, $match);
    }

    public function testPipe()
    {
        $expected = [
            ["/foo", "SomeMiddleware"],
            ["", "OtherMiddleware"],
        ];

        $this->router->group('/foo', function () {
            $this->router->get('/{id}', 'handler');
            $this->router->pipe('SomeMiddleware');
        })->pipe('Validation');

        $this->router->pipe('OtherMiddleware');
        $this->router->unPipe('Validation');

        $pipeline = $this->router->getPipeline();

        $this->assertEquals($expected, $pipeline);
    }
}
