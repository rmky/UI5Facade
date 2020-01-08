<?php
namespace exface\UI5FAcade\Facades\Elements;

use exface\Core\Widgets\Dashboard;

/**
 * 
 * @author tmc
 *
 * @method Dashboard getWidget()
 */
class UI5Dashboard extends UI5Panel
{
    /**
     * 
     * @param string $oControllerJs
     * @return string
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {   
        $dashboard = $this->buildJsConstructorForDashboard();
        
        return $dashboard;
    }
    
    /**
     * This function returns the JS-code for the `Dashboard`. 
     * A `Dashboard` in UI5 consists of a `sap.f.GridContainer`, which aligns the child widgets 
     * in a grid. 
     * The children Widgets itself are wrapped in instances of `sap.f.Card`, whose code is getting
     * created by call of the function `buildDashboardContentWrapper()`.
     * The `GridContainer` always has the parameters `allowDenseFill` and `snapToRow` set to `true`
     * to automatically optimize the alignment of the Cards. Furthermore the `GridContainer` uses 
     * the css-class `dashboard_gridcontainer_layout`, which sets the minimum and maximum width
     * of its child elements. 
     * 
     * @return string
     */
    protected function buildJsConstructorForDashboard() : string
    {
        
        $height = ($this->getWidget()->getHeight()->getValue()) ? "{$this->getWidget()->getHeight()->getValue()}" : "100%";
        $width = ($this->getWidget()->getWidth()->getValue()) ? "{$this->getWidget()->getWidth()->getValue()}": "100%";
        
        
        return <<<JS
        new sap.m.ScrollContainer({
            vertical: true,
            width: "{$width}",
            height: "{$height}",
            content: [
                new sap.f.GridContainer({
                    width: "auto",
                    allowDenseFill: true,
                    snapToRow: true,
                    layout: [
                        {
                          //  minColumnSize: "300px",
                          //  columnSize: "calc((100% - 2 * 5px) / 3)",
                            gap: "5px"
                        }
                    ],
                    items: [
                        {$this->buildDashboardContentWrapper()}
                    ]
                }).addStyleClass("dashboard_gridcontainer_layout sapUiTinyMargin")
            ]
        })
JS;
    }
    
    /**
     * This function generates the JS-code for the `sap.f.Card`'s which wrap the content declared
     * in the UXON of the `Dashbord`. For each widget in the `Dashboard`, a `Card` gets created. 
     * The `Card` gets its layoutdata (e.g. width and height) assigned from the data given in the   
     * widgets UXON and the widget itseif is then ebing inserted in the `Card`'s content.
     * 
     * @return string
     */
    protected function buildDashboardContentWrapper() : string
    {
        $js = '';
        
        foreach ($this->getWidget()->getChildren() as $widget){
            
            $widthUnits = $this->getChildrenElementWidthUnitCount($widget);
            $heightUnits = $this->getChildrenElementHeightUnitCount($widget);
           
            $height = ($widget->getHeight()->isMax() || $widget->getHeight()->isUndefined()) ? "height : \"100%\"," : "height : \"{$widget->getHeight()->getValue()}\",";
            
            if ($widthUnits == ''){
                $width = ($widget->getWidth()->isMax() || $widget->getWidth()->isUndefined()) ? "width : \"100%\"," : "width : \"{$widget->getWidth()->getValue()}\",";
            } else {
                $width = '';
            }
            
            
            $widget->setHeight("100%");
            $widget->setWidth("100%");
            
            $js .= <<<JS
                    new sap.f.Card({
                        {$height}
                        {$width}
                        content: [
                            {$this->getFacade()->getElement($widget)->buildJsConstructor()}
                        ] 
                    }).setLayoutData(new sap.f.GridContainerItemLayoutData({
                                {$widthUnits}
                            })),

JS;
        }
        
        return $js;
    }
    
    /**
     * 
     * @param unknown $widget
     * @return string
     */
    protected function getChildrenElementWidthUnitCount($widget) : string
    {
        $width = $widget->getWidth()->getValue();
        
        switch (true){
            case (is_numeric($width)):
                $widthUnits = "columns: {$width}";
                break;
                
            default:
                $widthUnits = '';
        }
                
        return $widthUnits;
    }
    
    /**
     * 
     * @param unknown $widget
     * @return int
     */
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