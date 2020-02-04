<?php
namespace exface\UI5Facade;

use exface\Core\Interfaces\WorkbenchDependantInterface;
use exface\Core\CommonLogic\Workbench;
use exface\UI5Facade\Facades\UI5Facade;
use exface\UI5Facade\Exceptions\UI5RouteInvalidException;
use exface\Core\DataTypes\StringDataType;
use exface\Core\Exceptions\LogicException;
use exface\Core\Factories\UiPageFactory;
use exface\Core\Interfaces\Selectors\AliasSelectorInterface;
use exface\Core\Exceptions\FileNotFoundError;
use exface\Core\Exceptions\RuntimeException;
use exface\Core\Interfaces\Model\UiPageInterface;
use exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface;
use exface\Core\Interfaces\WidgetInterface;
use exface\UI5Facade\Facades\Interfaces\UI5ViewInterface;
use GuzzleHttp\Psr7\Uri;
use exface\Core\Interfaces\Log\LoggerInterface;
use exface\Core\Interfaces\Tasks\HttpTaskInterface;
use exface\Core\Interfaces\Widgets\iTriggerAction;
use exface\Core\Interfaces\Tasks\ResultWidgetInterface;
use exface\Core\Interfaces\Actions\iShowWidget;
use exface\Core\Events\Widget\OnPrefillChangePropertyEvent;
use exface\Core\Exceptions\Facades\FacadeLogicError;
use exface\Core\Interfaces\Exceptions\ErrorExceptionInterface;
use exface\Core\Interfaces\Exceptions\ExceptionInterface;
use exface\Core\Exceptions\Facades\FacadeRoutingError;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\Events\DataSheet\OnBeforeReadDataEvent;

class Webapp implements WorkbenchDependantInterface
{
    private $workbench = null;
    
    private $facade = null;
    
    private $appId = null;
    
    private $rootPage = null;
    
    private $facadeFolder = null;
    
    private $config = [];
    
    private $controllers = [];
    
    private $models = [];
    
    public function __construct(UI5Facade $facade, string $ui5AppId, string $facadeFolder, array $config)
    {
        $this->workbench = $facade->getWorkbench();
        $this->facade = $facade;
        $this->appId = $ui5AppId;
        $this->facadeFolder = $facadeFolder;
        $this->config = $config;
    }
    
    public function getWorkbench() : Workbench
    {
        return $this->workbench;
    }
    
