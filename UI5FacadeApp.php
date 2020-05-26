<?php
namespace exface\UI5Facade;

use exface\Core\Interfaces\InstallerInterface;
use exface\Core\Facades\AbstractHttpFacade\HttpFacadeInstaller;
use exface\Core\CommonLogic\Model\App;
use exface\Core\Factories\FacadeFactory;
use exface\Core\CommonLogic\AppInstallers\MySqlDatabaseInstaller;
use exface\Core\Facades\AbstractPWAFacade\ServiceWorkerInstaller;

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
        
        // Install ServiceWorker
        $installer->addInstaller(ServiceWorkerInstaller::fromConfig($this->getSelector(), $this->getConfig()));
        
        return $installer;
    }
}
?>