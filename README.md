# Route
Fast and flexible routing library
## Install
composer require alpha-zeta/route
## Usage
```php
use Az\Route\RouteCollectionInterface;
use App\Http\Conroller\Home;

class App {

    private RouteCollectionInterface $route;

    public function __construct(RouteCollectionInterface $route)
    {
        $this->route = $route;
    }

    public function run()
    {
        $this->route->get('/hello', function () {
            return 'Hello, World!';
        });

        $this->route->conroller('/image/{id}/{action?}/{slug?}', Image::class, 'image')

        $this->route->get('/', Home::class, 'home');
    }
}
```
