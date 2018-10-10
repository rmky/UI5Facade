<?php
namespace exface\OpenUI5Template\Actions;

use exface\Core\Actions\DownloadZippedFolder;
use exface\Core\Interfaces\Tasks\TaskInterface;
use exface\Core\CommonLogic\ArchiveManager;
use exface\OpenUI5Template\OpenUI5TemplateApp;
use exface\Core\Interfaces\Model\UiPageInterface;
use exface\Core\Factories\UiPageFactory;
use exface\Core\CommonLogic\Filemanager;
use exface\Core\Interfaces\AppInterface;
use exface\Core\DataTypes\StringDataType;
use exface\OpenUI5Template\Webapp;
use exface\OpenUI5Template\Templates\OpenUI5Template;
use exface\Core\CommonLogic\Selectors\TemplateSelector;
use exface\Core\Factories\TemplateFactory;
use exface\Core\Interfaces\Selectors\AliasSelectorInterface;
use exface\Core\Exceptions\RuntimeException;

/**
 * 
 * @author Andrej Kabachnik
 * 
 * @method OpenUI5TemplateApp getApp()
 *
 */
class ExportFioriWebapp extends DownloadZippedFolder
{
    private $templateSelectorString = 'exface\\OpenUI5Template\\Templates\\OpenUI5Template';
    
    protected function init()
    {
        parent::init();
        $this->setInputRowsMin(1);
        $this->setInputRowsMax(1);
    }
    
    protected function createZip(TaskInterface $task) : ArchiveManager
    {
        // Export app
        $input = $this->getInputDataSheet($task);
        $columns = $input->getColumns();
        $columns->addFromExpression('root_page_alias');
        $columns->addFromExpression('current_version');
        $columns->addFromExpression('ui5_min_version');
        $columns->addFromExpression('ui5_source');
        $columns->addFromExpression('ui5_theme');
        $columns->addFromExpression('ui5_app_control');
        $columns->addFromExpression('app_id');
        $columns->addFromExpression('app_title');
        $columns->addFromExpression('app_subTitle');
        $columns->addFromExpression('app_shortTitle');
        $columns->addFromExpression('app_info');
        $columns->addFromExpression('app_description');
        
        if (! $input->isFresh()) {
            $input->addFilterFromColumnValues($input->getUidColumn());
            $input->dataRead();
        }
        
        $row = $input->getRows()[0];
        $row['component_path'] = str_replace('.', '/', $row['app_id']);
        $row['assets_path'] = './';
        $rootPage = UiPageFactory::createFromCmsPage($this->getWorkbench()->getCMS(), $row['root_page_alias']);
        
        $template = TemplateFactory::createFromString($this->templateSelectorString, $this->getWorkbench());
        $webappFolder = $this->exportWebapp($rootPage, $template, $row);
        
        // Create ZIP for download
        $defaultPath = $this->getZipPathAbsolute();
        $zipFolder = pathinfo($defaultPath, PATHINFO_DIRNAME);
        $this->setZipPath($zipFolder . DIRECTORY_SEPARATOR . $row['app_id'] . '_v' . $row['app_id'] . '_' . date('YmdHis') . '.zip');
        $zip = new ArchiveManager($this->getWorkbench(), $this->getZipPathAbsolute());
        $zip->addFolder($webappFolder);
        
        $zip->close();
        return $zip;
    }
    
