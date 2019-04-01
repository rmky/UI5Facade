<?php
namespace exface\UI5Facade;

use exface\Core\Interfaces\InstallerInterface;
use exface\Core\Facades\AbstractHttpFacade\HttpFacadeInstaller;
use exface\Core\CommonLogic\Model\App;
use exface\Core\Factories\FacadeFactory;
use exface\Core\CommonLogic\AppInstallers\SqlSchemaInstaller;
use exface\Core\Facades\AbstractPWAFacade\ServiceWorkerInstaller;
use exface\Core\CommonLogic\Filemanager;

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
        
        $tplInstaller = new HttpFacadeInstaller($this->getSelector());
        $tplInstaller->setFacade(FacadeFactory::createFromString('exface.UI5Facade.UI5Facade', $this->getWorkbench()));
        $installer->addInstaller($tplInstaller);
        
        // Install routes
        $schema_installer = new SqlSchemaInstaller($this->getSelector());
        $schema_installer->setDataConnection($this->getWorkbench()->model()->getModelLoader()->getDataConnection());
        $installer->addInstaller($schema_installer);
        
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