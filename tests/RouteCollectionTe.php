<?php declare(strict_types=1);

namespace Tests\Az\Route;

use Az\Route\RouteCollection;
use Az\Route\Route;

use HttpSoft\ServerRequest\ServerRequestCreator;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\Uri;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

final class RouteCollectionTest extends TestCase
{
    private RouteCollection $route;

    public function setUp(): void
    {
        if (!defined('SYSPATH')) {
            define('SYSPATH', 'sys/');
            require_once 'sys/autoload.php';
        }
        
        $server = $_SERVER;
        $server['REQUEST_SCHEME'] = 'http';
        $server['SERVER_NAME'] = 'example.com';

        $request = ServerRequestCreator::createFromGlobals($server);

        $route = new RouteCollection($request);

        $route->group('/api', function() use ($route) {
            $route->get('api.index', '', 'handler::index');
            $route->post('api.store', '', 'handler::store');
            $route->get('api.show', '/{id}', 'handler::show');
            $route->put('api.update', '/{id}', 'handler::update');
            $route->delete('api.delete', '/{id}', 'handler::delete');
        });

        $this->route = $route;
    }

    public function testAddMatch()
    {        
        $route = $this->route->match($this->buildRequest('get', '/api'));
        $this->assertInstanceOf('Az\Route\Route', $route);
        $this->assertEquals('api.index', $route->getName());
        $this->assertEquals('handler::index', $route->getHandler());

        $route = $this->route->match($this->buildRequest('post', '/api'));
        $this->assertInstanceOf('Az\Route\Route', $route);
        $this->assertEquals('api.store', $route->getName());
        $this->assertEquals('handler::store', $route->getHandler());

        $route = $this->route->match($this->buildRequest('get', '/api/5'));
        $this->assertInstanceOf('Az\Route\Route', $route);
        $this->assertEquals('api.show', $route->getName());
        $this->assertEquals('handler::show', $route->getHandler());

        $route = $this->route->match($this->buildRequest('put', '/api/5'));
        $this->assertInstanceOf('Az\Route\Route', $route);
        $this->assertEquals('api.update', $route->getName());
        $this->assertEquals('handler::update', $route->getHandler());

        $route = $this->route->match($this->buildRequest('delete', '/api/5'));
        $this->assertInstanceOf('Az\Route\Route', $route);
        $this->assertEquals('api.delete', $route->getName());
        $this->assertEquals('handler::delete', $route->getHandler());
    }

    public function testPath()
    {
        $path = $this->route->path('api.index');
        assertEquals('/api', $path);

        $path = $this->route->path('api.store');
        assertEquals('/api', $path);

        $path = $this->route->path('api.show', ['id' => '5']);
        assertEquals('/api/5', $path);

        // $url = $this->route->url('api.show', ['id' => '5']);
        // assertEquals('http://example.com/api/5', $url);
    }

    private function buildRequest($method, $uri)
    {
        $request = new ServerRequest();
        return $request->withUri(new Uri($uri))->withMethod($method);
    }
}