    protected function exportWebapp(UiPageInterface $rootPage, OpenUI5Template $template, array $appDataRow) : string
    {
        $appPath = $this->getApp()->getExportFolderAbsolutePath() . DIRECTORY_SEPARATOR . $rootPage->getAliasWithNamespace();
        $exportPath =  $appPath . DIRECTORY_SEPARATOR . 'WebContent';
        if (! file_exists($exportPath)) {
            Filemanager::pathConstruct($exportPath);
        } else {
            $this->getWorkbench()->filemanager()->emptyDir($exportPath);
        }
        
        $exportPath = $exportPath . DIRECTORY_SEPARATOR;
        /* @var $webapp \exface\OpenUI5Template\Webapp */ 
        $webapp = $template->initWebapp($appDataRow['app_id'], $appDataRow);
        
        if (! file_exists($exportPath . 'view')) {
            Filemanager::pathConstruct($exportPath . 'view');
        }
        if (! file_exists($exportPath . 'controller')) {
            Filemanager::pathConstruct($exportPath . 'controller');
        }
        if (! file_exists($exportPath . 'libs')) {
            Filemanager::pathConstruct($exportPath . 'libs');
        }
        
        $this
            ->exportFile($webapp, 'manifest.json', $exportPath)
            ->exportFile($webapp, 'index.html', $exportPath)
            ->exportFile($webapp, 'Component.js', $exportPath)
            ->exportTranslations($rootPage->getApp(), $webapp, $exportPath)
            ->exportStaticViews($webapp, $exportPath)
            ->exportPages($webapp, $exportPath);
        
        return $appPath;
    }
    
    protected function exportTranslations(AppInterface $app, Webapp $webapp, string $exportFolder) : ExportFioriWebapp
    {
        $defaultLang = $app->getDefaultLanguageCode();
        $i18nFolder = $exportFolder . 'i18n' . DIRECTORY_SEPARATOR;
        if (! file_exists($i18nFolder)) {
            Filemanager::pathConstruct($i18nFolder);
        }
        
        foreach ($app->getLanguages() as $lang) {
            $i18nSuffix = (strcasecmp($lang, $defaultLang) === 0) ? '' : '_' . $lang;
            $i18nFile = $i18nFolder . 'i18n' . $i18nSuffix . '.properties';
            file_put_contents($i18nFile, $webapp->get($i18nFile));
        }
        return $this;
    }
    
    protected function exportStaticViews(Webapp $webapp, string $exportFolder) : ExportFioriWebapp
    {
        $this->exportFile($webapp, 'view/App.view.js', $exportFolder);
        $this->exportFile($webapp, 'view/NotFound.view.js', $exportFolder);
        $this->exportFile($webapp, 'controller/BaseController.js', $exportFolder);
        $this->exportFile($webapp, 'controller/App.controller.js', $exportFolder);
        $this->exportFile($webapp, 'controller/NotFound.controller.js', $exportFolder);
        return $this;
    }
    
    protected function exportPages(Webapp $webapp, string $exportFolder) : ExportFioriWebapp
    {
        $this->exportPage($webapp, $webapp->getRootPage(), $exportFolder);
        return $this;
    }
    
    protected function exportPage(Webapp $webapp, UiPageInterface $page, string $exportFolder) : ExportFioriWebapp
    {     
        // IMPORTANT: generate the view first to allow it to add controller methods!
        $view = $webapp->get('view/' . $page->getAliasWithNamespace() . '.view.js');
        $view = $this->escapeUnicode($view);
        $controller = $webapp->get('controller/' . $page->getAliasWithNamespace() . '.controller.js');
        $controller = $this->escapeUnicode($controller);
        
        // Copy external includes and replace their paths in the controller
        $controller = $this->exportExternalLibs($controller, $exportFolder . DIRECTORY_SEPARATOR . 'libs');
        
        // Save view and controller as files
        file_put_contents($this->buildPathToPageAsset($page, $exportFolder, 'view') . $page->getAlias() . '.view.js', $view);
        file_put_contents($this->buildPathToPageAsset($page, $exportFolder, 'controller') . $page->getAlias() . '.controller.js', $controller);
        return $this;
    }
    
    protected function exportExternalLibs(string $controllerJs, string $libsFolder) : string
    {
        $filemanager = $this->getWorkbench()->filemanager();
        
        // Process JS files
        $matches = [];
        preg_match_all('/jQuery\.sap\.registerModulePath\((?>[\'"].*[\'"], )?[\'"](.*)["\']\)/mi', $controllerJs, $matches);
        $jsIncludes = $matches[1];
        
        foreach ($jsIncludes as $path) {
            if ($this->isExternalUrl($path)) {
                continue;
            }
            $pathExported = $this->exportExternalLib($path . '.js', $libsFolder, $filemanager);
            $controllerJs = str_replace($path, substr($pathExported, 0, -3), $controllerJs);
        }
        
        // Process CSS files
        $matches = [];
        preg_match_all('/jQuery\.sap\.includeStyleSheet\((?>[\'"].*[\'"], )?[\'"](.*)["\']\)/mi', $controllerJs, $matches);
        $cssIncludes = $matches[1];
        
        foreach ($cssIncludes as $path) {
            if ($this->isExternalUrl($path)) {
                continue;
            }
            $pathExported = $this->exportExternalLib($path, $libsFolder, $filemanager);
            $controllerJs = str_replace($path, $pathExported, $controllerJs);
        }
        
        return $controllerJs;
    }
    
