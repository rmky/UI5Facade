<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\DataCarousel;
use exface\Core\Interfaces\Widgets\iShowSingleAttribute;
use exface\OpenUI5Template\Templates\Interfaces\ui5ValueBindingInterface;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryDataCarouselTrait;

/**
 * Generates OpenUI5 data carousels
 *
 * @author Andrej Kabachnik
 * 
 * @method DataCarousel getWidget()
 *        
 */
class ui5DataCarousel extends ui5SplitHorizontal
{
    use JqueryDataCarouselTrait;
    
    protected function init()
    {
        parent::init(); 
        $this->registerSyncOnMaster();
    }
    
    protected function registerSyncOnMaster()
    {
        $dataIsEditable = $this->getDataElement()->isEditable();
        foreach ($this->getWidget()->getDetailsWidget()->getChildrenRecursive() as $child) {
            if (! ($child instanceof iShowSingleAttribute) || ! $child->hasAttributeReference()) {
                continue;
            }
            if (! $dataIsEditable) {
                $this->getDataElement()->setEditable(true);
            }
            $childElement = $this->getTemplate()->getElement($child);
            if ($childElement instanceof ui5ValueBindingInterface) {
                $bindings .= <<<JS
            oControl = sap.ui.getCore().byId("{$childElement->getId()}");
            oBindingInfo = oControl.getBindingInfo("{$childElement->buildJsValueBindingPropertyName()}");
            oBindingInfo.parts[0].path = sPath + "{$childElement->getValueBindingPath()}";
            oControl.setModel(oModel).bindProperty("{$childElement->buildJsValueBindingPropertyName()}", oBindingInfo);
            oControl.setBindingContext(new sap.ui.model.Context(oModel, sPath + "{$childElement->getValueBindingPath()}"));
JS;
            }
        }
        
        $bindingScript = <<<JS
        
        var oCtxt = event.getSource().getBindingContext();
        var sPath = event.getParameters().srcControl.getBindingContext().sPath;
        var oModel = event.getSource().getModel();
        var oControl, oBindingInfo;
        {$bindings}
        
        
JS;
        
        $this->getDataElement()->addOnChangeScript($bindingScript);
        return $this;
    }
}
?>