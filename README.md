# Route
Fast and flexible routing library
## Install
composer require alpha-zeta/route
## Usage
```php
use Az\Route\RouteCollectionInterface;
use App\Http\Conroller\Home;
use App\Http\Conroller\Image;
...

class App
{
    private RouteCollectionInterface $route;

    public function __construct(RouteCollectionInterface $route, ...)
    {
        $this->route = $route;
        ...
    }

    public function run()
    {
        $this->route->get('/hello', function () {
            return 'Hello, World!';
        });

        $this->route->conroller('/image/{id}/{action?}/{slug?}', Image::class, 'image');

        $this->route->get('/', [Home::class, 'index'], 'home');

        $response = $this->pipeline->process($this->request, $this->defaultHandler);

        $this->emitter->emit($response);
    }
}
```
