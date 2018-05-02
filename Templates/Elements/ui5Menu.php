<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\Menu;
use exface\Core\Widgets\Button;

/**
 * Generates OpenUI5 controls for menu widgets: 
 * 
 * @method Menu getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Menu extends ui5AbstractElement
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oController = 'oController') : string
    {
        return <<<JS

    new sap.m.List({
        {$this->buildJsProperties()}
		items: [
			{$this->buildJsButtonsListItems()}
		]
	}).addStyleClass("exf-menu")

JS;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsButtonsListItems()
    {
        $js = '';
        $last_parent = null;
        
        foreach ($this->getWidget()->getButtons() as $b) {
            if (is_null($last_parent)){
                $last_parent = $b->getParent();
            }
            
            if ($b->getParent() !== $last_parent){
                $js .= <<<JS

            new sap.m.StandardListItem({
				title: ""
			}),

JS;
                $last_parent = $b->getParent();
            }
            
            $js .= $this->buildJsButtonListItem($b);
            
        }
        return $js;
    }
    
    /**
     * 
     * @param Button $button
     * @return string
     */
    protected function buildJsButtonListItem(Button $button) : string
    {
        $btn_element = $this->getTemplate()->getElement($button);
        
        if ($button->getIcon()) {
            $icon = 'icon: "' . $btn_element->buildCssIconClass($button->getIcon()) . '",';
        } else {
            $icon = '';
        }
        
        return <<<JS

            new sap.m.StandardListItem({
				title: "{$btn_element->getCaption()}",
                iconDensityAware: false,
				iconInset: false,
                {$icon}
				type: "Active",
				press: function(){ {$btn_element->buildJsClickFunctionName()}() },
			}),

JS;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsPropertyCaption()
    {
        return ! $this->getCaption() ? '' : <<<JS

        headerText: "{$this->getCaption()}", 

JS;
    }
}
?>