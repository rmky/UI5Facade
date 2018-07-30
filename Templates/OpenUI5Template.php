<?php
namespace exface\OpenUI5Template\Templates;

use exface\Core\Templates\AbstractAjaxTemplate\AbstractAjaxTemplate;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\Interfaces\DataTypes\DataTypeInterface;
use exface\Core\Templates\AbstractAjaxTemplate\Formatters\JsDateFormatter;
use exface\OpenUI5Template\Templates\Formatters\ui5DateFormatter;
use exface\OpenUI5Template\Templates\Formatters\ui5TransparentFormatter;
use exface\Core\DataTypes\TimestampDataType;
use exface\OpenUI5Template\Templates\Formatters\ui5DateTimeFormatter;
use exface\Core\Templates\AbstractAjaxTemplate\Formatters\JsBooleanFormatter;
use exface\OpenUI5Template\Templates\Formatters\ui5BooleanFormatter;
use exface\Core\Templates\AbstractAjaxTemplate\Formatters\JsNumberFormatter;
use exface\OpenUI5Template\Templates\Formatters\ui5NumberFormatter;
use exface\OpenUI5Template\Templates\Middleware\ui5TableUrlParamsReader;
use exface\OpenUI5Template\Templates\Middleware\ui5WebappRouter;
use exface\OpenUI5Template\Webapp;
use exface\Core\Interfaces\WidgetInterface;
use exface\OpenUI5Template\Templates\Elements\ui5AbstractElement;
use exface\Core\Interfaces\Model\UiPageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use exface\Core\Factories\UiPageFactory;
use exface\Core\Exceptions\RuntimeException;
use exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface;
use exface\OpenUI5Template\WebappController;
use exface\Core\Exceptions\LogicException;
use exface\Core\DataTypes\StringDataType;
use exface\OpenUI5Template\Templates\Interfaces\ui5ViewInterface;
use exface\OpenUI5Template\WebappView;

/**
 * 
 * @method ui5AbstractElement getElement()
 * 
 * @author Andrej Kabachnik
 *
 */
class OpenUI5Template extends AbstractAjaxTemplate
{

    private $requestPageAlias = null;
    
    private $rootView = null;
    
    private $rootController = null;
    
    private $webapp = null;
    
    /**
     * Cache for config key WIDGET.DIALOG.MAXIMIZE_BY_DEFAULT_IN_ACTIONS:
     * @var array [ action_alias => true/false ]
     */
    private $config_maximize_dialog_on_actions = [];

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\AbstractAjaxTemplate::init()
     */
    public function init()
    {
        parent::init();
        $this->setClassPrefix('ui5');
        $this->setClassNamespace(__NAMESPACE__);
    }
    
