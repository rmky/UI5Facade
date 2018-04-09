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
        $zip = new ArchiveManager($this->getWorkbench(), $this->getZipPathAbsolute());
        $input = $this->getInputDataSheet($task);
        
        $input->getColumns()->addFromExpression('root_page_alias');
        $input->getColumns()->addFromExpression('ui5_min_version');
        $input->getColumns()->addFromExpression('ui5_source');
        $input->getColumns()->addFromExpression('ui5_theme');
        $input->getColumns()->addFromExpression('ui5_app_control');
        $input->getColumns()->addFromExpression('app_id');
        $input->getColumns()->addFromExpression('app_title');
        $input->getColumns()->addFromExpression('app_subTitle');
        $input->getColumns()->addFromExpression('app_shortTitle');
        $input->getColumns()->addFromExpression('app_info');
        $input->getColumns()->addFromExpression('app_description');
        
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
        $zip->addFolder($webappFolder);
        
        $zip->close();
        return $zip;
    }
    
    protected function exportWebapp(UiPageInterface $rootPage, OpenUI5Template $template, array $appDataRow) : string
    {
        $path = $this->getApp()->getExportFolderAbsolutePath() . DIRECTORY_SEPARATOR . $rootPage->getAliasWithNamespace();
        
        if (! file_exists($path)) {
            Filemanager::pathConstruct($path);
        } else {
            $this->getWorkbench()->filemanager()->emptyDir($path);
        }
        
        $path = $path . DIRECTORY_SEPARATOR;
        /* @var $webapp \exface\OpenUI5Template\Webapp */ 
        $webapp = $template->createWebapp($appDataRow['app_id'], $appDataRow);
        
        if (! file_exists($path . 'view')) {
            Filemanager::pathConstruct($path . 'view');
        }
        if (! file_exists($path . 'controller')) {
            Filemanager::pathConstruct($path . 'controller');
        }
        
        $this
            ->exportFile($webapp, 'manifest.json', $path)
            ->exportFile($webapp, 'index.html', $path)
            ->exportFile($webapp, 'Component.js', $path)
            ->exportTranslations($rootPage->getApp(), $webapp, $path)
            ->exportStaticViews($webapp, $path)
            ->exportPages($webapp, $path);
        
        return $path;
    }
    
    protected function exportTranslations(AppInterface $app, Webapp $webapp, string $exportFolder) : ExportFioriWebapp
    {
        $folder = $app->getDirectoryAbsolutePath() . DIRECTORY_SEPARATOR . 'Translations' . DIRECTORY_SEPARATOR;
        $defaultLang = $app->getDefaultLanguageCode();
        $i18nFolder = $exportFolder . 'i18n' . DIRECTORY_SEPARATOR;
        if (! file_exists($i18nFolder)) {
            Filemanager::pathConstruct($i18nFolder);
        }
        
        foreach (glob($folder . "*.json") as $path) {
            $filename = pathinfo($path, PATHINFO_FILENAME);
            $lang = StringDataType::substringAfter($filename, '.', false, false, true);
            
            $i18nSuffix = (strcasecmp($lang, $defaultLang) === 0) ? '' : '_' . $lang;
            $i18nFile = $i18nFolder . 'i18n' . $i18nSuffix . '.properties';
            file_put_contents($i18nFile, $webapp->getTranslation($path));
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
        $subfolder = str_replace(AliasSelectorInterface::ALIAS_NAMESPACE_DELIMITER, DIRECTORY_SEPARATOR, $page->getNamespace());
        
        $destination .= $exportFolder . 'view' . DIRECTORY_SEPARATOR . ($subfolder ? $subfolder . DIRECTORY_SEPARATOR : '');
        
        if (! file_exists($destination)) {
            Filemanager::pathConstruct($destination);
        }
        
        file_put_contents($destination . $page->getAlias() . '.view.js', $webapp->get('view/' . $page->getAliasWithNamespace() . '.view.js'));
        return $this;
    }
    
    protected function exportFile(Webapp $webapp, string $route, string $exportFolder) : ExportFioriWebapp
    {
        file_put_contents($exportFolder . $route, $webapp->get($route));
        return $this;
    }
    
}