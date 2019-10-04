<?php
namespace exface\UI5Facade;

use exface\Core\Interfaces\InstallerInterface;
use exface\Core\Facades\AbstractHttpFacade\HttpFacadeInstaller;
use exface\Core\CommonLogic\Model\App;
use exface\Core\Factories\FacadeFactory;
use exface\Core\CommonLogic\AppInstallers\MySqlDatabaseInstaller;
use exface\Core\Facades\AbstractPWAFacade\ServiceWorkerInstaller;
use exface\Core\CommonLogic\Filemanager;
use exface\ModxCmsConnector\CommonLogic\Installers\ModxCmsTemplateInstaller;

class UI5FacadeApp extends App
{
    private $exportPath = null;
    
    /**
     * {@inheritdoc}
     * 
     * An additional installer is included to condigure the routing for the HTTP facades.
     * 
     * @see App::getInstaller($injected_installer)
     */
    public function getInstaller(InstallerInterface $injected_installer = null)
    {
        $installer = parent::getInstaller($injected_installer);
        
        // Install routes
        $tplInstaller = new HttpFacadeInstaller($this->getSelector());
        $tplInstaller->setFacade(FacadeFactory::createFromString('exface.UI5Facade.UI5Facade', $this->getWorkbench()));
        $installer->addInstaller($tplInstaller);
        
        // Install SQL tables for UI5 export projects
        $exportProjectsDataSource = $this->getWorkbench()->model()->getModelLoader()->getDataConnection();
        $schema_installer = new MySqlDatabaseInstaller($this->getSelector());
        $schema_installer
        ->setFoldersWithMigrations(['InitDB','Migrations'])
        ->setDataConnection($exportProjectsDataSource)
        ->setMigrationsTableName('_migrations_ui5facade');
        $installer->addInstaller($schema_installer);
        
        // Install MODx templates if needed
        $cmsTplInstaller = new ModxCmsTemplateInstaller($this->getSelector());
        $cmsTplInstaller
            ->setTemplateName('SAP Fiori')
            ->setTemplateDescription('Responsive template based on SAP OpenUI5')
            ->setFacadeAlias('exface.UI5Facade.UI5Facade')
            ->setTemplateFilePath('vendor/exface/UI5Facade/Facades/exface.OpenUI5Template.modx.html');
        $installer->addInstaller($cmsTplInstaller);
        $cmsTplInstaller = new ModxCmsTemplateInstaller($this->getSelector());
        $cmsTplInstaller
        ->setTemplateName('SAP Fiori mobile')
        ->setTemplateDescription('Mobile template based on SAP OpenUI5')
        ->setFacadeAlias('exface.UI5Facade.UI5Facade')
        ->setTemplateFilePath('vendor/exface/UI5Facade/Facades/exface.OpenUI5TemplateMobile.modx.html');
        $installer->addInstaller($cmsTplInstaller);
        
        
        // Install ServiceWorker
        $installer->addInstaller(ServiceWorkerInstaller::fromConfig($this->getSelector(), $this->getConfig(), $this->getWorkbench()->getCMS()));
        
        return $installer;
    }
    
    public function getExportFolderAbsolutePath() : string
    {
        if ($this->exportPath === null) {
            $fm = $this->getWorkbench()->filemanager();
            $path = $fm::pathJoin([$fm->getPathToBaseFolder(), $this->getConfig()->getOption('WEBAPP_EXPORT.FOLDER_RELATIVE_TO_BASE')]);
            if (! file_exists($path)) {
                $fm::pathConstruct($path);
            }
            $this->exportPath = $path;
        }
        return $this->exportPath;
    }
}
?>