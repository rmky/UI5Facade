<?php
namespace exface\OpenUI5Template\Templates\Elements;

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
    
    public function buildJsConstructor($oController = 'oController') : string
    {
        $widget = $this->getWidget();
        $left_buttons = '';
        $right_buttons = '';
        foreach ($widget->getButtons() as $btn) {
            switch ($btn->getAlign()) {
                case EXF_ALIGN_OPPOSITE:
                case EXF_ALIGN_RIGHT:
                    $right_buttons = $this->getTemplate()->getElement($btn)->buildJsConstructor() . ",\n" . $right_buttons;
                    break;
                default:
                    $left_buttons .= $this->getTemplate()->getElement($btn)->buildJsConstructor() . ",\n";
            }
        }
        
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
                $buttons .= ($buttons ? ", \n" : '') . $this->getTemplate()->getElement($btn)->buildJsConstructor();
            }
        }
        return $buttons;
    }
}
?>