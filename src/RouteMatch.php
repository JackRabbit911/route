<?php

namespace Az\Route;

use InvalidArgumentException;
use Az\Route\Route;
use Psr\Http\Message\ServerRequestInterface;

final class RouteMatch
{
    private const PLACEHOLDER = '~(?:\{([\w\-]+|[^{}\?]+\?)\})~';
    private const DELIMETER_PLACEHOLDER = '~(?:([^\w\{\}]+)\{[\w\/\-\&\+]+\?\})~i';
    private const DEFAULT_TOKEN = '\w+';
    private const ROOT_PATH_PATTERN = '\/?';

    private Route $route;
    private array $delimeters = [];

    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    public function parse(ServerRequestInterface $request, $pattern)
    {
        $pattern = $this->santizePattern($pattern);
        $path = rawurldecode(rtrim($request->getUri()->getPath(), '/'));

        if (preg_match('~^' . $pattern . '$~i', $path, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return false;
    }

    // public function parsePrefix(ServerRequestInterface $request)
    // {
    //     $path = rawurldecode(rtrim($request->getUri()->getPath(), '/'));
    //     $pattern = $this->santizePattern($this->route->getGroupPrefix($request));

    //     if (preg_match('~^' . $pattern . '~i', $path, $matches)) {
    //         return $matches[0];
    //     }

    //     return '';
    // }

    public function path(array $params = []): string
    {
        $path = preg_replace_callback(self::PLACEHOLDER, function($matches) use ($params) {
            $parameter = $matches[1];
           
            if (substr((string) $parameter, -1) === '?') {
                $parsedOptionalParameters = $this->parsePatternOptional($parameter);
                $substr = '';

                foreach ($parsedOptionalParameters['params'] as $k => $p) {
                    $delimeter = $parsedOptionalParameters['delimeters'][$k] ?? '/';
                    $substr .= $params[$p] ?? $this->route->getDefaults()[$p] ?? '';
                    $substr .= $delimeter;
                }

                return str_replace('__invoke', '', $substr);
            } elseif (!isset($params[$parameter])) {
                throw new InvalidArgumentException(
                    sprintf('The token "%s" is required! Route "%s", "%s"'
                    , $parameter, $this->route->getName(), $this->route->getPattern()));
            }

            return $params[$parameter];
        }, $this->route->getPattern());

        $path = preg_replace('~\/{2,}~', '/', $path);
        return '/' . trim($path, '/');
    }

    private function santizePattern(string $pattern)
    {
        $pattern = str_replace(['+', '~'], ['\+', '\~'], $pattern);
        $pattern = '/' . trim($pattern, '/');

        $pattern = preg_replace_callback(self::DELIMETER_PLACEHOLDER, function (array $m): string {
            $this->delimeters[] = $m[1];
            return substr($m[0], strlen($m[1]));
        }, $pattern);

        return !($pattern === '' || $pattern === '/') // !is root path
            ? preg_replace_callback(self::PLACEHOLDER, function (array $matches): string {
                $parameter = $matches[1];

                return (substr((string) $parameter, -1) === '?') // is optional parameter
                    ? $this->getOptionalReplacement($parameter)
                    : $this->getReplacement($parameter)
                ;
            }, $pattern)
            : self::ROOT_PATH_PATTERN;
    }

    private function getReplacement(string $parameter): string
    {
        return '(?P<' . $parameter . '>' . ($this->route->getTokens()[$parameter] ?? self::DEFAULT_TOKEN) . ')';
    }

    private function getOptionalReplacement(string $parameter): string
    {
        $head = $tail = '';

        $parsedOptionalParameters = $this->parsePatternOptional($parameter);

        foreach ($parsedOptionalParameters['params'] as $k => $parameter) {
            $delimeter = ($k === 0)
                ? array_shift($this->delimeters)
                : array_shift($parsedOptionalParameters['delimeters']);
            $head .= '(?:'. $delimeter . $this->getReplacement($parameter);
            $tail .= ')?';
        }

        return $head . $tail;
    }

    private function parsePatternOptional(string $parameter): array
    {
        $parameter = rtrim($parameter, '?');

        $array = preg_split('~(\W+)~', $parameter, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);

        foreach ($array as $item) {
            if (ctype_punct($item)) {
                $result['delimeters'][] = $item;
            } else { 
                $result['params'][] = $item;
            }
        }

        return array_filter($result);
    }
}