    /**
     * Prefills the given widget if required by the task.
     * 
     * This method takes care of prefilling widgets loaded via app routing (i.e. in views and viewcontrollers).
     * These resources are loaded without any input data, so just performing the corresponding task would not
     * result in a prefill. If the widget is actually supposed to be prefilled (e.g. an edit dialog), we need
     * that prefill operation to figure out model bindings. 
     * 
     * Technically, the task is being modified in a way, that the prefill is performed. Than, the task is being
     * handled by the workbench and the resulting prefilled widget is returned.
     * 
     * @param WidgetInterface $widget
     * @param HttpTaskInterface $task
     * 
     * @return WidgetInterface
     */
    protected function handlePrefill(WidgetInterface $widget, HttpTaskInterface $task) : WidgetInterface
    {
        // The whole trick only makes sense for widgets, that are created by actions (e.g. via button press).
        // Otherwise we would not be able to find out, if the widget is supposed to be prefilled because 
        // actions controll the prefill.
        if ($widget->getParent() instanceof iTriggerAction) {
            $button = $widget->getParent();
            // Make sure, the task has page and widget selectors (they are not set automatically, for routed URLs)
            $task->setPageSelector($button->getPage()->getSelector());
            $task->setWidgetIdTriggeredBy($button->getId());
            
            // Now see, what action, we are dealing with and whether it requires a prefill
            $action = $button->getAction();
            if (($action instanceof iShowWidget) && ($action->getPrefillWithInputData() || $action->getPrefillWithPrefillData() || $action->getPrefillWithFilterContext())) {
                // If a prefill is required, but there is no input data (e.g. a button with ShowObjectEditDialog was pressed and
                // the corresponding view or viewcontroller is being loaded), just fake the input data by reading the first row of
                // the default data for the input widget. Since we are just interested in model bindings, id does not matter, what
                // data we use as prefill - only it's structure matters!
                if (! $task->hasInputData() && $action->getPrefillWithInputData()) {
                    try {
                        $inputData = $button->getInputWidget()->prepareDataSheetToRead();
                        $inputData = $this->handlePrefillGenerateDummyData($inputData);
                        $task->setInputData($inputData);
                    } catch (\Throwable $e) {
                        throw new FacadeLogicError('Cannot load prefill data for UI5 view. ' . $e->getMessage(), null, $e);
                    }
                }
                if (! $task->hasPrefillData() && $action->getPrefillWithInputData() === false && $action->getPrefillWithPrefillData()) {
                    try {
                        $prefillData = $button->getInputWidget()->prepareDataSheetToRead();
                        $prefillData = $this->handlePrefillGenerateDummyData($prefillData);
                        $task->setPrefillData($prefillData);
                    } catch (\Throwable $e) {
                        throw new FacadeLogicError('Cannot load prefill data for UI5 view. ' . $e->getMessage(), null, $e);
                    }
                }
                
                // Listen to OnPrefillChangePropertyEvent and generate model bindings from it
                $model = $this->createViewModel($this->facade->getViewName($widget, $this->getRootPage()));
                $eventHandler = function($event) use ($model) {
                    $model->setBindingPointer($event->getWidget(), $event->getPropertyName(), $event->getPrefillValuePointer());
                };
                $this->getWorkbench()->eventManager()->addListener(OnPrefillChangePropertyEvent::getEventName(), $eventHandler);
            }
            // Overwrite the task's action with the action of the trigger widget to make sure, the prefill is really performed
            $task->setActionSelector($action->getSelector());
            
            // Handle the modified task
            try {
                $result = $this->getWorkbench()->handle($task);
                if ($result instanceof ResultWidgetInterface) {
                    $widget = $result->getWidget();
                }
            } catch (\Throwable $e) {
                // TODO
                throw $e;
            }
        }
        
        return $widget;
    }
    
    /**
     * Adds a dummy-row to the prefill data and makes sure it triggers the prefill logic.
     * 
     * @param DataSheetInterface $dataSheet
     * @return DataSheetInterface
     */
    protected function handlePrefillGenerateDummyData(DataSheetInterface $dataSheet) : DataSheetInterface
    {
        $row = [];
        
        // Make sure, there is a UID column - otherwise no prefill will take place!
        if ($dataSheet->hasUidColumn() === false && $dataSheet->getMetaObject()->hasUidAttribute() === true) {
            $dataSheet->getColumns()->addFromUidAttribute();
        }
        
        // Create a row with empty values for every column
        foreach ($dataSheet->getColumns() as $col) {
            $row[$col->getName()] = '';
        }
        $dataSheet->addRow($row);
        
        // If the resulting sheet has a UID column, make sure it has a value - otherwise
        // the prefill logic will not attempt to ask the target widget for more columns
        // via prepareDataSheetToPrefill() because there would not be a way to read missing
        // values.
        if ($dataSheet->hasUidColumn(false) === true) {
            $dataSheet->getUidColumn()->setValue(0, 1);
        }
        
        // Make sure, that if a read operation is attempted for our dummy data, that will
        // not really take place! Otherwise our data will be removed if there are no rows
        // matching our dummy-UID.
        $this->getWorkbench()->eventManager()->addListener(OnBeforeReadDataEvent::getEventName(), function(OnBeforeReadDataEvent $event) use ($dataSheet) {
            $eventSheet = $event->getDataSheet();
            // Prevent read operations on our dummy-sheet as they will change or even remove our data!
            // If the prefill will cause a read operation, the prefill-sheet will be
            // a copy of our sheet, so we cant simply check if they are equal. However,
            // the prefill sheet will have our values and may have other columns with NULL
            // values.
            if ($eventSheet->getMetaObject()->isExactly($dataSheet->getMetaObject()) === true) {
                $row = $dataSheet->getRow(0);
                foreach ($eventSheet->getRow(0) as $fld => $val) {
                    if ($val !== null && $val !== $row[$fld]) {
                        return;
                    }
                }
                $event->preventRead();
            }
        });
        
        return $dataSheet;
    }
    
