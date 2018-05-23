<?php
namespace exface\OpenUI5Template\Templates\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use exface\Core\Interfaces\Templates\HttpTemplateInterface;
use GuzzleHttp\Psr7\Response;
use exface\Core\DataTypes\StringDataType;
use exface\OpenUI5Template\Exceptions\Ui5RouteInvalidException;
use exface\OpenUI5Template\Templates\OpenUI5Template;

/**
 * This PSR-15 middleware routes requests to components of a UI5 webapp.
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5WebappRouter implements MiddlewareInterface
{
    private $template = null;
    
    private $taskAttributeName = null;
    
    private $webappRoot = null;
    
    private $webapp = null;
    
    /**
     * 
     * @param HttpTemplateInterface $template
     */
    public function __construct(OpenUI5Template $template, string $webappRoot = '/webapps/', string $taskAttributeName = 'task')
    {
        $this->template = $template;
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
            return $this->resolve($webappRoute, $handler);
        }
        return $handler->handle($request);
    }
    
    protected function resolve(string $route) : ResponseInterface
    {
        $target = StringDataType::substringAfter($route, '/');
        $appId = StringDataType::substringBefore($route, '/');
        
        $webapp = $this->template->initWebapp($appId);
        try {
            $body = $webapp->get($target);
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
        $json = file_get_contents($this->template->getWebappTemplateFolder() . DIRECTORY_SEPARATOR . 'manifest.json');
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