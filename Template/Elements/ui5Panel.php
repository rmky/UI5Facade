<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Interfaces\Widgets\iFillEntireContainer;

class ui5Panel extends ui5Container
{
    
    public function generateJsConstructor()
    {
        return  <<<JS
                new sap.m.Panel({
                    height: "100%",
                    content: [
                        {$this->buildJsChildrenConstructors()}
                    ],
                    {$this->buildJsInitOptions()}
                }).addStyleClass("sapUiNoContentPadding")
JS;
    }
                    
    public function buildJsLayoutConstructor($content)
    {
        $widget = $this->getWidget();
        if ($widget->countWidgetsVisible() === 1 && ($widget->getWidget(0) instanceof iFillEntireContainer)) {
            return $content;
        } else {
            return $this->buildJsLayoutForm($content);
        }
    }
    
    protected function buildJsLayoutForm($content)
    {
        return <<<JS
        
            new sap.ui.layout.form.SimpleForm({
                width: "100%",
                editable: true,
                layout: "ResponsiveGridLayout",
                labelSpanXL: 4,
    			labelSpanL: 4,
    			labelSpanM: 12,
    			labelSpanS: 12,
    			adjustLabelSpan: false,
    			emptySpanXL: 0,
    			emptySpanL: 0,
    			emptySpanM: 0,
    			emptySpanS: 0,
    			columnsXL: 2,
    			columnsL: 2,
    			columnsM: 2,
                singleContainerFullSize: false,
                content: [
                    {$content}
                ]
            })
            
JS;
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