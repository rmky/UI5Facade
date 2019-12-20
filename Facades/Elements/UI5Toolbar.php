<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryToolbarTrait;
use exface\Core\Widgets\Toolbar;

/**
 * The AdminLTE implementation of the Toolbar widget
 *
 * @author Andrej Kabachnik
 *        
 * @method Toolbar getWidget()
 */
class UI5Toolbar extends UI5AbstractElement
{
    use JqueryToolbarTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $left_buttons = $this->buildJsConstructorsForLeftButtons();
        $right_buttons = $this->buildJsConstructorsForRightButtons();
        
        $toolbar = <<<JS
			new sap.m.OverflowToolbar({
				content: [
					{$this->getCaption()}
                    {$left_buttons}
                    new sap.m.ToolbarSpacer(),
                    {$right_buttons}
				]
			}).addStyleClass("{$this->buildCssElementClass()}")
JS;
        return $toolbar;
        
    }
    
    /**
     * Function for building the JS-Code of buttons, aligned left in an instance of a Toolbox.
     * 
     * @return string
     */
    public function buildJsConstructorsForLeftButtons() : string
    {
        $left_buttons = '';
        foreach ($this->getWidget()->getButtons() as $btn) {
            switch ($btn->getAlign()) {
                case EXF_ALIGN_OPPOSITE:
                case EXF_ALIGN_RIGHT:
                    break;
                default:
                    $left_buttons .= $this->getFacade()->getElement($btn)->buildJsConstructor() . ",\n";
            }
        }
        return $left_buttons;
    }
    
    /**
     * Function for building the JS-Code of buttons, aligned right in an instance of a Toolbox.
     *  
     * Normally the first right-aligned button is positioned right-most - this means, the
     * rendering order is reversed for right-aligned buttons. This can be explicitly disabled
     * by setting $reverseOrder = false.
     * 
     *  
     * @param bool $reverseOrder
     * @return string
     */
    public function buildJsConstructorsForRightButtons(bool $reverseOrder = true) : string
    {
        $right_buttons = '';
        foreach ($this->getWidget()->getButtons() as $btn) {
            switch ($btn->getAlign()) {
                case EXF_ALIGN_OPPOSITE:
                case EXF_ALIGN_RIGHT:
                    if ($reverseOrder === true) {
                        $this->getFacade()->getElement($btn)->buildJsConstructor() . ",\n" . $right_buttons;
                    } else {
                        $right_buttons .= $this->getFacade()->getElement($btn)->buildJsConstructor() . ",\n";
                    }
                    break;
            }
        }
        return $right_buttons;
    }
    
    /**
     * 
     * @see JqueryToolbarTrait::buildJsButtons()
     */
    protected function buildJsButtons()
    {
        $widget = $this->getWidget();
        $buttons = '';
        foreach ($widget->getButtonGroups() as $btn_group) {
            $buttons .= ($buttons && $btn_group->getVisibility() > EXF_WIDGET_VISIBILITY_OPTIONAL ? ",\n new sap.m.ToolbarSeparator()" : '');
            foreach ($btn_group->getButtons() as $btn) {
                $buttons .= ($buttons ? ", \n" : '') . $this->getFacade()->getElement($btn)->buildJsConstructor();
            }
        }
        return $buttons;
    }
}
?>