<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Interfaces\Widgets\iFillEntireContainer;

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
                    
    public function buildJsLayoutConstructor($content, $use_form = true)
    {
        $widget = $this->getWidget();
        if ($widget->countWidgetsVisible() === 1 && ($widget->getWidget(0) instanceof iFillEntireContainer)) {
            return $content;
        } elseif ($use_form) {
            return $this->buildJsLayoutForm($content);
        } else {
            return $this->buildJsLayoutGrid($content);
        }
    }
    
    protected function buildJsLayoutForm($content)
    {
        return <<<JS
        
            new sap.ui.layout.form.SimpleForm({
                width: "100%",
                {$this->buildJsPropertyEditable()}
                layout: "ResponsiveGridLayout",
                labelSpanXL: 4,
    			labelSpanL: 4,
    			labelSpanM: 4,
    			labelSpanS: 5,
    			adjustLabelSpan: false,
    			emptySpanXL: 0,
    			emptySpanL: 0,
    			emptySpanM: 0,
    			emptySpanS: 0,
    			columnsXL: 3,
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