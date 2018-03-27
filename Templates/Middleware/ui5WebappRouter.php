<?php
namespace exface\OpenUI5Template\Templates\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use exface\Core\Interfaces\WorkbenchInterface;
use exface\Core\Interfaces\Templates\HttpTemplateInterface;
use GuzzleHttp\Psr7\Response;

/**
 * This PSR-15 middleware reads inline-filters from the URL and passes them to the task
 * in the attributes of the request.
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5WebappRouter implements MiddlewareInterface
{
    private $template = null;
    
    private $taskAttributeName = null;
    
    private $webappRoot = null;
    
    /**
     * 
     * @param HttpTemplateInterface $template
     */
    public function __construct(HttpTemplateInterface $template, string $webappRoot = '/webapps/', string $taskAttributeName = 'task')
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
        if (($pos = strpos($path, $this->webappRoot)) !== false) {
            $webappRoute = substr($path, ($pos + strlen($this->webappRoot))); 
            return $this->resolve($webappRoute);
        }
        return $handler->handle($request);
    }
    
    protected function resolve(string $route) : ResponseInterface
    {
        $parts = explode('/', $route);
        $target = $parts[1];
        $rootAlias = $parts[0];
        switch (true) {
            case $target === 'manifest.json':
                return $this->getManifest();
            default:
                //return $fallbackHandler->handle($request)
        }
    }
    
    protected function getWebappFolderAbsolutePath() : string
    {
        return $this->template->getApp()->getDirectoryAbsolutePath() . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'webapp';
    }
    
    protected function getManifest() : ResponseInterface
    {
        $json = file_get_contents($this->getWebappFolderAbsolutePath() . DIRECTORY_SEPARATOR . 'manifest.json');
        return $this->createResponseJson($json);
    }
    
    protected function createResponseJson(string $jsonString) : ResponseInterface
    {
        return new Response(200, ['Content-type' => ['application/json;charset=utf-8']], $jsonString);
    }
}