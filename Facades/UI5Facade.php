<?php
namespace exface\UI5Facade\Facades;

use exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\Interfaces\DataTypes\DataTypeInterface;
use exface\Core\Facades\AbstractAjaxFacade\Formatters\JsDateFormatter;
use exface\Core\Facades\AbstractAjaxFacade\Formatters\JsTimeFormatter;
use exface\UI5Facade\Facades\Formatters\UI5DateFormatter;
use exface\UI5Facade\Facades\Formatters\UI5DefaultFormatter;
use exface\Core\DataTypes\TimestampDataType;
use exface\UI5Facade\Facades\Formatters\UI5DateTimeFormatter;
use exface\Core\Facades\AbstractAjaxFacade\Formatters\JsBooleanFormatter;
use exface\UI5Facade\Facades\Formatters\UI5BooleanFormatter;
use exface\Core\Facades\AbstractAjaxFacade\Formatters\JsNumberFormatter;
use exface\UI5Facade\Facades\Formatters\UI5NumberFormatter;
use exface\UI5Facade\Facades\Middleware\UI5TableUrlParamsReader;
use exface\UI5Facade\Facades\Middleware\UI5WebappRouter;
use exface\UI5Facade\Webapp;
use exface\Core\Interfaces\WidgetInterface;
use exface\UI5Facade\Facades\Elements\UI5AbstractElement;
use exface\Core\Interfaces\Model\UiPageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use exface\Core\Factories\UiPageFactory;
use exface\Core\Exceptions\RuntimeException;
use exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface;
use exface\UI5Facade\UI5Controller;
use exface\Core\Exceptions\LogicException;
use exface\UI5Facade\Facades\Interfaces\UI5ViewInterface;
use exface\UI5Facade\UI5View;
use exface\Core\Facades\AbstractAjaxFacade\Formatters\JsEnumFormatter;
use exface\Core\DataTypes\NumberDataType;
use exface\UI5Facade\Facades\Formatters\UI5EnumFormatter;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\UI5Facade\Facades\Formatters\UI5TimeFormatter;

/**
 * 
 * @method ui5AbstractElement getElement()
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5Facade extends AbstractAjaxFacade
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
     * @see \exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade::init()
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
     * @see \exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade::buildHtmlBody()
     */
    public function buildHtmlBody(WidgetInterface $widget)
    {
        return $this->buildJs($widget);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade::buildJs()
     */
    public function buildJs(\exface\Core\Widgets\AbstractWidget $widget)
    {
        $element = $this->getElement($widget);
        $webapp = $this->getWebapp();
        $controller = $this->createController($element);
        
        if ($widget !== $webapp->getRootPage()->getWidgetRoot()) {
            return <<<JS
    
    {$controller->buildJsController()}
    
    {$controller->getView()->buildJsView()}

JS;
        }
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
     * according to the current facade configuration - and FALSE otherwise.
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
     * @see \exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade::getDataTypeFormatter()
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
            case ($formatter instanceof JsTimeFormatter):
                return new ui5TimeFormatter($formatter);
                break;
            case $formatter instanceof JsDateFormatter:
                if ($formatter->getDataType() instanceof TimestampDataType) {
                    return new ui5DateTimeFormatter($formatter);
                } else {
                    return new ui5DateFormatter($formatter);
                }
                break;
            case $formatter instanceof JsEnumFormatter:
                return new ui5EnumFormatter($formatter);
        }
        
        return new ui5DefaultFormatter($formatter);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Facades\HttpFacadeInterface::getUrlRoutePatterns()
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
     * @see \exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade::getMiddleware()
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
    public function getWebappFacadeFolder() : string
    {
        return $this->getApp()->getDirectoryAbsolutePath() . DIRECTORY_SEPARATOR . 'Facades' . DIRECTORY_SEPARATOR . 'Webapp' . DIRECTORY_SEPARATOR;
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
        $app = new Webapp($this, $id, $this->getWebappFacadeFolder(), $config);
        $this->webapp = $app;
        return $app;
    }
    
    protected function getRequestPage() : UiPageInterface
    {
        if ($this->requestPageAlias === null) {
            throw new RuntimeException('No root page found in request for facade "' . $this->getAliasWithNamespace() . '"!');
        }
        return UiPageFactory::createFromCmsPage($this->getWorkbench()->getCMS(), $this->requestPageAlias);
    }
    
    protected function getWebappDefaultConfig(string $appId) : array
    {
        $config = $this->getConfig();
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
            'assets_path' => $this->getBaseUrl() . '/webapps/' . $appId,
            'pwa_flag' => $config->getOption('PWA.ENABLED'),
            'pwa_theme_color' => $config->getOption('PWA.DEFAULT_STYLE.THEME_COLOR'),
            'pwa_background_color' => $config->getOption('PWA.DEFAULT_STYLE.BACKGROUND_COLOR')
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
        $controller = new ui5Controller($this->getWebapp(), $controllerName, $this->createView($element));
        $element->setController($controller);
        
        $controller->addExternalCss($this->buildUrlToSource('LIBS.FACADE.CSS'));
        $controller->addExternalCss($this->buildUrlToSource('LIBS.FONT_AWESOME.CSS'));
        
        $controller->addExternalModule('libs.font_awesome.plugin', $this->buildUrlToSource('LIBS.FONT_AWESOME.PLUGIN'));
        $controller->addExternalModule('libs.exface.custom_controls', $this->buildUrlToSource('LIBS.FACADE.CUSTOM_CONTROLS'));
        
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
        return new ui5View($this->getWebapp(), $viewName, $element);
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
     * E.g. C:\wamp\www\exface\exface\vendor\exface\UI5Facade\facades\js_openui5\
     * 
     * @return string
     */
    public function getUI5LibrariesPath() : string
    {
        return $this->getApp()->getDirectoryAbsolutePath() . DIRECTORY_SEPARATOR . 'Facades' . DIRECTORY_SEPARATOR . 'js_openui5' . DIRECTORY_SEPARATOR;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade::buildHtmlHeadCommonIncludes()
     */
    public function buildHtmlHeadCommonIncludes() : array
    {
        $tags = $this->buildHtmlHeadIcons();
        $webapp = $this->getWebapp();
        $tags[] = '<link rel="manifest" href="' . $webapp->getComponentUrl() . 'manifest.json">';
        return $tags;
    }
    
    public function buildResponseData(DataSheetInterface $data_sheet, WidgetInterface $widget = null)
    {
        $data = array();
        $data['data'] = array_merge($data_sheet->getRows(), $data_sheet->getTotalsRows());
        $data['recordsFiltered'] = $data_sheet->countRowsInDataSource();
        $data['recordsTotal'] = $data_sheet->countRowsInDataSource();
        if (! is_null($data_sheet->getRowsLimit())) {
            $data['recordsLimit'] = $data_sheet->getRowsLimit();
            $data['recordsOffset'] = $data_sheet->getRowsOffset();
        }
        
        $data['footerRows'] = count($data_sheet->getTotalsRows());
        return $data;
    }
}
?>