    public function get(string $route, HttpTaskInterface $task = null) : string
    {
        switch (true) {
            case $route === 'manifest.json':
                return $this->getManifestJson();
            case $route === 'Component-preload.js' && $this->facade->getConfig()->getOption('UI5.USE_COMPONENT_PRELOAD'):
                return $this->getComponentPreload();
            case StringDataType::startsWith($route, 'i18n/'):
                $lang = explode('_', pathinfo($route, PATHINFO_FILENAME))[1];
                return $this->getTranslation($lang);
            case file_exists($this->getFacadesFolder() . $route):
                return $this->getFromFileTemplate($route);
            case StringDataType::startsWith($route, 'view/'):
                $path = StringDataType::substringAfter($route, 'view/');
                if (StringDataType::endsWith($path, '.view.js')) {
                    $path = StringDataType::substringBefore($path, '.view.js');
                    try {
                        $widget = $this->getWidgetFromPath($path);
                        if ($widget) {
                            return $this->getViewForWidget($widget)->buildJsView();
                        } 
                    } catch (\Throwable $e) {
                        $this->getWorkbench()->getLogger()->logException($e);
                        $errorViewName = $this->getViewNamespace() . str_replace('/', '.', $path);
                        return $this->getErrorView($e, $errorViewName);
                    }
                    $widget = $this->handlePrefill($widget, $task);
                    return '';
                }
            case StringDataType::startsWith($route, 'controller/'):
                $path = StringDataType::substringAfter($route, 'controller/');
                if (StringDataType::endsWith($path, '.controller.js')) {
                    $path = StringDataType::substringBefore($path, '.controller.js');
                    try {
                        $widget = $this->getWidgetFromPath($path);
                        if ($widget) {
                            $widget = $this->handlePrefill($widget, $task);
                            return $this->getControllerForWidget($widget)->buildJsController();
                        }
                    } catch (\Throwable $e) {
                        $this->getWorkbench()->getLogger()->logException($e);
                        return $this->getErrorController($e);
                    }
                    return '';
                }
            case StringDataType::startsWith($route, 'viewcontroller/'):
                $path = StringDataType::substringAfter($route, 'viewcontroller/');
                if (StringDataType::endsWith($path, '.viewcontroller.js')) {
                    $path = StringDataType::substringBefore($path, '.viewcontroller.js');
                    try {
                        $widget = $this->getWidgetFromPath($path);
                        if ($widget) {
                            $widget = $this->handlePrefill($widget, $task);
                            $controller = $this->getControllerForWidget($widget);
                            return $controller->buildJsController() . "\n\n" . $controller->getView()->buildJsView();
                        }
                    } catch (\Throwable $e) {
                        if ($controller) {
                            $errorViewName = $controller->getView()->getName();
                        } else {
                            $errorViewName = $this->getViewNamespace() . str_replace('/', '.', $path);
                        }
                        $this->getWorkbench()->getLogger()->logException($e);
                        return $this->getErrorView($e, $errorViewName);
                    }
                    return '';
                }
            default:
                throw new UI5RouteInvalidException('Cannot match route "' . $route . '"!');
        }
    }
    
    public function has(string $route) : bool
    {
        try {
            $this->get($route);
        } catch (UI5RouteInvalidException $e) {
            return false;
        }
        return true;
    }
    
