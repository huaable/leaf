<?php

namespace Leaf\Facade;

use Leaf\Application;

/**
 * RouteFacade
 *
 * @author  Zou Yiliang
 * @since   1.0
 */
class RouteFacade
{
    /**
     * 注解
     *
     * @param array|string $controllers
     */
    public static function annotation($controllers)
    {
        $controllers = (array)$controllers;
        //根据注解注册路由
        foreach ($controllers as $controller) {
            $ref = new \ReflectionClass($controller);

            //使用缓存
            $cacheFile = Application::$app->getRuntimePath() . '/route/' . md5($ref->getFileName());
            if (file_exists($cacheFile) && filemtime($cacheFile) > filemtime($ref->getFileName())) {
                $routeArguments = unserialize(file_get_contents($cacheFile));
                call_user_func_array(array(Application::$app['router'], 'add'), $routeArguments);
                return;
            }

            $group = self::parseDocCommentTags($ref);
            $groupMiddleware = array();
            if (isset($group['Middleware'])) {
                $groupMiddleware = explode('|', $group['Middleware']);
            }

            $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                $arr = self::parseDocCommentTags($method);

                if (isset($arr['Route'])) {

                    //支持一个方法多条路由规则(多行@Route)
                    foreach ((array)$arr['Route'] as $path) {

                        if (!empty($path)) {

                            $httpMethod = isset($arr['Method']) ? $arr['Method'] : 'any';
                            $callback = $ref->getName() . '@' . $method->getName();

                            $middleware = array();
                            if (isset($arr['Middleware'])) {
                                $middleware = explode('|', $arr['Middleware']);
                            }

                            //static::add($httpMethod, $path, $callback, array_merge($groupMiddleware, $middleware));

                            $routeArguments = array(
                                $httpMethod, $path, $callback, array_merge($groupMiddleware, $middleware)
                            );
                            call_user_func_array(array(Application::$app['router'], 'add'), $routeArguments);

                            //缓存到文件
                            if (!file_exists(dirname($cacheFile))) {
                                mkdir(dirname($cacheFile), 0777, true);
                            }
                            file_put_contents($cacheFile, serialize($routeArguments), LOCK_EX);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param \ReflectionClass | \ReflectionMethod $reflection
     * @return array
     */
    private static function parseDocCommentTags($reflection)
    {
        $comment = $reflection->getDocComment();
        $comment = "@description \n" . strtr(trim(preg_replace('/^\s*\**( |\t)?/m', '', trim($comment, '/'))), "\r", '');
        $parts = preg_split('/^\s*@/m', $comment, -1, PREG_SPLIT_NO_EMPTY);
        $tags = array();
        foreach ($parts as $part) {
            if (preg_match('/^(\w+)(.*)/ms', trim($part), $matches)) {
                $name = $matches[1];
                if (!isset($tags[$name])) {
                    $tags[$name] = trim($matches[2]);
                } elseif (is_array($tags[$name])) {
                    $tags[$name][] = trim($matches[2]);
                } else {
                    $tags[$name] = array($tags[$name], trim($matches[2]));
                }
            }
        }
        return $tags;
    }

    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array(array(Application::$app['router'], $name), $arguments);
    }
}