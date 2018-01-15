<?php
namespace exface\OpenUI5Template\Template\Elements;

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
    public function generateJs()
    {
        $js = '';
        foreach ($this->getWidget()->getButtons() as $b) {
            $js .= $this->getTemplate()->getElement($b)->generateJs();
        }
        return $js;
    }
    
    public function buildJsConstructor()
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
    
    protected function buildJsButtonListItem(Button $button)
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
        
    protected function buildJsPropertyCaption()
    {
        return ! $this->getCaption() ? '' : <<<JS

        headerText: "{$this->getCaption()}", 

JS;
    }
}
?>