    protected function getManifestJson() : string
    {
        $placeholders = $this->config;
        $tpl = file_get_contents($this->getFacadesFolder() . 'manifest.json');
        $tpl = str_replace('[#app_id#]', $placeholders['app_id'], $tpl);
        $json = json_decode($tpl, true);
        $json['_version'] = $this->getManifestVersion($placeholders['ui5_min_version']);
        $json['sap.app']['id'] = $placeholders['app_id'];
        $json['sap.app']['title'] = $this->getTitle();
        $json['sap.app']['subTitle'] = $placeholders['app_subTitle'] ? $placeholders['app_subTitle'] : '';
        $json['sap.app']['shortTitle'] = $placeholders['app_shortTitle'] ? $placeholders['app_shortTitle'] : '';
        $json['sap.app']['info'] = $placeholders['app_info'] ? $placeholders['app_info'] : '';
        $json['sap.app']['description'] = $placeholders['app_description'] ? $placeholders['app_description'] : '';
        $json['sap.app']['applicationVersion']['version'] = $placeholders['current_version'];
        $json['sap.ui5']['dependencies']['minUI5Version'] = $placeholders['ui5_min_version'];
        
        $json['exface']['useCombinedViewControllers'] = $placeholders['use_combined_viewcontrollers'];
        
        if ($this->isPWA() === true) {
            $json["short_name"] = $this->getName();
            $json["name"] = $this->getTitle();
            $json["icons"] = $this->getWorkbench()->getCMS()->getFavIcons();
            $json["start_url"] = $this->getStartUrl();
            $json["scope"] = $this->getRootUrl() . "/";
            $json["background_color"] = $this->config['pwa_background_color'];
            $json["theme_color"] = $this->config['pwa_theme_color'];
            $json["display"] = "standalone";
        }
        
        $json['sap.app']['dataSources'] = $this->facade->getConfig()->getOption('WEBAPP_EXPORT.MANIFEST.DATASOURCES')->toArray();
        
        return json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }
    
    /**
     *
     * @return string
     */
    public function getFacadesFolder() : string
    {
        return $this->facadeFolder;
    }
    
    protected function getFromFileTemplate(string $pathRelativeToWebappFolder, array $placeholders = null) : string
    {
        $path = $this->getFacadesFolder() . $pathRelativeToWebappFolder;
        if (! file_exists($path)) {
            throw new FileNotFoundError('Cannot load facade file "' . $pathRelativeToWebappFolder . '": file does not exist!');
        }
        
        $tpl = file_get_contents($path);
        
        if ($tpl === false) {
            throw new RuntimeException('Cannot read facade file "' . $pathRelativeToWebappFolder . '"!');
        }
        
        $phs = $placeholders === null ? $this->config : $placeholders;
        
        try {
            return StringDataType::replacePlaceholders($tpl, $phs);
        } catch (\exface\Core\Exceptions\RangeException $e) {
            throw new LogicException('Incomplete  UI5 webapp configuration - ' . $e->getMessage(), null, $e);
        }
    }
    
    protected function getManifestVersion(string $ui5Version) : string
    {
        switch (true) {
            case strcmp($ui5Version, '1.30') < 0: return '1.0.0';
            case strcmp($ui5Version, '1.32') < 0: return '1.1.0';
            case strcmp($ui5Version, '1.34') < 0: return '1.2.0';
            case strcmp($ui5Version, '1.38') < 0: return '1.3.0';
            case strcmp($ui5Version, '1.42') < 0: return '1.4.0';
            case strcmp($ui5Version, '1.46') < 0: return '1.5.0';
            case strcmp($ui5Version, '1.48') < 0: return '1.6.0';
            case strcmp($ui5Version, '1.50') < 0: return '1.7.0';
            case strcmp($ui5Version, '1.52') < 0: return '1.8.0';
            default: return '1.9.0';
        }
    }
    
