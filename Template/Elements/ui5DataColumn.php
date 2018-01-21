<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\DataColumn;

/**
 *
 * @method DataColumn getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class ui5DataColumn extends ui5AbstractElement
{
    public function generateJs()
    {
        return '';
    }
    
    public function buildJsConstructor()
    {
        return $this->buildJsConstructorForUiColumn();
    }

    /**
     * Returns the constructor for a sap.ui.table.Column created for this DataColumn widget
     * 
     * @return string
     */
    protected function buildJsConstructorForUiColumn()
    {
        $widget = $this->getWidget();
        $visible = $widget->isHidden() ? 'false' : 'true';
        switch ($widget->getAlign()) {
            case EXF_ALIGN_RIGHT:
            case EXF_ALIGN_OPPOSITE:
                $alignment = 'textAlign: sap.ui.core.TextAlign.End';
                break;
            case EXF_ALIGN_CENTER:
                $alignment = 'textAlign: sap.ui.core.TextAlign.Center';
                break;
            case EXF_ALIGN_LEFT:
            case EXF_ALIGN_DEFAULT:
            default:
                $alignment = 'textAlign: sap.ui.core.TextAlign.Begin';
                                
        }
        
        return <<<JS
	 new sap.ui.table.Column({
	    label: new sap.ui.commons.Label({
            text: "{$this->getCaption()}"
        })
        , autoResizable: true
        , tooltip: "{$this->escapeJsTextValue($this->buildJsPropertyTooltip())}"
	    , template: new sap.ui.commons.TextField({
            {$alignment}
        }).bindProperty("value", "{$widget->getDataColumnName()}")
	    , sortProperty: "{$widget->getAttributeAlias()}"
	    , filterProperty: "{$widget->getAttributeAlias()}"
		, visible: {$visible}
	})
JS;
    }
            
    protected function buildJsPropertyTooltip()
    {
        return $this->getWidget()->getCaption() . ($this->getWidget()->getCaption() ? ': ' : '') . $this->getWidget()->getHint();
    }
}
?>