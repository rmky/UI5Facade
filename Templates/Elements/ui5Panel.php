<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Interfaces\Widgets\iFillEntireContainer;
use exface\OpenUI5Template\Templates\Interfaces\ui5ControlWithToolbarInterface;

class ui5Panel extends ui5Container
{
    
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        return  <<<JS
                new sap.m.Panel("{$this->getId()}", {
                    height: "100%",
                    content: [
                        {$this->buildJsChildrenConstructors()}
                    ],
                    {$this->buildJsProperties()}
                }).addStyleClass("sapUiNoContentPadding")
JS;
    }
                    
    public function buildJsLayoutConstructor(string $content = null, bool $use_form = true) : string
    {
        $widget = $this->getWidget();
        $content = $content ?? $this->buildJsChildrenConstructors();
        if ($widget->countWidgetsVisible() === 1 && ($widget->getWidgetFirst() instanceof iFillEntireContainer)) {
            return $content;
        } elseif ($use_form) {
            return $this->buildJsLayoutForm($content);
        } else {
            return $this->buildJsLayoutGrid($content);
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Container::buildJsChildrenConstructors()
     */
    public function buildJsChildrenConstructors() : string
    {
        $js = '';
        foreach ($this->getWidget()->getWidgets() as $idx => $widget) {
            // Larger widgets need a Title before them to make SimpleForm generate a new FormContainer
            if ($idx > 0 && (($widget instanceof iFillEntireContainer) || $widget->getWidth()->isMax())) {
                $js .= ($js ? ",\n" : '') . 'new sap.ui.core.Title()';                
            } 
            $js .= ($js ? ",\n" : '') . $this->getTemplate()->getElement($widget)->buildJsConstructor();
        }
        
        return $js;
    }
    
    protected function buildJsLayoutForm($content)
    {
        return <<<JS
        
            new sap.ui.layout.form.SimpleForm({
                width: "100%",
                {$this->buildJsPropertyEditable()}
                layout: "ResponsiveGridLayout",
                labelSpanXL: 5,
    			labelSpanL: 4,
    			labelSpanM: 4,
    			labelSpanS: 5,
    			adjustLabelSpan: false,
    			emptySpanXL: 0,
    			emptySpanL: 0,
    			emptySpanM: 0,
    			emptySpanS: 0,
    			columnsXL: 2,
    			columnsL: 2,
    			columnsM: 2,
                singleContainerFullSize: true,
                content: [
                    {$content}
                ]
            })
            
JS;
    }
            
    /**
     * Returns the editable property for the ui5-form with property name and tailing comma.
     * 
     * A ui5-form is marked editable if it contains at least one visible input widget.
     * Non-editable forms are more compact, so it is a good idea only to use editable
     * ones if really editing.
     * 
     * @return string
     */
    protected function buildJsPropertyEditable()
    {
        $editable = 'false';
        foreach ($this->getWidget()->getInputWidgets() as $input){
            if (! $input->isHidden()) {
                $editable = 'true';
                break;
            }
        }
        return 'editable: ' . $editable . ',';
    }
    
    protected function buildJsLayoutGrid($content)
    {
        return <<<JS

            new sap.ui.layout.Grid({
                height: "100%",
                defaultSpan: "XL4 L4 M6 S12",
                content: [
                    {$content}
				]
            })

JS;
    }
}
?>