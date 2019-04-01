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
    
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $widget = $this->getWidget();
        $left_buttons = '';
        $right_buttons = '';
        foreach ($widget->getButtons() as $btn) {
            switch ($btn->getAlign()) {
                case EXF_ALIGN_OPPOSITE:
                case EXF_ALIGN_RIGHT:
                    $right_buttons = $this->getFacade()->getElement($btn)->buildJsConstructor() . ",\n" . $right_buttons;
                    break;
                default:
                    $left_buttons .= $this->getFacade()->getElement($btn)->buildJsConstructor() . ",\n";
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
                $buttons .= ($buttons ? ", \n" : '') . $this->getFacade()->getElement($btn)->buildJsConstructor();
            }
        }
        return $buttons;
    }
}
?>