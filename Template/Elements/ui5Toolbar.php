<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryToolbarTrait;
use exface\Core\Widgets\Toolbar;

/**
 * The AdminLTE implementation of the Toolbar widget
 *
 * @author Andrej Kabachnik
 *        
 * @method Toolbar getWidget()
 */
class ui5Toolbar extends ui5AbstractElement
{
    use JqueryToolbarTrait;
    
    public function generateHtml()
    {
        return '';
    }
    
    public function generateJs()
    {
        $js = '';
        
        foreach ($this->getWidget()->getButtons() as $btn) {
            $js .= $this->getTemplate()->getElement($btn)->generateJs();
        }
        
        return $js;
    }
    
    public function generateJsConstructor()
    {
        $widget = $this->getWidget();
        
        foreach ($widget->getButtons() as $btn) {
            switch ($btn->getAlign()) {
                case EXF_ALIGN_OPPOSITE:
                case EXF_ALIGN_RIGHT:
                    $right_buttons = $this->getTemplate()->getElement($btn)->generateJsConstructor() . ",\n" . $right_buttons;
                    break;
                default:
                    $left_buttons .= $this->getTemplate()->getElement($btn)->generateJsConstructor() . ",\n";
            }
        }
        
        $left_buttons = '';
        $toolbar = <<<JS
			new sap.m.OverflowToolbar({
				content: [
					{$this->getCaption()}
                    {$left_buttons}
                    new sap.m.ToolbarSpacer(),
                    {$right_buttons}
				]
			})
JS;
        return $toolbar;
        
    }
    
    protected function buildJsButtons()
    {
        $widget = $this->getWidget();
        $buttons = '';
        foreach ($widget->getButtonGroups() as $btn_group) {
            $buttons .= ($buttons && $btn_group->getVisibility() > EXF_WIDGET_VISIBILITY_OPTIONAL ? ",\n new sap.m.ToolbarSeparator()" : '');
            foreach ($btn_group->getButtons() as $btn) {
                $buttons .= ($buttons ? ", \n" : '') . $this->getTemplate()->getElement($btn)->generateJsConstructor();
            }
        }
        return $buttons;
    }
}
?>