    public function getTranslation(string $locale = null) : string
    {
        try {
            $app = $this->getRootPage()->getApp();
        } catch (\Throwable $e) {
            $this->getWorkbench()->getLogger()->logException($e);
            try {
                if ($this->getRootPage()->isEmpty() === false) {
                    $rootObj = $this->getRootPage()->getWidgetRoot()->getMetaObject();
                    $app = $rootObj->getApp();
                }
            } catch (\Throwable $e2) {
                $this->getWorkbench()->getLogger()->logException($e2);
            }
        }
        
        if ($locale === null) {
            if ($app) {
                $locale = $app->getLanguageDefault();
            } else {
                $locale = $this->getWorkbench()->getCoreApp()->getLanguageDefault();
            }
        }
        
        $tplTranslator = $this->facade->getApp()->getTranslator();
        $dict = $tplTranslator->getDictionary($locale);
        
        if ($app) {
            $dict = array_merge($dict, $app->getTranslator()->getDictionary($locale));
        }
        
        // Transform the array into a properties file
        foreach ($dict as $key => $text) {
            $output .= $key . '=' . $text. "\n";
        }
        
        return $output;
    }
    
    public function getRootPage() : UiPageInterface
    {
        if ($this->rootPage === null) {
            try {
                $this->rootPage = UiPageFactory::createFromCmsPage($this->getWorkbench()->getCMS(), $this->appId);
            } catch (\Throwable $e) {
                throw new FacadeRoutingError('Root page for UI5 app "' . $this->appId . '" not found!', null, $e);
            }
        }
        return $this->rootPage;
    }
    
    /**
     * 
     * @param string $path
     * @throws UI5RouteInvalidException
     * @return WidgetInterface|NULL
     */
    protected function getWidgetFromPath(string $path) : ?WidgetInterface
    {
        $parts = explode('/', $path);
        $cnt = count($parts);
        if ($cnt === 1 || $cnt === 2) {
            // URLs of non-namespaced pages like 
            // - appRoot/view/mypage
            // - appRoot/view/mypage/widget_id
            $pageAlias = $parts[0];
        } elseif ($cnt === 3 || $cnt === 4) {
            // URLs of namespaced pages like 
            // - appRoot/view/vendor/app/page
            // - appRoot/view/vendor/app/page/widget_id
            $pageAlias = $parts[0] . AliasSelectorInterface::ALIAS_NAMESPACE_DELIMITER . $parts[1] . AliasSelectorInterface::ALIAS_NAMESPACE_DELIMITER . $parts[2];
        } else {
            throw new UI5RouteInvalidException('Route "' . $path . '" not found!');
        }
        
        $page = UiPageFactory::createFromCmsPage($this->getWorkbench()->getCMS(), $pageAlias);
        
        if ($cnt === 4) {
            $widget = $page->getWidget($parts[3]);
        } elseif ($cnt === 2) {
            $widget = $page->getWidget($parts[1]);
        } else {
            $widget = $page->getWidgetRoot();
        }
        
        return $widget;
    }
    
    public function getComponentName() : string
    {
        return $this->appId;
    }
    
    public function getComponentId() : string
    {
        return $this->appId . '.Component';
    }
    
    public function getComponentPath() : string
    {
        return str_replace('.', '/', $this->getComponentName());
    }
    
    public function getComponentUrl() : string
    {
        return 'exface' . $this->facade->getConfig()->getOption('DEFAULT_AJAX_URL') . '/webapps/' . $this->getComponentName() . '/';
    }
    
