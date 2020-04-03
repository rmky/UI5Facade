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
use exface\UI5Facade\Facades\Formatters\UI5EnumFormatter;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\UI5Facade\Facades\Formatters\UI5TimeFormatter;
use exface\Core\Facades\AbstractFacade\AbstractFacade;
use exface\Core\CommonLogic\Model\UiPage;
use exface\Core\CommonLogic\Selectors\UiPageSelector;
use exface\Core\Interfaces\Exceptions\ErrorExceptionInterface;
use exface\Core\Interfaces\Tasks\HttpTaskInterface;
use exface\Core\Exceptions\Facades\FacadeLogicError;

/**
 * Renders SAP Fiori apps using OpenUI5 or SAP UI5.
 * 
 * ## Custom facade options for widgets
 * 
 * ### `Button` and derivatives
 * 
 * - `custom_request_data_script` [string] - allows to process the javascript variable `requestData`
 * right before the action is actually performed. Returning FALSE will prevent the the action!
 * 
 * ### `Input` and derivatives
 * 
 * - `advance_focus_on_enter` [boolean] - makes the focus go to the next focusable widget when ENTER 
 * is pressed (in addition to the default TAB).
 * 
 * @method ui5AbstractElement getElement()
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5Facade extends AbstractAjaxFacade
{
    private $webapp = null;
    
    private $contentDensity = null;
    
    private $theme = null;
    
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
        /* @var $task \exface\Core\CommonLogic\Tasks\HttpTask */
        if ($task = $request->getAttribute($this->getRequestAttributeForTask())) {
            if ($this->webapp === null) {
                if (! $appRootPageAlias = $task->getParameter('webapp')) {
                    if ($task->isTriggeredOnPage()) {
                        $appRootPageAlias = $task->getPageTriggeredOn()->getAliasWithNamespace();
                    }
                }
                if ($appRootPageAlias) {
                    $this->initWebapp($appRootPageAlias);
                } else {
                    throw new FacadeLogicError('Cannot determine webapp from request!');
                }
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
    
    public function getDataTypeFormatter(DataTypeInterface $dataType)
    {
        return parent::getDataTypeFormatter($dataType);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade::getDataTypeFormatter()
     */
    public function getDataTypeFormatterForUI5Bindings(DataTypeInterface $dataType)
    {
        $formatter = $this->getDataTypeFormatter($dataType);
        
        switch (true) {
            case $formatter instanceof JsBooleanFormatter:
                return new UI5BooleanFormatter($formatter);
            case ($formatter instanceof JsNumberFormatter) && $formatter->getDataType()->getBase() === 10:
                return new UI5NumberFormatter($formatter);
            case ($formatter instanceof JsTimeFormatter):
                return new UI5TimeFormatter($formatter);
            case $formatter instanceof JsDateFormatter:
                return new UI5DateFormatter($formatter);
            case $formatter instanceof JsEnumFormatter:
                return new UI5EnumFormatter($formatter);
        }
        
        return new UI5DefaultFormatter($formatter);
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
        $middleware[] = new UI5TableUrlParamsReader($this, 'getInputData', 'setInputData');
        $middleware[] = new UI5WebappRouter($this);
        
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
            'assets_path' => $this->buildUrlToFacade() . '/webapps/' . $appId,
            'pwa_flag' => $config->getOption('PWA.ENABLED'),
            'pwa_theme_color' => $config->getOption('PWA.DEFAULT_STYLE.THEME_COLOR'),
            'pwa_background_color' => $config->getOption('PWA.DEFAULT_STYLE.BACKGROUND_COLOR'),
            'use_combined_viewcontrollers' => $config->getOption('UI5.USE_COMBINED_VIEWCONTROLLERS')
        ];
    }
    
    /**
     * 
     * @param UI5AbstractElement $element
     * @param string $controllerName
     * @return UI5ControllerInterface
     */
    public function createController(UI5AbstractElement $element, $controllerName = null) : UI5ControllerInterface
    {
        if ($controllerName === null) {
            $controllerName = $this->getWebapp()->getControllerName($element->getWidget());
        }
        $controller = new UI5Controller($this->getWebapp(), $controllerName, $this->createView($element));
        $element->setController($controller);
        
        $controller->addExternalCss($this->buildUrlToSource('LIBS.FACADE.CSS'));
        $controller->addExternalCss($this->buildUrlToSource('LIBS.FONT_AWESOME.CSS'));
        
        $controller->addExternalModule('libs.font_awesome.plugin', $this->buildUrlToSource('LIBS.FONT_AWESOME.PLUGIN'));
        $controller->addExternalModule('libs.exface.custom_controls', $this->buildUrlToSource('LIBS.FACADE.CUSTOM_CONTROLS'));
        
        return $controller;
    }
    
    /**
     * 
     * @param UI5AbstractElement $element
     * @param string $viewName
     * @return UI5ViewInterface
     */
    public function createView(UI5AbstractElement $element, $viewName = null) : UI5ViewInterface
    {
        $widget = $element->getWidget();
        if ($viewName === null) {
            $viewName = $this->getWebapp()->getViewName($widget);
        }
        return new UI5View($this->getWebapp(), $viewName, $element);
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
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade::buildResponseData()
     */   
    public function buildResponseData(DataSheetInterface $data_sheet, WidgetInterface $widget = null)
    {
        $data = array();
        $data['rows'] = array_merge($data_sheet->getRows(), $data_sheet->getTotalsRows());
        $data['recordsFiltered'] = $data_sheet->countRowsInDataSource();
        $data['recordsTotal'] = $data_sheet->countRowsInDataSource();
        if (! is_null($data_sheet->getRowsLimit())) {
            $data['recordsLimit'] = $data_sheet->getRowsLimit();
            $data['recordsOffset'] = $data_sheet->getRowsOffset();
        }
        
        $data['footerRows'] = count($data_sheet->getTotalsRows());
        return $data;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade::buildHtmlFromError()
     */
    protected function buildHtmlFromError(ServerRequestInterface $request, \Throwable $exception, UiPageInterface $page = null) : string
    {
        return '';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade::isShowingErrorDetails()
     */
    protected function isShowingErrorDetails() : bool
    {
        return false;
    }
    
    /**
     *
     * @return string
     */
    public function getContentDensity() : string
    {
        return $this->contentDensity ?? 'compact';
    }
    
    /**
     * Controls the size of widgets: cozy (large, touch-friendly) or compact (desktop).
     * 
     * @uxon-property content_density
     * @uxon-type [cozy,compact]
     * @uxon-default compact
     * 
     * @param string $value
     * @return UI5Facade
     */
    public function setContentDensity(string $value) : UI5Facade
    {
        $this->contentDensity = $value;
        return $this;
    }
    
    /**
     *
     * @return string
     */
    public function getTheme() : string
    {
        return $this->theme ?? 'sap_belize';
    }
    
    /**
     * 
     * @uxon-property theme
     * @uxon-type [sap_belize,sap_fiori_3]
     * @uxon-default sap_belize
     * 
     * @param string $value
     * @return UI5Facade
     */
    public function setTheme(string $value) : UI5Facade
    {
        $this->theme = $value;
        return $this;
    }
    
    /**
     * {@inheritdoc}
     * @see AbstractFacade::exportUxonObject()
     */
    public function exportUxonObject()
    {
        $uxon = parent::exportUxonObject();
        if ($this->contentDensity !== null) {
            $uxon->setProperty('content_density', $this->getContentDensity());
        }
        if ($this->theme !== null) {
            $uxon->setProperty('theme', $this->getTheme());
        }
        return $uxon;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade::createResponseUnauthorized()
     */
    protected function createResponseUnauthorized(\Throwable $exception, UiPageInterface $page = null) : ?ResponseInterface
    {
        if ($page === null) {
            if ($exception instanceof ErrorExceptionInterface) {
                $pageAlias = 'ERROR_' . $exception->getId();
            } else {
                $pageAlias = 'ERROR';
            }
            $page = new UiPage(new UiPageSelector($this->getWorkbench(), $pageAlias));
        }
        return parent::createResponseUnauthorized($exception, $page);
    }
}