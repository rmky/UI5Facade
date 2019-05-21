<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\DataColumn;
use exface\UI5Facade\Facades\Interfaces\UI5BindingFormatterInterface;
use exface\UI5Facade\Facades\Interfaces\UI5ValueBindingInterface;
use exface\UI5Facade\Facades\Interfaces\UI5CompoundControlInterface;

/**
 *
 * @method DataColumn getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class UI5DataColumn extends UI5AbstractElement
{
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $parentElement = $this->getFacade()->getElement($this->getWidget()->getParent());
        if (($parentElement instanceof UI5DataTable) && $parentElement->isMTable()) {
            return $this->buildJsConstructorForMColumn();
        }
        return $this->buildJsConstructorForUiColumn();
    }

    /**
     * Returns the constructor for a sap.ui.table.Column for this DataColumn widget
     * 
     * @return string
     */
    public function buildJsConstructorForUiColumn()
    {
        $widget = $this->getWidget();
        
        return <<<JS
	 new sap.ui.table.Column({
	    label: new sap.ui.commons.Label({
            text: "{$this->getCaption()}"
        }),
        autoResizable: true,
        template: {$this->buildJsConstructorForCell()},
	    sortProperty: "{$widget->getAttributeAlias()}",
	    filterProperty: "{$widget->getAttributeAlias()}",
        {$this->buildJsPropertyTooltip()}
	    {$this->buildJsPropertyVisibile()}
	    {$this->buildJsPropertyWidth()}
	})
JS;
    }
	
    /**
     * Returns the javascript constructor for a cell control to be used in cell template aggregations.
     * 
     * @return string
     */
    public function buildJsConstructorForCell(string $modelName = null, bool $hideCaptions = true)
    {
        $widget = $this->getWidget();
        $tpl = $this->getFacade()->getElement($widget->getCellWidget());
        // Disable using widget id as control id because this is a template for multiple controls
        $tpl->setUseWidgetId(false);
        
        $modelPrefix = $modelName ? $modelName . '>' : '';
        if ($tpl instanceof ui5Display) {
            $tpl->setValueBindingPrefix($modelPrefix);
            $tpl->setAlignment($this->buildJsAlignment());
        } elseif ($tpl instanceof UI5ValueBindingInterface) {
            $tpl->setValueBindingPrefix($modelPrefix);
        }
        if (($tpl instanceof ui5CompoundControlInterface) && ($hideCaptions === true || $widget->getHideCaption() === true)) {
            return $tpl->buildJsConstructorForMainControl();
        } else {
            return $tpl->buildJsConstructor();
        }
    }
		
    /**
     * Returns the constructor for a sap.m.Column for this DataColumn widget.
     * 
     * @return string
     */
    public function buildJsConstructorForMColumn()
    {
        $col = $this->getWidget();
        $alignment = 'hAlign: ' . $this->buildJsAlignment() . ',';
        $popinDisplay = $col->getHideCaption() || $col->getCellWidget()->getHideCaption() ? 'sap.m.PopinDisplay.WithoutHeader' : 'sap.m.PopinDisplay.Inline';
        
        return <<<JS
        
                    new sap.m.Column({
						popinDisplay: {$popinDisplay},
						demandPopin: true,
						{$this->buildJsPropertyMinScreenWidth()}
						{$this->buildJsPropertyTooltip()}
						{$this->buildJsPropertyWidth()}
						header: [
                            new sap.m.Label({
                                text: "{$col->getCaption()}"
                            })
                        ],
                        {$alignment}
                        {$this->buildJsPropertyVisibile()}
					})
					
JS;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsPropertyMinScreenWidth()
    {
        switch ($this->getWidget()->getVisibility()) {
            case EXF_WIDGET_VISIBILITY_PROMOTED:
                $val = '';
                break;
            case EXF_WIDGET_VISIBILITY_NORMAL:
            default:
                $val = 'Tablet';
        }
        
        if ($val) {
            return 'minScreenWidth: "' . $val . '",';
        } else {
            return '';
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsPropertyTooltip()
     */
    protected function buildJsPropertyTooltip()
    {
        return 'tooltip: "' . $this->escapeJsTextValue($this->buildTextTooltip()) . '",';
    }
    
    protected function buildTextTooltip()
    {
        return $this->getWidget()->getCaption() . ($this->getWidget()->getCaption() ? ': ' : '') . $this->getWidget()->getHint();
    }
    
    /**
     * Builds alignment options like 'hAlign: "Begin",' etc. - allways ending with a comma.
     * 
     * @param string $propertyName
     * @return string
     */
    protected function buildJsAlignment()
    {
        switch ($this->getWidget()->getAlign()) {
            case EXF_ALIGN_RIGHT:
            case EXF_ALIGN_OPPOSITE:
                $alignment = '"End"';
                break;
            case EXF_ALIGN_CENTER:
                $alignment = '"Center"';
                break;
            case EXF_ALIGN_LEFT:
            case EXF_ALIGN_DEFAULT:
            default:
                $alignment = '"Begin"';
                
        }
        
        return $alignment;
    }
    
    protected function buildJsPropertyWidth()
    {
        $dim = $this->getWidget()->getWidth();
        
        if ($dim->isFacadeSpecific()) {
            return 'width: "' . $dim->getValue() . '",';
        }   
        
        return '';
    }
}
?>