<?php

namespace Bitrock\Router\FastRoute;
use Bitrock\Router\Router;
use FastRoute;
use FastRoute\RouteCollector;
use Bitrock\Container\Container;

class FastRouter extends Router
{
    public CONST METHOD = 'METHOD';
    public CONST URL = 'URL';
    public CONST HANDLER = 'HANDLER';

    protected $routeList;
    protected $containerDefinitions = [];

    public function handle()
    {
        $dispatcher = $this->getDispatcher();
        if ($dispatcher) {
            $httpMethod = $_SERVER['REQUEST_METHOD'];
            $uri = $_SERVER['REQUEST_URI'];
            if (false !== $pos = strpos($uri, '?')) {
                $uri = substr($uri, 0, $pos);
            }

            $uri = rawurldecode($uri);
            $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
            switch ($routeInfo[0]) {
                case FastRoute\Dispatcher::NOT_FOUND:
                    // ... 404 Not Found
                    break;
                case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                    $allowedMethods = $routeInfo[1];
                    // ... 405 Method Not Allowed
                    break;
                case FastRoute\Dispatcher::FOUND:
                    $handler = $routeInfo[1];
                    $vars = $routeInfo[2];
                    $vars['POST'] = $_POST;
                    if (empty($vars['POST'])) {
                        $postData = file_get_contents('php://input');
                        $data = json_decode($postData, true);
                        $vars['POST'] = $data;
                    }

                    [$class, $method] = explode("/", $handler, 2);
                    $container = new Container();
                    if (!empty($this->containerDefinitions)) {
                        $container->setConfig($this->containerDefinitions);
                    }
                    try {
                        $container->handle([$class, $method], $vars);
                    } catch (\Exception $e) {
                        throw $e;
                    }
                    break;
            }
        }

        return false;
    }

    private function getDispatcher()
    {
        $routeList = $this->getRouteList();

        if (!empty($routeList)) {
            return FastRoute\simpleDispatcher(function (RouteCollector $r) use ($routeList) {
                foreach ($routeList as $route) {
                    $r->addRoute(
                        $route[static::METHOD],
                        $route[static::URL],
                        $route[static::HANDLER]
                    );
                }
            });
        }

        return false;
    }

    public function getRouteList()
    {
        return $this->routeList;
    }

    public function addRoute(array $routeArray = [])
    {
        if (
            empty($routeArray[static::METHOD])
            || empty($routeArray[static::URL])
            || empty($routeArray[static::HANDLER])
            || empty($routeArray[static::HANDLER][0])|| empty($routeArray[static::HANDLER][1])
        ) return false;

        $routeArrayPrepared = [];

        $routeArrayPrepared[static::URL] = $routeArray[static::URL];
        $routeArrayPrepared[static::METHOD] = $routeArray[static::METHOD];
        $routeArrayPrepared[static::HANDLER] = $this->setHandlerString(
            $routeArray[static::HANDLER][0],
            $routeArray[static::HANDLER][1]
        );
        $this->routeList[] = $routeArrayPrepared;

        return true;
    }

    /** Метод для установления настроек DI-контейнера */
    public function setContainerDefinitions($array = [])
    {
        if (!empty($array)) $this->containerDefinitions = $array;
    }

    /** @return string */
    private function setHandlerString($className, $methodName)
    {
        if (empty($className) || empty($methodName)) return false;

        return $className . '/' . $methodName;
    }
}