    protected function exportExternalLib(string $includePath, string $libsFolder, Filemanager $filemanager) : string
    {
        $pathInVendorFolder = Filemanager::pathNormalize(StringDataType::substringAfter($includePath, 'vendor/'));
        $file = $filemanager->getPathToVendorFolder() . DIRECTORY_SEPARATOR . $pathInVendorFolder;
        if (! file_exists($file)) {
            throw new RuntimeException('Cannot export external library with path "' . $includePath . '": file "' . $file . '" not found!');
        }
        $pathParts = explode('/', $pathInVendorFolder);
        $folder = $pathParts[0] . DIRECTORY_SEPARATOR . $pathParts[1];
        if (! file_exists($libsFolder . DIRECTORY_SEPARATOR . $folder)) {
            if (strcasecmp($folder, $this->getApp()->getDirectory()) === 0) {
                $jsFolder = '/Templates/js';
                $filemanager->copyDir($filemanager->getPathToVendorFolder() . DIRECTORY_SEPARATOR . $folder . $jsFolder, $libsFolder . DIRECTORY_SEPARATOR . $folder);
            } else {
                $filemanager->copyDir($filemanager->getPathToVendorFolder() . DIRECTORY_SEPARATOR . $folder, $libsFolder . DIRECTORY_SEPARATOR . $folder);
            }
        }
        
        if (strcasecmp($folder, $this->getApp()->getDirectory()) === 0) {
            $pathInVendorFolder = str_replace('/Templates/js', '', $pathInVendorFolder);
        }
        
        return pathinfo($libsFolder, PATHINFO_BASENAME) . '/' . $pathInVendorFolder;
    }
    
    protected function isExternalUrl(string $uri) : bool
    {
        return StringDataType::startsWith($uri, 'https:', false) || StringDataType::startsWith($uri, 'http:', false) || StringDataType::startsWith($uri, 'ftp:', false);
    }
    
    protected function buildPathToPageAsset(UiPageInterface $page, string $exportFolder, string $assetType = 'view') : string
    {
        $subfolder = str_replace(AliasSelectorInterface::ALIAS_NAMESPACE_DELIMITER, DIRECTORY_SEPARATOR, $page->getNamespace());
        $destination = $exportFolder . $assetType . DIRECTORY_SEPARATOR . ($subfolder ? $subfolder . DIRECTORY_SEPARATOR : '');
        if (! file_exists($destination)) {
            Filemanager::pathConstruct($destination);
        }
        return $destination;
    }
    
    protected function exportFile(Webapp $webapp, string $route, string $exportFolder) : ExportFioriWebapp
    {
        file_put_contents($exportFolder . $route, $webapp->get($route));
        return $this;
    }
    
    /**
     * Converts non-ASCII characters into unicode escape sequences (\uXXXX).
     * 
     * @param string $str
     * @return string
     */
    protected function escapeUnicode(string $str) : string
    {
        // json_encode automatically escapes unicode, but it also escapes lots of other things
        $string = json_encode($str);
        // convert unicode escape sequences to their HTML equivalents, so they survive json_decode()
        $string = preg_replace('/\\\u([0-9a-f]{4})/i', '&#x$1;', $string);
        // decode JSON to remove all other escaped stuff (newlines, etc.)
        $string = json_decode($string);
        // convert HTML unicode back to \uXXXX notation.
        $string = preg_replace('/&#x([0-9a-f]{4});/i', '\u$1', $string);
        
        return $string;
    }
    
}