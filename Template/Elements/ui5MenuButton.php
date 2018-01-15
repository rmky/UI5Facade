<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\Button;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryButtonTrait;
use exface\Core\Widgets\MenuButton;

/**
 * Generates OpenUI5 MenuButtons for respective widgets
 *
 * @method MenuButton getWidget()
 * 
 * @author Andrej Kabachnik
 *        
 */
class ui5MenuButton extends ui5AbstractElement
{
    use JqueryButtonTrait;

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::generateJs()
     */
    public function generateJs()
    {
        $output = '';
        foreach ($this->getWidget()->getButtons() as $b) {
            if ($js_click_function = $this->getTemplate()->getElement($b)->buildJsClickFunction()) {
                $output .= "
					function " . $this->getTemplate()->getElement($b)->buildJsClickFunctionName(). "(){
						" . $js_click_function . "
					}
					";
            }
        }
        return $output;
    }
    
    public function buildJsConstructor()
    {
        return <<<JS

    new sap.m.MenuButton("{$this->getId()}", {
        text: "{$this->getCaption()}",
        {$this->buildJsPropertyIcon()}
        menu: [
            new sap.m.Menu({
                items: [
                    {$this->buildJsMenuItems()}
                ]
            })
		]
	})

JS;
    }
        
    protected function getCaption()
    {
        $caption = parent::getCaption();
        if ($caption == '') {
            $caption = '...';
        }
        return $caption;
    }
        
    protected function buildJsMenuItems()
    {
        $js = '';
        $last_parent = null;
        $start_section = false;
        /* @var $b \exface\Core\Widgets\Button */
        foreach ($this->getWidget()->getButtons() as $b) {
            if (is_null($last_parent)){
                $last_parent = $b->getParent();
            }
            
            // Create a menu entry: a link for actions or a separator for empty buttons
            if (! $b->getCaption() && ! $b->getAction()){
                $start_section = true;
            } else {
                $properties = '';
                if ($b->getParent() !== $last_parent){
                    $start_section = true;
                    $last_parent = $b->getParent();
                }
                
                if ($start_section) {
                    $properties .= 'startsSection: true,';
                }
                $js .= <<<JS

                        new sap.m.MenuItem({
                            {$properties}
                            text: "{$b->getCaption()}",
                            icon: "{$this->getIconSrc($b->getIcon())}",
                            press: function(oEvent) {
                                {$this->getTemplate()->getElement($b)->buildJsClickFunctionName()}();
                            }
                        }),

JS;
            }
        }
        return $js;
    }
    
    protected function buildJsMenuItem(Button $button)
    {
        
    }
    
    protected function buildJsPropertyIcon()
    {
        $widget = $this->getWidget();
        return ($widget->getIcon() ? 'icon: "' . $this->getIconSrc($widget->getIcon()) . '", ' : '');
    }
}
?>