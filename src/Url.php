<?php

namespace Leaf;

class Url
{
    /**
     * 生成Url
     *
     * @param $path
     * @param array $params
     * @param bool $absoluteUrl
     * @return string
     */
    public static function to($path, $params = array(), $absoluteUrl = false)
    {
        //兼容第二个参数是bool值，简化在没有参数时，生成绝对路径url
        if (is_bool($params)) {
            $absoluteUrl = $params;
            $params = array();
        }

        $script = '';
        if (strpos($_SERVER['REQUEST_URI'], '/' . basename($_SERVER['SCRIPT_NAME'])) !== false) {
            $script = basename($_SERVER['SCRIPT_NAME']);
        }

        $routeVar = Application::$app['router']->routeVar;

        if ($routeVar !== null) {

            if (array_key_exists($routeVar, $params)) {
                unset($params[$routeVar]);
            }
            $params = array($routeVar => ltrim($path, '/')) + $params;

            $pathInfo = '';
        } else {
            $pathInfo = '/' . ltrim($path, '/');
        }

        $query = '';
        if (count($params) > 0) {
            $query = '?' . http_build_query($params);
        }

        $host = '';
        if ($absoluteUrl) {
            $host = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        }

        return $host . static::asset($script) . $pathInfo . $query;
    }

    /**
     * 生成入口文件所在目录为基础目录的url，默认最后没有斜线"/"
     * 例如 Url::asset('images/logo.png')
     * @param string $asset
     * @param bool $absoluteUrl 是否生成绝对url(http开头)
     * @return string
     */
    public static function asset($asset = '', $absoluteUrl = false)
    {
        if (preg_match('/^http(s)?:\/\//i', $asset)) { // "http://" "https://"
            return $asset;
        }

        if ($asset !== '') {
            $asset = '/' . ltrim($asset, '/');
        }

        $host = '';
        if ($absoluteUrl) {
            $host = Application::$app['request']->getSchemeAndHttpHost();
        }

        return $host . Application::$app['request']->getBasePath() . $asset;
    }
}