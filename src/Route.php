<?php

namespace Az\Route;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Az\Route\RouteCollection;

final class Route implements RouteInterface
{
    private string $name;
    private string $pattern;
    private $handler;
    private array $methods = [];
    private array $tokens = [];
    private array $defaults = [];
    private ?string $host = null;
    private array $parameters = [];
    private array $filters = [];
    private ?bool $ajax = null;
    private array $pipeline = [];
    private string $groupPrefix = '';
    private bool $allowAttribute = false;

    private RouteMatch $matcher;

    public function __construct(string $pattern, $handler, $name = null)
    {
        if ($name) {
            $this->name = $name;
        }
        
        $this->pattern = $pattern;
        $this->handler = $handler;
    }

    public function methods(...$methods)
    {
        $this->methods = array_map(function ($v) {
            return strtoupper($v);
        }, $methods);

        return $this;
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function getHandler(): mixed
    {
        if (is_string($this->handler)) {
            $handler = str_replace('@', '::', $this->handler);
            $handler = explode('::', $handler);
            $handler[1] = $handler[1] ?? $this->parameters['action'] ?? '__invoke';

            return $handler;
        }
        
        return $this->handler;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getTokens(): array
    {
        return $this->tokens;
    }

    public function getPattern(): string
    {
        return (empty($this->pattern)) ? '/' : $this->pattern;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function tokens(array $tokens): self
    {
        foreach ($tokens as $key => $token) {
            if ($token === null) {
                $this->tokens[$key] = null;
                continue;
            }

            $this->tokens[$key] = $token;
        }

        return $this;
    }

    public function defaults(array $defaults): self
    {
        foreach ($defaults as $key => $default) {
            if (!is_scalar($default)) {
                // throw InvalidRouteParameterException::forDefaults($default);
            }

            $this->defaults[$key] = (string) $default;
        }

        return $this;
    }

    public function host(string $host): self
    {
        $this->host = trim($host, '/');
        return $this;
    }

    public function filter(callable $filter): self
    {
        $this->filters[] = $filter;
        return $this;
    }

    public function ajax(?bool $value = null): self
    {
        $this->ajax = $value;
        return $this;
    }

    public function allowAttribute(?bool $allow = null): self|bool
    {
        if ($allow === null) {
            return $this->allowAttribute;
        }

        $this->allowAttribute = $allow;
        return $this;
    }

    public function middleware(...$params): self
    {
        return $this->pipe($params);
    }

    public function pipe(...$params): self
    {
        foreach ($params as $middleware) {
            if (is_array($middleware)) {
                $this->pipeline += $middleware;
            } else {
                $this->pipeline[] = $middleware;
            }
        }

        return $this;
    }

    public function unPipe(...$params)
    {
        $collection = container()->get(RouteCollectionInterface::class);
        
        foreach ($params as $middleware) {
            $collection->unPipe($middleware, $this->groupPrefix);

            $key = array_search($middleware, $this->pipeline);

            if ($key) {
                unset($this->pipeline[$key]);
            }
        }
        
        return $this;
    }

    public function getPipeline()
    {
        return $this->pipeline;
    }

    public function groupPrefix(string $prefix): self
    {
        $this->groupPrefix = $prefix;
        return $this;
    }

    public function getGroupPrefix()
    {
        return $this->groupPrefix;
    }

    public function match(ServerRequestInterface &$request): bool
    {
        $this->matcher = new RouteMatch($this);
        $params = $this->matcher->parse($request, $this->pattern);

        if ($params === false) {
            return false;
        }

        $this->parameters = array_filter($params) + $this->defaults;

        if ($this->allowAttribute) {
            $reflection_method = $this->setByAttribute();
        }

        if ($this->checkHost($request) && $this->checkTokens() && $this->checkAjax($request)
            && $this->checkFilters($request) && $this->checkMethod($request)) {

            if (isset($reflection_method)) {
                $request = $request->withAttribute('reflection_method', $reflection_method);
            }

            return true;
        }

        return false;
    }

    public function path(array $params = []): string
    {
        return (new RouteMatch($this))->path($params);
    }

    private function checkMethod(ServerRequestInterface $request)
    {
        if (!empty($this->methods) 
            && !in_array(strtoupper($request->getMethod()), $this->methods, true)) {
            $methods = implode(', ', array_unique(array_filter($this->methods)));
            RouteCollection::$methodNotAllowed = $methods;
            return false;
        } 

        return true;
    }

    private function setByAttribute()
    {
        $handler = $this->getHandler();

        if (is_array($handler) && method_exists($handler[0], $handler[1])) {
            $reflect = new \ReflectionMethod($handler[0], $handler[1]);
            $attributes = $reflect->getAttributes(__CLASS__) ?? [];
        
            foreach ($attributes as $attribute) {
                $arguments = $attribute->getArguments();

                foreach ($arguments as $method => $arg) {
                    if (is_array($arg)) {
                        call_user_func_array([$this, $method], $arg);
                    } else {
                        call_user_func([$this, $method], $arg);
                    }
                }
            }
        }

        return $reflect ?? null;
    }

    private function checkHost(ServerRequestInterface $request)
    {
        if ($this->host && !preg_match('~^' 
                . str_replace('.', '\\.', $this->host) 
                . '$~i', $request->getUri()->getHost())) {
            return false;
        }

        return true;
    }

    private function checkTokens()
    {
        foreach ($this->tokens as $key => $pattern) {
            if (array_key_exists($key, $this->parameters) 
                && !preg_match('~^(' . $pattern . ')$~i', $this->parameters[$key])) {
                return false;
            }
        }

        return true;
    }

    private function checkFilters(ServerRequestInterface $request)
    {
        foreach ($this->filters as $filter) {
            if (!$filter($this, $request)) {
                return false;
            }
        }

        return true;
    }

    private function checkAjax(ServerRequestInterface $request)
    {
        if ($this->ajax === null) {
            return true;
        }

        return is_ajax($request) === $this->ajax;        
    }
}