    public function handle(ServerRequestInterface $request, $useCacheKey = null) : ResponseInterface
    {
        if ($task = $request->getAttribute($this->getRequestAttributeForTask())) {
            $pageAlias = $task->getPageTriggeredOn()->getAliasWithNamespace();
            if ($this->requestPageAlias === null) {
                $this->requestPageAlias = $pageAlias;
            }
            if ($this->webapp === null) {
                $this->initWebapp($pageAlias);
            }
        }
        return parent::handle($request);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\AbstractAjaxTemplate::buildJs()
     */
    public function buildJs(\exface\Core\Widgets\AbstractWidget $widget)
    {
        $element = $this->getElement($widget);
        $webapp = $this->getWebapp();
        $controller = $this->createController($element);
        
        if ($widget !== $webapp->getRootPage()->getWidgetRoot()) {
            return <<<JS
sap.ui.getCore().attachInit(function () {
    
    {$controller->buildJsController()}
    
    {$controller->getView()->buildJsView()}
    
});

JS;
        }
        
        /* TODO remove this if we are going to continue using Component-preload
        
        // If we are showing the root page of the app (more precisely the root widget of the root page),
        // include common views and controllers to avoid extra ajax calls.
        if ($widget === $webapp->getRootPage()->getWidgetRoot()) {
            $baseControllers = '';
            $baseViews = '';
            foreach ($webapp->getBaseControllers() as $controllerPath) {
                $baseControllers .= $this->getWebapp()->get($controllerPath) . "\n\n";
            }
            foreach ($webapp->getBaseViews() as $viewPath) {
                $baseViews .= $this->getWebapp()->get($viewPath);
            }
        }
        
        $controller = $this->createController($element);
        
        return <<<JS
sap.ui.getCore().attachInit(function () {
    
    {$baseControllers}
    
    {$baseViews}

    {$controller->buildJsController()}

    {$controller->getView()->buildJsView()}

});

JS;*/
    }
         
    /**
     * 
     * @param WidgetInterface $widget
     * @return string
     */
    public function getViewName(WidgetInterface $widget, UiPageInterface $appRootPage) : string
    {
        $pageAlias = $widget->getPage()->getAliasWithNamespace() ? $widget->getPage()->getAliasWithNamespace() : $appRootPage->getAliasWithNamespace();
        return $appRootPage->getAliasWithNamespace() . '.view.' . $pageAlias . ($widget->hasParent() ? '.' . $widget->getId() : '');
    }  
    
    /**
     * 
     * @param WidgetInterface $widget
     * @return string
     */
    public function getControllerName(WidgetInterface $widget, UiPageInterface $appRootPage) : string
    {
        $pageAlias = $widget->getPage()->getAliasWithNamespace() ? $widget->getPage()->getAliasWithNamespace() : $appRootPage->getAliasWithNamespace();
        return $appRootPage->getAliasWithNamespace() . '.controller.' . $pageAlias . ($widget->hasParent() ? '.' . $widget->getId() : '');
    }
    
    /**
     * Returns TRUE if a dialog generated by the given action should be maximized by default
     * according to the current template configuration - and FALSE otherwise.
     * 
     * @param ActionInterface $action
     * @return boolean
     */
    public function getConfigMaximizeDialogByDefault(ActionInterface $action)
    {
        // Check the cache first.
        if (array_key_exists($action->getAliasWithNamespace(), $this->config_maximize_dialog_on_actions)) {
            return $this->config_maximize_dialog_on_actions[$action->getAliasWithNamespace()];
        }
        
        // If no cache hit, see if the action matches one of the action selectors from the config or
        // is derived from them. If so, return TRUE and cache the result to avoid having to do the
        // checks again for the next button with the same action. This saves a lot of checks as
        // generic actions like ShowObjectEditDialog are often used for multiple buttons.
        $selectors = $this->getConfig()->getOption('WIDGET.DIALOG.MAXIMIZE_BY_DEFAULT_IN_ACTIONS');
        if ($selectors instanceof UxonObject) {
            foreach ($selectors as $selector) {
                if ($action->is($selector)) {
                    $this->config_maximize_dialog_on_actions[$action->getAliasWithNamespace()] = true;
                    return true;
                }
            }
        }
        
        // Cache FALSE results too.
        $this->config_maximize_dialog_on_actions[$action->getAliasWithNamespace()] = false;
        return false;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\AbstractAjaxTemplate::getDataTypeFormatter()
     */
    public function getDataTypeFormatter(DataTypeInterface $dataType)
    {
        $formatter = parent::getDataTypeFormatter($dataType);
        
        switch (true) {
            case $formatter instanceof JsBooleanFormatter:
                return new ui5BooleanFormatter($formatter);
                break;
            case ($formatter instanceof JsNumberFormatter) && $formatter->getDataType()->getBase() === 10:
                return new ui5NumberFormatter($formatter);
                break;
            case $formatter instanceof JsDateFormatter:
                if ($formatter->getDataType() instanceof TimestampDataType) {
                    return new ui5DateTimeFormatter($formatter);
                } else {
                    return new ui5DateFormatter($formatter);
                }
                break;
        }
        
        return new ui5TransparentFormatter($formatter);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Templates\HttpTemplateInterface::getUrlRoutePatterns()
     */
    public function getUrlRoutePatterns() : array
    {
        return [
            "/[\?&]tpl=ui5/",
            "/\/api\/ui5[\/?]/"
        ];
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\AbstractAjaxTemplate::getMiddleware()
     */
    protected function getMiddleware() : array
    {
        $middleware = parent::getMiddleware();
        $middleware[] = new ui5TableUrlParamsReader($this, 'getInputData', 'setInputData');
        $middleware[] = new ui5WebappRouter($this);
        return $middleware;
    }
    
    /**
     * 
     * @return string
     */
    public function getWebappTemplateFolder() : string
    {
        return $this->getApp()->getDirectoryAbsolutePath() . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . 'Webapp' . DIRECTORY_SEPARATOR;
    }
    
    public function getWebapp() : Webapp
    {
        return $this->webapp;
    }
    
    /**
     * 
     * @param string $id
     * @return Webapp
     */
    public function initWebapp(string $id, array $config = null) : Webapp
    {
        if ($this->webapp !== null) {
            throw new LogicException('Cannot initialize webapp in "' . $this->getAlias() . '": it had been already initialized previously!');
        }
        $config = $config === null ? $this->getWebappDefaultConfig($id) : $config;
        $app = new Webapp($this, $id, $this->getWebappTemplateFolder(), $config);
        $this->webapp = $app;
        return $app;
    }
    
    protected function getRequestPage() : UiPageInterface
    {
        if ($this->requestPageAlias === null) {
            throw new RuntimeException('No root page found in request for template "' . $this->getAliasWithNamespace() . '"!');
        }
        return UiPageFactory::createFromCmsPage($this->getWorkbench()->getCMS(), $this->requestPageAlias);
    }
    
    protected function getWebappDefaultConfig(string $appId) : array
    {
        return [
            'app_id' => $appId,
            'component_path' => str_replace('.', '/', $appId),
            //'name' => 'axenox WMS MDE', 
            //'current_version' => '1.0.0', 
            //'current_version_date' => '2018-04-25 14:10:40',
            //'app_title' => '{{appTitle}}', 
            'ui5_min_version' => '1.52', 
            //'root_page_alias' => 'axenox.wms.mde-verladen-x', 
            'root_url' => '/exface',
            //'ui5_source' => 'https://openui5.hana.ondemand.com/resources/sap-ui-core.js', 
            //'ui5_theme' => 'sap_belize', 
            'ui5_app_control' => 'sap.m.App',
            //'app_subTitle' => '', 
            //'app_shortTitle' => '', 
            //'app_info' => '', 
            //'app_description' => '{{appDescription}}', 
            //'assets_path' => './'
        ];
    }
    
    /**
     * 
     * @param ui5AbstractElement $element
     * @param string $controllerName
     * @return ui5ControllerInterface
     */
    public function createController(ui5AbstractElement $element, $controllerName = null) : ui5ControllerInterface
    {
        if ($controllerName === null) {
            $controllerName = $this->getControllerName($element->getWidget(), $this->getWebapp()->getRootPage());
        }
        $controller = new WebappController($this->getWebapp(), $controllerName, $this->createView($element));
        $element->setController($controller);
        
        $controller->addExternalCss($this->buildUrlToSource('LIBS.TEMPLATE.CSS'));
        $controller->addExternalCss($this->buildUrlToSource('LIBS.FONT_AWESOME.CSS'));
        
        $controller->addExternalModule('libs.font_awesome.plugin', $this->buildUrlToSource('LIBS.FONT_AWESOME.PLUGIN'));
        $controller->addExternalModule('libs.exface.custom_controls', $this->buildUrlToSource('LIBS.TEMPLATE.CUSTOM_CONTROLS'));
        
        return $controller;
    }
    
    /**
     * 
     * @param ui5AbstractElement $element
     * @param string $viewName
     * @return ui5ViewInterface
     */
    public function createView(ui5AbstractElement $element, $viewName = null) : ui5ViewInterface
    {
        $widget = $element->getWidget();
        if ($viewName === null) {
            $viewName = $this->getViewName($widget, $this->getWebapp()->getRootPage());
        }
        return new WebappView($this->getWebapp(), $viewName, $element);
    }
    
    public function getUI5LibrariesUsed() : array
    {
        return [
            'sap.m',
            'sap.tnt',
            'sap.ui.unified',
            'sap.ui.commons',
            'sap.ui.table',
            'sap.f',
            'sap.uxap'
        ];
    }
    
    /**
     * Returns the absolute path to the UI5 sources ending with a directory separator.
     * 
     * E.g. C:\wamp\www\exface\exface\vendor\exface\OpenUI5Template\templates\js_openui5\
     * 
     * @return string
     */
    public function getUI5LibrariesPath() : string
    {
        return $this->getApp()->getDirectoryAbsolutePath() . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . 'js_openui5' . DIRECTORY_SEPARATOR;
    }
}
?>