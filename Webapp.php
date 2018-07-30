<?php
namespace exface\OpenUI5Template;

use exface\Core\Interfaces\WorkbenchDependantInterface;
use exface\Core\CommonLogic\Workbench;
use exface\OpenUI5Template\Templates\OpenUI5Template;
use exface\OpenUI5Template\Exceptions\Ui5RouteInvalidException;
use exface\Core\DataTypes\StringDataType;
use exface\Core\Exceptions\LogicException;
use exface\Core\Factories\UiPageFactory;
use exface\Core\Interfaces\Selectors\AliasSelectorInterface;
use exface\Core\Exceptions\FileNotFoundError;
use exface\Core\Exceptions\RuntimeException;
use exface\Core\Interfaces\Model\UiPageInterface;
use exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface;
use exface\Core\Interfaces\WidgetInterface;
use exface\OpenUI5Template\Templates\Interfaces\ui5ViewInterface;
use GuzzleHttp\Psr7\Uri;
use exface\Core\Interfaces\Log\LoggerInterface;

class Webapp implements WorkbenchDependantInterface
{
    private $workbench = null;
    
    private $template = null;
    
    private $appId = null;
    
    private $rootPage = null;
    
    private $templateFolder = null;
    
    private $config = [];
    
    private $controllers = [];
    
    public function __construct(OpenUI5Template $template, string $ui5AppId, string $templateFolder, array $config)
    {
        $this->workbench = $template->getWorkbench();
        $this->template = $template;
        $this->appId = $ui5AppId;
        $this->templateFolder = $templateFolder;
        $this->config = $config;
    }
    
    public function getWorkbench() : Workbench
    {
        return $this->workbench;
    }
    
    public function get(string $route) : string
    {
        switch (true) {
            case $route === 'manifest.json':
                return $this->getManifestJson();
            case $route === 'Component-preload.js':
                return $this->getComponentPreload();
            case StringDataType::startsWith($route, 'i18n/'):
                //$filename = pathinfo($filePath, PATHINFO_FILENAME);
                return '';
            case file_exists($this->getTemplatesFolder() . $route):
                return $this->getFromFileTemplate($route);
            case StringDataType::startsWith($route, 'view/'):
                $path = StringDataType::substringAfter($route, 'view/');
                if (StringDataType::endsWith($path, '.view.js')) {
                    $path = StringDataType::substringBefore($path, '.view.js');
                    $widget = $this->getWidgetFromPath($path);
                    if ($widget) {
                        return $this->getViewForWidget($widget)->buildJsView();
                    } 
                    return '';
                }
            case StringDataType::startsWith($route, 'controller/'):
                $path = StringDataType::substringAfter($route, 'controller/');
                if (StringDataType::endsWith($path, '.controller.js')) {
                    $path = StringDataType::substringBefore($path, '.controller.js');
                    $widget = $this->getWidgetFromPath($path);
                    if ($widget) {
                        return $this->getControllerForWidget($widget)->buildJsController();
                    }
                    return '';
                }
            default:
                throw new Ui5RouteInvalidException('Cannot match route "' . $route . '"!');
        }
    }
    
    public function has(string $route) : bool
    {
        try {
            $this->get($route);
        } catch (Ui5RouteInvalidException $e) {
            return false;
        }
        return true;
    }
    
    protected function getManifestJson() : string
    {
        $placeholders = $this->config;
        $tpl = file_get_contents($this->getTemplatesFolder() . 'manifest.json');
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
        
        if ($this->isPWA() === true) {
            $json["short_name"] = $this->getName();
            $json["name"] = $this->getTitle();
            $json["icons"] = $this->getWorkbench()->getCMS()->getFavIcons();
            $json["start_url"] = $this->getStartUrl();
            $json["scope"] = $this->getRootUrl() . "/";
            $json["background_color"] = "#3367D6";
            $json["theme_color"] = "#3367D6";
            $json["display"] = "standalone";
        }
        
        return json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }
    
    /**
     *
     * @return string
     */
    public function getTemplatesFolder() : string
    {
        return $this->templateFolder;
    }
    
    protected function getFromFileTemplate(string $pathRelativeToTemplatesFolder, array $placeholders = null) : string
    {
        $path = $this->getTemplatesFolder() . $pathRelativeToTemplatesFolder;
        if (! file_exists($path)) {
            throw new FileNotFoundError('Cannot load template file "' . $pathRelativeToTemplatesFolder . '": file does not exist!');
        }
        
        $tpl = file_get_contents($path);
        
        if ($tpl === false) {
            throw new RuntimeException('Cannot read template file "' . $pathRelativeToTemplatesFolder . '"!');
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
    
    public function getTranslation(string $filePath) : string
    {
        $json = json_decode(file_get_contents($filePath), true);
        $output = '';
        foreach ($json as $key => $text) {
            $output .= $key . '=' . $text. "\n";
        }
        return $output;
    }
    
    public function getRootPage() : UiPageInterface
    {
        if ($this->rootPage === null) {
            $this->rootPage = UiPageFactory::createFromCmsPage($this->getWorkbench()->getCMS(), $this->appId);
        }
        return $this->rootPage;
    }
    
    protected function getWidgetFromPath($path)
    {
        $parts = explode('/', $path);
        $cnt = count($parts);
        if ($cnt === 1) {
            $pageAlias = $parts[0];
        } elseif ($cnt !== 3 || $cnt !== 4) {
            $pageAlias = $parts[0] . AliasSelectorInterface::ALIAS_NAMESPACE_DELIMITER . $parts[1] . AliasSelectorInterface::ALIAS_NAMESPACE_DELIMITER . $parts[2];
        } else {
            throw new Ui5RouteInvalidException('Route "' . $path . '" not found!');
        }
        
        $page = UiPageFactory::createFromCmsPage($this->getWorkbench()->getCMS(), $pageAlias);
        
        if (count($parts) === 4) {
            $widget = $page->getWidget($parts[3]);
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
        return 'exface' . $this->template->getConfig()->getOption('DEFAULT_AJAX_URL') . '/webapps/' . $this->getComponentName() . '/';
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
        $rootWidget = $this->getRootPage()->getWidgetRoot();
        $rootController = $this->getControllerForWidget($rootWidget);
        $resources = array_merge($resources, $this->getComponentPreloadForController($rootController));
        
        return 'sap.ui.require.preload(' . json_encode($resources, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) . ', "' . $prefix . 'Component-preload");';
    }
    
    /**
     * 
     * @param ui5ControllerInterface $controller
     * @return string[]
     */
    protected function getComponentPreloadForController(ui5ControllerInterface $controller) : array
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
            $filePath = $this->getWorkbench()->filemanager()->getPathToBaseFolder() . StringDataType::substringAfter($filePath, 'exface/');
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
            $cldrPath = $this->template->getUI5LibrariesPath() . 'resources/sap/ui/core/cldr/' . $loc . '.json';
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
            'controller/App.controller.js'
        ];
    }
    
    /**
     * 
     * @return string[]
     */
    public function getBaseViews() : array
    {
        return [
            'view/App.view.js'
        ];
    }
    
    public function getViewForWidget(WidgetInterface $widget) : ui5ViewInterface
    {
        return $this->getControllerForWidget($widget)->getView();
    }
    
    public function getControllerForWidget(WidgetInterface $widget) : ui5ControllerInterface
    {
        return $this->template->createController($this->template->getElement($widget));
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
}