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

/**
 * 
 * @author Andrej Kabachnik
 * 
 * @method OpenUI5TemplateApp getApp()
 *
 */
class ExportFioriWebapp extends DownloadZippedFolder
{
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
        
        $webappFolder = $this->exportWebapp($rootPage, $row);
        $zip->addFolder($webappFolder);
        
        $zip->close();
        return $zip;
    }
    
    protected function exportWebapp(UiPageInterface $rootPage, array $appDataRow) : string
    {
        
        $path = $this->getApp()->getExportFolderAbsolutePath() . DIRECTORY_SEPARATOR . $rootPage->getAliasWithNamespace();
        
        if (! file_exists($path)) {
            Filemanager::pathConstruct($path);
        } else {
            $this->getWorkbench()->filemanager()->emptyDir($path);
        }
        
        $path = $path . DIRECTORY_SEPARATOR;
        
        $this->exportManifest($appDataRow, $path);
        $this->exportFileTemplate($appDataRow, 'index.html', $path);
        $this->exportComponent($appDataRow['app_id'], $path);
        $this->exportTranslations($rootPage->getApp(), $path);
        $this->exportStaticViews($appDataRow, $path);
        
        return $path;
    }
    
    protected function exportManifest(array $appDataRow, string $exportFolder) : string
    {
        $tpl = file_get_contents($this->getWebappTemplateFolder() . 'manifest.json');
        $tpl = str_replace('[#app_id#]', $appDataRow['app_id'], $tpl);
        $json = json_decode($tpl, true);
        $json['_version'] = $this->getManifestVersion($appDataRow['ui5_min_version']);
        $json['sap.app']['id'] = $appDataRow['app_id'];
        $json['sap.app']['title'] = $appDataRow['app_title'];
        $json['sap.app']['subTitle'] = $appDataRow['app_subTitle'] ? $appDataRow['app_subTitle'] : '';
        $json['sap.app']['shortTitle'] = $appDataRow['app_shortTitle'] ? $appDataRow['app_shortTitle'] : '';
        $json['sap.app']['info'] = $appDataRow['app_info'] ? $appDataRow['app_info'] : '';
        $json['sap.app']['description'] = $appDataRow['app_description'] ? $appDataRow['app_description'] : '';
        $json['sap.app']['applicationVersion']['version'] = $appDataRow['current_version'];
        $json['sap.ui5']['dependencies']['minUI5Version'] = $appDataRow['ui5_min_version'];
        
        $target = $exportFolder . 'manifest.json';
        file_put_contents($target, json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        return $target;
    }
    
    protected function exportComponent(string $webapp_id, string $exportFolder) : string
    {
        $tpl = file_get_contents($this->getWebappTemplateFolder() . 'Component.js');
        $target = $exportFolder . 'Component.js';
        file_put_contents($target, str_replace('[#app_id#]', $webapp_id, $tpl));
        return $target;
    }
    
    protected function exportTranslations(AppInterface $app, string $exportFolder) : ExportFioriWebapp
    {
        $folder = $app->getDirectoryAbsolutePath() . DIRECTORY_SEPARATOR . 'Translations' . DIRECTORY_SEPARATOR;
        $defaultLang = $app->getDefaultLanguageCode();
        $i18nFolder = $exportFolder . 'i18n' . DIRECTORY_SEPARATOR;
        if (! file_exists($i18nFolder)) {
            Filemanager::pathConstruct($i18nFolder);
        }
        
        foreach (glob($folder . "*.json") as $filename) {
            $this->exportTranslation($filename, $defaultLang, $i18nFolder);
        }
        return $this;
    }
    
    protected function exportTranslation(string $filePath, string $defaultLang, string $exportFolder) : string
    {
        $json = json_decode(file_get_contents($filePath), true);
        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        $lang = StringDataType::substringAfter($filename, '.', false, false, true);
        $output = '';
        foreach ($json as $key => $text) {
            $output .= $key . '=' . $text. "\n";
        }
        $i18nSuffix = (strcasecmp($lang, $defaultLang) === 0) ? '' : '_' . $lang;
        $i18nFile = $exportFolder . 'i18n' . $i18nSuffix . '.properties';
        file_put_contents($i18nFile, $output);
        return $i18nFile;
    }
    
    protected function getWebappTemplateFolder() : string
    {
        return $this->getApp()->getDirectoryAbsolutePath() . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'webapp' . DIRECTORY_SEPARATOR;
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
    
    protected function exportFileTemplate(array $placeholders, string $pathRelativeToWebappFolder, string $exportFolder) : string
    {
        $tpl = file_get_contents($this->getWebappTemplateFolder() . $pathRelativeToWebappFolder);
        $target = $exportFolder . $pathRelativeToWebappFolder;
        file_put_contents($target, StringDataType::replacePlaceholders($tpl, $placeholders));
        return $target;
    }
    
    protected function exportStaticViews(array $appDataRow, string $exportFolder) : string
    {
        if (! file_exists($exportFolder . 'view')) {
            Filemanager::pathConstruct($exportFolder . 'view');
        }
        if (! file_exists($exportFolder . 'controller')) {
            Filemanager::pathConstruct($exportFolder . 'controller');
        }
        $this->exportFileTemplate($appDataRow, 'view/App.view.js', $exportFolder);
        $this->exportFileTemplate($appDataRow, 'view/NotFound.view.js', $exportFolder);
        $this->exportFileTemplate($appDataRow, 'controller/BaseController.js', $exportFolder);
        $this->exportFileTemplate($appDataRow, 'controller/App.controller.js', $exportFolder);
        $this->exportFileTemplate($appDataRow, 'controller/NotFound.controller.js', $exportFolder);
        return $exportFolder . 'view';
    }
    
}