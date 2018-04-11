<?php
namespace exface\OpenUI5Template;

use exface\Core\Interfaces\InstallerInterface;
use exface\Core\Templates\AbstractHttpTemplate\HttpTemplateInstaller;
use exface\Core\CommonLogic\Model\App;
use exface\Core\Factories\TemplateFactory;
use exface\Core\CommonLogic\AppInstallers\SqlSchemaInstaller;

class OpenUI5TemplateApp extends App
{
    private $exportPath = null;
    
    /**
     * {@inheritdoc}
     * 
     * An additional installer is included to condigure the routing for the HTTP templates.
     * 
     * @see App::getInstaller($injected_installer)
     */
    public function getInstaller(InstallerInterface $injected_installer = null)
    {
        $installer = parent::getInstaller($injected_installer);
        
        $tplInstaller = new HttpTemplateInstaller($this->getSelector());
        $tplInstaller->setTemplate(TemplateFactory::createFromString('exface.OpenUI5Template.OpenUI5Template', $this->getWorkbench()));
        $installer->addInstaller($tplInstaller);
        
        $schema_installer = new SqlSchemaInstaller($this->getSelector());
        $schema_installer->setDataConnection($this->getWorkbench()->model()->getModelLoader()->getDataConnection());
        $installer->addInstaller($schema_installer);
        
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