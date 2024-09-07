<?php declare(strict_types=1);

namespace Tests\Az\Route;

final class RouteDataProvider
{
    public static function matchProvider(): array
    {
        return [
            ['/foo/{a}/{b?}/{c?}', '/foo/a/b/c', ['a'=>'a', 'b'=>'b', 'c'=>'c']],
            ['/foo/{a}/{b?}/{c?}', '/foo/a/b', ['a'=>'a', 'b'=>'b']],
            ['/foo/{a}/{b?}/{c?}', '/foo/a', ['a'=>'a']],
            ['/foo/{a}/{b/c?}', '/foo/a/b/c', ['a'=>'a', 'b'=>'b', 'c'=>'c']],
            ['/foo/{a}/{b/c?}', '/foo/a/b', ['a'=>'a', 'b'=>'b']],
            ['/foo/{a}/{b/c?}', '/foo/a', ['a'=>'a']],
            ['/foo/{a}/{b?}-{c?}', '/foo/a/b-c', ['a'=>'a', 'b'=>'b', 'c'=>'c']],
            ['/foo/{a}/{b?}&{c?}', '/foo/a/b&c', ['a'=>'a', 'b'=>'b', 'c'=>'c']],
            ['/foo/{a}/{b?}+{c?}', '/foo/a/b+c', ['a'=>'a', 'b'=>'b', 'c'=>'c']],
            ['/foo/{a}/{b-c?}', '/foo/a/b-c', ['a'=>'a', 'b'=>'b', 'c'=>'c']],
            ['/foo/{a}/{b&c?}', '/foo/a/b&c', ['a'=>'a', 'b'=>'b', 'c'=>'c']],
            ['/foo/{a}/{b+c?}', '/foo/a/b+c', ['a'=>'a', 'b'=>'b', 'c'=>'c']],
            ['/foo/{a}/{b!c?}', '/foo/a/b_c', ['a'=>'a', 'b'=>'b_c']],
            ['/foo+{a}:{b?}', '/foo+a:b', ['a'=>'a', 'b'=>'b']],
            ['/foo/{a}/{b?}+{c?}', '/foo/a/b/', ['a'=>'a', 'b'=>'b']],
            ['/foo/{a}/{b?}/{c?}', '/foo/a/b/c/', ['a'=>'a', 'b'=>'b', 'c'=>'c']],
            ['/foo/{a}/{b/c?}', '/foo/a/b/', ['a'=>'a', 'b'=>'b']],
            ['/{foo?}', '/', []],
            ['/', '/', []],
            ['', '/', []],
        ];
    }

    public static function notMatchProvider(): array
    {
        return [          
            ['/foo/{a}/{b?}/{c?}', '/foo/a/b!'],
            ['/foo/{a}/{b?}/{c?}', '/foo-a'],
            ['/foo/{a}/{b/c?}', '/foo/a/b/c/d'],
            ['/foo/{a}/{b/c?}', '/foo'],
            ['/foo/{a}/{b?}-{c?}', '/foo/a/b/c'],
            ['/foo/{a}/{b?}&{c?}', '/foo/a/b&'],           
            ['/foo/{a}/{b?}', '/bar/a/b'],
        ];
    }

    public static function parametersProvider()
    {
        return [
            ['/foo/{a}/{b?}', '/foo/a', ['a'=>'a', 'b'=>'d']],
            ['/foo/{a}/{b?}', '/foo/a/b', ['a'=>'a', 'b'=>'b']],
        ];
    }

    public static function tokensProvider()
    {
        return [
            ['/123', '\d+', true],
            ['/123a', '\d+', false],
            ['/foo', 'foo|bar', true],
            ['/fou', 'foo|bar', false],
        ];
    }

    public static function pathProvider()
    {
        return [
            ['/foo/{a}/{b?}', ['a' => 'bar'], '/foo/bar'],
            ['/foo/{a}/{b?}', ['a' => 'bar', 'b' => 'baz'], '/foo/bar/baz'],
            ['/foo/{a}/{b/c?}', ['a' => 'bar'], '/foo/bar'],
            ['/foo/{a}/{b/c?}', ['a' => 'bar', 'b' => 'baz'], '/foo/bar/baz'],
            ['/foo/{a}/{b/c?}', ['a' => 'bar', 'b' => 'baz', 'c' => 'qqq'], '/foo/bar/baz/qqq'],
            ['/foo/{a}/{b-c?}', ['a' => 'bar', 'b' => 'baz', 'c' => 'qqq'], '/foo/bar/baz-qqq'],
        ];
    }

    public static function pathInvalidArgsProvider()
    {
        return [
            ['/foo/{a}/{b?}', ['b' => 'bar']],
            ['/foo/{a}/{b?}', ['c' => 'bar']],
            ['/foo/{a}/{b}/{c?}', ['a' => 'bar', 'c' => 'baz']],
            ['/foo/{a}/{b/c?}', ['c' => 'bar']],
        ];
    }
}
