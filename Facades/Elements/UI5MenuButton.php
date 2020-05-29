<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\Button;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryButtonTrait;
use exface\Core\Widgets\MenuButton;

/**
 * Generates OpenUI5 MenuButtons for respective widgets
 *
 * @method MenuButton getWidget()
 * 
 * @author Andrej Kabachnik
 *        
 */
class UI5MenuButton extends UI5AbstractElement
{
    use JqueryButtonTrait;
    
    public function buildJsConstructor($oControllerJs = 'oController') : string
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
        
    protected function getCaption() : string
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
                
                /* @var $btnElement \exface\UI5Facade\Facades\Elements\UI5Button */
                $btnElement = $this->getFacade()->getElement($b);
                $handler = $btnElement->buildJsClickViewEventHandlerCall();
                $press = $handler !== '' ? 'press: ' . $handler . ',' : '';
                
                $js .= <<<JS

                        new sap.m.MenuItem({
                            {$properties}
                            text: "{$btnElement->getCaption()}",
                            icon: "{$btnElement->getIconSrc($b->getIcon())}",
                            {$press}
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
    
    /**
     *
     * {@inheritdoc}
     * @see JqueryButtonTrait::buildJsCloseDialog()
     */
    protected function buildJsCloseDialog($widget, $input_element)
    {
        return '';
    }
}