    public function getComponentPreload() : string
    {
        $prefix = $this->getComponentPath() . '/';
        $resources = [];
        
        // Component and manifest
        $resources[$prefix . 'Component.js'] = $this->get('Component.js');
        $resources[$prefix . 'manifest.json'] = $this->get('manifest.json');
        
        // Base views and controllers (App.view.js, NotFound.view.js, BaseController.js, etc.)
        foreach ($this->getBaseControllers() as $controllerPath) {
            $resources[$prefix . $controllerPath] = $this->get($controllerPath);
        }
        foreach ($this->getBaseViews() as $viewPath) {
            $resources[$prefix . $viewPath] = $this->get($viewPath);
        }
        
        // i18n
        $currentLocale = $this->getWorkbench()->getContext()->getScopeSession()->getSessionLocale();
        $locales = [StringDataType::substringBefore($currentLocale, '_')];
        $resources = array_merge($resources, $this->getComponentPreloadForLangs($locales));
        
        // Root view and controller
        try {
            $rootWidget = $this->getRootPage()->getWidgetRoot();
            $rootController = $this->getControllerForWidget($rootWidget);
            $resources = array_merge($resources, $this->getComponentPreloadForController($rootController));
        } catch (\Throwable $e) {
            $this->getWorkbench()->getLogger()->logException($e);
            if ($rootController) {
                $viewPath = $rootController->getView()->getPath();
                $viewName = $rootController->getView()->getName();
                $controllerPath = $rootController->getPath();
                
                $resources[$viewPath] = $this->getErrorView($e, $viewName);
                $resources[$controllerPath] = $this->getErrorController($e);
            }
        }
        
        return 'sap.ui.require.preload(' . json_encode($resources, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) . ', "' . $prefix . 'Component-preload");';
    }
    
    /**
     * 
     * @param UI5ControllerInterface $controller
     * @return string[]
     */
    protected function getComponentPreloadForController(UI5ControllerInterface $controller) : array
    {
        $resources = [
            $controller->getView()->getPath() => $controller->getView()->buildJsView(),
            $controller->getPath() => $controller->buildJsController()
        ];
        
        // External libs for
        foreach ($controller->getExternalModulePaths() as $name => $path) {
            $filePath = StringDataType::endsWith($path, '.js', false) ? $path : $path . '.js';
            // FIXME At this point a path (not URL) of the external module is required. Where do we get it? What do we
            // do with scripts hosted on other servers? The current script simply assumes, that all external modules
            // reside in subfolders of the platform and the platform itself is located in the subfolder exface of the
            // server root.
            $filePath = $this->getWorkbench()->filemanager()->getPathToBaseFolder() . StringDataType::substringAfter($filePath, 'exface');
            $lib = str_replace('.', '/', $name);
            $lib = StringDataType::endsWith($lib, '.js', false) ? $lib : $lib . '.js';
            if (file_exists($filePath)) {
                $resources[$lib] = file_get_contents($filePath);
            } else {
                $this->getWorkbench()->getLogger()->logException(new FileNotFoundError('File "' . $filePath . '" not found for required UI5 module "' . $name . '"!'), LoggerInterface::ERROR);
            }
        }
        
        return $resources;
    }
    
    protected function getComponentPreloadForLangs(array $locales) : array
    {
        $resources = [];
        foreach ($locales as $loc) {
            $cldrPath = $this->facade->getUI5LibrariesPath() . 'resources/sap/ui/core/cldr/' . $loc . '.json';
            if (file_exists($cldrPath)) {
                $resources['sap/ui/core/cldr/' . $loc . '.json'] = file_get_contents($cldrPath);
            }
        }
        return $resources;
    }
    
    public static function convertNameToPath(string $name, string $suffix = '.view.js') : string
    {
        $path = str_replace('.', '/', $name);
        return $path . $suffix;
    }
    
    /**
     * 
     * @return string[]
     */
    public function getBaseControllers() : array
    {
        return [
            'controller/BaseController.js',
            'controller/App.controller.js',
            'controller/NotFound.controller.js',
            'controller/Offline.controller.js',
            'controller/Error.controller.js'
        ];
    }
    
    /**
     * 
     * @return string[]
     */
    public function getBaseViews() : array
    {
        return [
            'view/App.view.js',
            'view/NotFound.view.js',
            'view/Offline.view.js'
        ];
    }
    
    public function getViewForWidget(WidgetInterface $widget) : UI5ViewInterface
    {
        return $this->getControllerForWidget($widget)->getView();
    }
    
