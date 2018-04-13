<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Factories\WidgetFactory;
use exface\Core\Factories\ActionFactory;
use exface\Core\Actions\ShowObjectInfoDialog;

/**
 * Generates OpenUI5 data carousels
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5DataCarousel extends ui5SplitHorizontal
{
    protected function init()
    {
        parent::init();
        
        $syncScript = <<<JS

        var oCtxt = event.getSource().getBindingContext();
        {$this->getDetailsElement()->buildJsDataSetter($this->getDataElement()->buildJsDataGetter(ActionFactory::createFromString($this->getWorkbench(), ShowObjectInfoDialog::class)))};

JS;
        
        $this->getDataElement()->addOnChangeScript($syncScript);
        
    }
    
    /**
     * 
     * @return ui5DataTable
     */
    protected function getDataElement()
    {
        return $this->getTemplate()->getElement($this->getWidget()->getDataWidget());
    }
    
    /**
     * 
     * @return ui5Form
     */
    protected function getDetailsElement()
    {
        return $this->getTemplate()->getElement($this->getWidget()->getDetailsWidget());
    }
}
?>