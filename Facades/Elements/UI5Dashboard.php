<?php
namespace exface\UI5FAcade\Facades\Elements;

/**
 * 
 * @author tmc
 *
 */
class UI5Dashboard extends UI5Panel
{

    public function buildJsConstructor($oControllerJs = 'oController') : string
    {   
        $dashboard = $this->buildJsConstructorForDashboard();
        
        return $dashboard;
    }
    
    protected function buildJsConstructorForDashboard() : string
    {
        
        $height = ($this->getWidget()->getHeight()->getValue()) ? "height : \"{$this->getWidget()->getHeight()->getValue()}\"," : 'height : "100%",';
        $width = ($this->getWidget()->getWidth()->getValue()) ? "width : \"{$this->getWidget()->getWidth()->getValue()}\"," : 'width : "100%",';
        
        return <<<JS
        new sap.f.GridContainer({
            {$height}
            {$width}
            layout: [
                {
                    rowSize: "10%",
                    columnSize:"10%",
                    gap: "6px"
                }
            ],
            items: [
                {$this->buildDashboardContentWrapper()}
            ]
        })
        
JS;
    }
    
    protected function buildDashboardContentWrapper() : string
    {
        $js = '';
        
        foreach ($this->getWidget()->getChildren() as $widget){
            
            $widthUnits = $this->getChildrenElementWidthUnitCount($widget);
            $heightUnits = $this->getChildrenElementHeightUnitCount($widget);
            
            $js .= <<<JS
                    new sap.f.Card({
                        content: [
                            {$this->getFacade()->getElement($widget)->buildJsConstructor()}
                        ] 
                    }).setLayoutData(new sap.f.GridContainerItemLayoutData({
                                columns: {$widthUnits},
                                minRows: {$heightUnits}
                            })),

JS;
        }
        
        return $js;
    }
    
    protected function getChildrenElementWidthUnitCount($widget) : int
    {
        $width = $widget->getWidth()->getValue();
        
        if (strpos($width, '%') !== false){
            $widthUnits = round((str_replace('%', '', $width) / 10));   
        } else {
            $widthUnits = round(10 / $this->getWidget()->countWidgetsVisible());
        }
        
        return $widthUnits;
    }
    
    protected function getChildrenElementHeightUnitCount($widget) : int
    {
        $height = $widget->getHeight()->getValue();
        
        if (strpos($height, '%') !== false){
            $heightUnits = round((str_replace('%', '', $height) / 10));
            
        } else {
            $heightUnits = 6;
        }
        
        return $heightUnits;
    }
}