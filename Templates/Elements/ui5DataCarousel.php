<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\DataCarousel;
use exface\Core\Interfaces\Widgets\iShowSingleAttribute;
use exface\UI5Facade\Facades\Interfaces\ui5ValueBindingInterface;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryDataCarouselTrait;

/**
 * Generates OpenUI5 data carousels
 *
 * @author Andrej Kabachnik
 * 
 * @method DataCarousel getWidget()
 *        
 */
class ui5DataCarousel extends ui5Split
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
            if (! ($child instanceof iShowSingleAttribute) || ! $child->isBoundToAttribute()) {
                continue;
            }
            if (! $dataIsEditable) {
                $this->getDataElement()->setEditable(true);
            }
            $childElement = $this->getFacade()->getElement($child);
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
        
        var oCtxt = oEvent.getSource().getBindingContext();
        var sPath = oEvent.getParameters().srcControl.getBindingContext().sPath;
        var oModel = oEvent.getSource().getModel();
        var oControl, oBindingInfo;
        {$bindings}
        
        
JS;
        
        $this->getDataElement()->addOnChangeScript($bindingScript);
        return $this;
    }
}
?>