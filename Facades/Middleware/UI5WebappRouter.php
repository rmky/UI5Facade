<?php
namespace exface\UI5Facade\Facades\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use exface\Core\Interfaces\Facades\HttpFacadeInterface;
use GuzzleHttp\Psr7\Response;
use exface\Core\DataTypes\StringDataType;
use exface\UI5Facade\Exceptions\Ui5RouteInvalidException;
use exface\UI5Facade\Facades\UI5Facade;
use exface\Core\Interfaces\Tasks\HttpTaskInterface;
use exface\Core\Facades\AbstractHttpFacade\Middleware\Traits\TaskRequestTrait;

/**
 * This PSR-15 middleware routes requests to components of a UI5 webapp.
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5WebappRouter implements MiddlewareInterface
{
    use TaskRequestTrait;
    
    private $facade = null;
    
    private $taskAttributeName = null;
    
    private $webappRoot = null;
    
    private $webapp = null;
    
    /**
     * 
     * @param HttpFacadeInterface $facade
     */
    public function __construct(UI5Facade $facade, string $webappRoot = '/webapps/', string $taskAttributeName = 'task')
    {
        $this->facade = $facade;
        $this->taskAttributeName = $taskAttributeName;
        $this->webappRoot = $webappRoot;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Psr\Http\Server\MiddlewareInterface::process()
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if (($webappRoute = StringDataType::substringAfter($path, $this->webappRoot)) !== false) {
            return $this->resolve($webappRoute, $this->getTask($request, $this->taskAttributeName, $this->facade));
        }
        return $handler->handle($request);
    }
    
    protected function resolve(string $route, HttpTaskInterface $task = null) : ResponseInterface
    {
        $target = StringDataType::substringAfter($route, '/');
        $appId = StringDataType::substringBefore($route, '/');
        
        $webapp = $this->facade->initWebapp($appId);
        try {
            $body = $webapp->get($target, $task);
        } catch (Ui5RouteInvalidException $e) {
            return new Response(404, [], $e->getMessage());
        }
        $type = pathinfo($target, PATHINFO_EXTENSION);
        
        switch (strtolower($type)) {
            case 'json':
                return $this->createResponseJson($body);
            case 'js':
                return $this->createResponseJs($body);
            default:
                return $this->createResponsePlain($body);
        }
    }
    
    protected function getManifest() : ResponseInterface
    {
        $json = file_get_contents($this->facade->getWebappFacadeFolder() . DIRECTORY_SEPARATOR . 'manifest.json');
        return $this->createResponseJson($json);
    }
    
    protected function createResponseJson(string $jsonString) : ResponseInterface
    {
        return new Response(200, ['Content-type' => ['application/json;charset=utf-8']], $jsonString);
    }
    
    protected function createResponseJs(string $body) : ResponseInterface
    {
        return new Response(200, ['Content-type' => ['application/javascript']], $body);
    }
    
    protected function createResponsePlain(string $body) : ResponseInterface
    {
        return new Response(200, ['Content-type' => ['text/plain']], $body);
    }
}