    public function getControllerForWidget(WidgetInterface $widget) : UI5ControllerInterface
    {
        return $this->facade->createController($this->facade->getElement($widget));
    }
    
    public function isPWA() : bool
    {
        return $this->config['pwa_flag'] ? true : false;
    }
    
    public function getStartUrl() : string
    {
        $uri = new Uri($this->getWorkbench()->getCMS()->buildUrlToPage($this->getRootPage()));
        $path = $uri->getPath();
        $path = '/' . ltrim($path, "/");        
        return $path;
    }
    
    public function getRootUrl() : string
    {
        return $this->config['root_url'];
    }
    
    public function getName() : string
    {
        return $this->config['name'] ? $this->config['name'] : $this->getRootPage()->getName();
    }
    
    public function getTitle() : string
    {
        return $this->config['app_title'] ? $this->config['app_title'] : $this->getRootPage()->getName();
    }
    
    public function createViewModel(string $viewName, string $modelName = '') : UI5Model
    {
        $model = new UI5Model($this, $viewName, $modelName);
        $this->models[$viewName . ':' . $modelName] = $model;
        return $model;
    }
    
    public function getViewModel(string $viewName, string $modelName = '') : UI5Model
    {
        $model = $this->models[$viewName . ':' . $modelName];
        if ($model === null) {
            $model = $this->createViewModel($viewName, $modelName);
        }
        return $model;
    }
    
    /**
     * Returns the code for the error view with information about the given exception.
     * 
     * If $originalViewPath is passed, the error view can be used instead of that view.
     * 
     * @param \Throwable $exception
     * @param string $originalViewPath
     * @return string
     */
    public function getErrorView(\Throwable $exception, string $originalViewPath = null) : string
    {
        if ($exception instanceof ExceptionInterface) {
            $logId = $exception->getId();
            $alias =  $exception->getAlias();
            $text = $exception->getMessageTitle($this->getWorkbench());
            $description = $exception->getMessage();
            if ($logId) {
                $title = "{i18n>MESSAGE.ERROR.SERVER_ERROR_TITLE}: Log ID $logId";
            } else {
                $title = "{i18n>MESSAGE.ERROR.SERVER_ERROR_TITLE}";
            }
            
            if (! $text) {
                $text = $exception->getMessage();
                $description = '';
            }
            if ($alias) {
                $text = "{i18n>MESSAGE.TYPE.{$exception->getMessageType($this->getWorkbench())}} $alias: $text";
            } else {
                $text = "{i18n>MESSAGE.TYPE.ERROR}";
            }
            $text = json_encode($text);
            $description = json_encode($description);
            $title = json_encode($title);
        } else {
            $title = '"{i18n>MESSAGE.ERROR.SERVER_ERROR_TITLE}"';
            $text = json_encode($exception->getMessage());
            $description = '""';
        }
        $placeholders = array_merge($this->config, [
            'error_view_name' => ($originalViewPath ?? 'Error'),
            'error_title' => $title,
            'error_text' => $text,
            'error_description' => $description            
        ]);
        return $this->getFromFileTemplate('view/Error.view.js', $placeholders);
    }
    
    /**
     * Returns the code for the controller for the error view from getErrorView().
     * 
     * @param \Throwable $exception
     * @return string
     */
    protected function getErrorController(\Throwable $exception) : string
    {
        return $this->getFromFileTemplate('controller/Error.controller.js');
    }
    
    /**
     * Returns the controller namespace for the app: e.g. "myComponent.controller."
     * 
     * @return string
     */
    public function getControllerNamespace() : string
    {
        return $this->getComponentName() . '.controller.';
    }
    
    /**
     * Returns the controller namespace for the app: e.g. "myComponent.view."
     *
     * @return string
     */
    public function getViewNamespace() : string
    {
        return $this->getComponentName() . '.view.';
    }
    
    /**
     * 
     * @return UI5Facade
     */
    public function getFacade() : UI5Facade
    {
        return $this->facade;
    }
}