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
    private $widgetsAssignedToContainers = [];
    
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
                {$this->getScrollContainerContent()}
            ]
        })
JS;
    }
    
    /**
     * This function generates the JS-code for the `sap.f.Card`'s which wrap the content declared
     * in the UXON of the `Dashbord`. For each widget in the `Dashboard`, a `Card` gets created. 
     * The `Card` gets its layoutdata (e.g. width and height) assigned from the data given in the   
     * widgets UXON and the widget itself is then being inserted in the `Card`'s content.
     * 
     * @return string
     */
    protected function buildDashboardContentWrapper(int $no) : string
    {
        $js = '';
        $containerWidthIsInUnits = $this->isWidgetContainerWidthInUnits($no);
        
        foreach ($this->widgetsAssignedToContainers[$no] as $widget){
            
            if ($containerWidthIsInUnits === false){
                $widthUnits = $this->getChildrenElementWidthUnitCount($widget);
                $width = "width: '{$widget->getWidth()->getValue()}',";
            } else {
                $widthUnits = $widget->getWidth()->getValue();
                $widget->setWidth("100%");
                $width = 'width: "100%",';
               
            }
            
            
            $height = ($widget->getHeight()->isMax() || $widget->getHeight()->isUndefined()) ? "height : \"100%\"," : "height : \"{$widget->getHeight()->getValue()}\",";
            
            
           // $widget->setHeight("100%");
           // $widget->setWidth("100%");
            
            $js .= <<<JS
                    new sap.f.Card({
                        {$height}
                        {$width}
                        content: [
                            {$this->getFacade()->getElement($widget)->buildJsConstructor()}
                        ] 
                    }).setLayoutData(new sap.f.GridContainerItemLayoutData({
                                columns: {$widthUnits}
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

        $width = ($widget->getWidth()->isMax() || $widget->getWidth()->isUndefined()) ? "100%" : $widget->getWidth()->getValue();
        if (strpos($width, '%') != false) {
            $widthUnits = round((str_replace('%', '', $width) / 10));
            $widget->setWidth("100%");
        } else {
            $widthUnits = 1;
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
    
    /**
     * This Function gets all childwidgets of the Dashboard.
     * 
     * @return UI5Dashboard
     */
    protected function getChildWidgets() : array
    {
        $childWidgets = [];
        foreach ($this->getWidget()->getChildren() as $widget){
            $childWidgets[] = $widget; 
        }
        return $childWidgets;
    }
    
    /**
     * 
     * @param unknown $widget
     * @param int $no
     * @return UI5Dashboard
     */
    protected function assignWidgetToContainerNo($widget, int $no) : UI5Dashboard
    {
        $this->widgetsAssignedToContainers[$no][] = $widget;
        return $this;
    }
    
    /**
     * 
     * @return UI5Dashboard
     */
    protected function assignWidgetsToContainers() : UI5Dashboard
    {
        $childWidgets = $this->getChildWidgets();
        $counter = 0;
        $modeOfCurrentContainerIsInUnits = null;
        
        foreach ($childWidgets as $widget){
            
            $widthIsInUnits = is_numeric($widget->getWidth()->getValue());
            
            if ($modeOfCurrentContainerIsInUnits !== $widthIsInUnits
                && $modeOfCurrentContainerIsInUnits !== null){
                $counter++;
            }
            
            $modeOfCurrentContainerIsInUnits = $widthIsInUnits;
            $this->assignWidgetToContainerNo($widget, $counter);
        }
        return $this;
    }
    
    /**
     * 
     * @return string
     */
    protected function getScrollContainerContent() : string
    {
        $this->assignWidgetsToContainers();
        $js = '';
        
        foreach ($this->widgetsAssignedToContainers as $no => $containerWithWidgets){
            //is true if the width of the widget in this container is given in container units, false if in %, px, etc...
            $containerWidthIsInUnits = $this->isWidgetContainerWidthInUnits($no);
            $containerStyleClass = '';
            
            if ($containerWidthIsInUnits === true) {
                $containerStyleClass .= "dashboard_gridcontainer_layout ";
            }
            
            if ($no == 0){
                $containerStyleClass .= "sapUiTinyMarginTop ";
            }
            
            if ($this->isLastContainerWithWidgets($no) === false){
                $containerStyleClass .= "dashboard_gridcontainer_gap_margin_bottom ";
            } else {
                $containerStyleClass .= "sapUiTinyMarginBottom ";
            }
            
            $js .= <<<JS
            new sap.f.GridContainer({
                    width: "auto",
                    allowDenseFill: true,
                    snapToRow: true,
                    layout: [
                        {
                            columnSize: "calc((100% - 9 * 10px) / 10)",
                            gap: "10px"
                        }
                    ],
                    items: [
                        {$this->buildDashboardContentWrapper($no)}
                    ]
                }).addStyleClass("{$containerStyleClass} sapUiTinyMarginBeginEnd"),

JS;
            
        }
        
        return $js;
    }
    
    /**
     * 
     * @param int $index
     * @return bool
     */
    protected function isWidgetContainerWidthInUnits(int $index) : bool
    {
        return is_numeric($this->widgetsAssignedToContainers[$index][0]->getWidth()->getValue());
    }
        
    /**
     * 
     * @param int $index
     * @return bool
     */
    protected function isLastContainerWithWidgets(int $index) : bool
    {
        if ($this->widgetsAssignedToContainers[$index + 1] == null){
            return true;
        } else {
            return false;
        }
    }
}