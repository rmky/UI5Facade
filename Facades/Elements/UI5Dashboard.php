<?php
namespace exface\UI5FAcade\Facades\Elements;

use exface\Core\Widgets\Dashboard;
use exface\Core\Interfaces\WidgetInterface;

/**
 * A `Dashboard` is a Widget to display multiple Widgets in an grid-like layout, to let the user have an
 * easy and instant overview about certain data.
 * 
 * This `Dashboard` in UI5 consists of `sap.f.GridContainer`'s, which align the child widgets 
 * in a grid. There might be used multiple instances of `sap.f.Gridcontainer` for one `Dashboard` to support
 * not only the use of units like '%' or 'px' as width parameters, but also the use of an `integer` to define the
 * number of columns one Widget uses.
 * 
 * The children Widgets itself are wrapped in instances of `sap.f.Card`.
 * 
 * The `GridContainer` always has the parameters `allowDenseFill` and `snapToRow` set to `true`
 * to automatically optimize the alignment of the Cards. Furthermore the `GridContainer` may use 
 * the css-class `dashboard_gridcontainer_layout`, which sets the minimum and maximum width
 * of its child elements, if the size of those elements is only given by column-count.
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
        return $this->buildJsLayoutConstructor($this->buildJsLayoutGridContainer($oControllerJs));
    }
    
    /**
     * This function returns the JS-code for the `Dashboard`. 
     * A Dashboard is getting wrapped in a `sap.m.ScrollContainer` to allow scrolling through the elements of
     * a dashboard.
     * 
     * @see UI5Panel::buildJsLayoutConstructor()
     */
    public function buildJsLayoutConstructor(string $content = null, bool $useFormLayout = true) : string
    {
        
        $height = ($this->getWidget()->getHeight()->getValue()) ? "{$this->getWidget()->getHeight()->getValue()}" : "100%";
        $width = ($this->getWidget()->getWidth()->getValue()) ? "{$this->getWidget()->getWidth()->getValue()}": "100%";
        
        
        return <<<JS
        new sap.m.ScrollContainer({
            vertical: true,
            width: "{$width}",
            height: "{$height}",
            content: [
                {$content}
            ]
        })
JS;
    }
    
    /**
     * This function generates the JS-code for the `sap.f.Card`'s which wrap the content declared
     * in the UXON of the `Dashbord`. For each widget in the `Dashboard`, a `Card` gets created. 
     * The `Card` gets its layoutdata (e.g. width and height) assigned from the data given in the   
     * widgets UXON and the widget itself is then being inserted in the `Card`'s content.
     * The parameter of this function is the number of the Gridcontainer for which the cards shall be generated.
     * 
     * Generating the correct width values for the cards is a bit tricky, because this process needs to be adapted
     * to the unit the width of the element is given. 
     *     
     *      If the width is given as an integer, this number is set directly into the card's 
     *      `GridContainerItemLayoutData`'s `columns` property. The width of the widget in the card itself
     *      is set to 100%.
     * 
     *      If the width is given as an percentage, the `columns` of the `GridContainerItemLayoutData` are getting
     *      calulated with the function `getChildrenElementWidthUnitCount()`. The width of the widget in the card
     *      is then set to 100%.
     *      
     * @return string
     */
    protected function buildDashboardContentWrapper(string $oControllerJs, int $no) : string
    {
        $js = '';
        $containerWidthIsInUnits = $this->isWidgetContainerWidthInUnits($no);
        
        foreach ($this->widgetsAssignedToContainers[$no] as $widget){
            
            if ($containerWidthIsInUnits === false){
                $widthUnits = $this->getChildrenElementWidthUnitCount($widget);
            } else {
                $widthUnits = $widget->getWidth()->getValue();
                $widget->setWidth("100%");

               
            }
            
            $this->setWidthOfBoxElementChildren($widget, "100%");
            $this->setHeightOfBoxElementChildren($widget, "100%");
            
            $element = $this->getFacade()->getElement($widget);
            $element->setLayoutData("new sap.f.GridContainerItemLayoutData({columns: {$widthUnits}})");
            $js .= ($js ? ', ' : '') . $element->buildJsConstructor($oControllerJs);
        }
        
        return $js;
    }
    
    /**
     * 
     * @param WidgetInterface $widget
     * @param string $width
     * @return UI5Dashboard
     */
    protected function setWidthOfBoxElementChildren(WidgetInterface $widget, string $width) : UI5Dashboard
    {
        foreach ($widget->getWidgets() as $child) {
            $child->setWidth($width);
        }
        return $this;
    }
    
    protected function setHeightOfBoxElementChildren(WidgetInterface $widget, string $height) : UI5Dashboard
    {
        foreach ($widget->getWidgets() as $child) {
            $child->setHeight($height);
        }
        return $this;
    }
    
    
    /**
     * 
     * @param WidgetInterface $widget
     * @return string
     */
    protected function getChildrenElementWidthUnitCount(WidgetInterface $widget) : string
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
     * 
     * @param WidgetInterface $widget
     * @param int $no
     * @return UI5Dashboard
     */
    protected function assignWidgetToContainerNo(WidgetInterface $widget, int $no) : UI5Dashboard
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
        $childWidgets = $this->getWidget()->getWidgets();
        $counter = 0;
        $modeOfCurrentContainerIsInUnits = null;
        
        foreach ($childWidgets as $widget){
            
            $widthDim = $widget->getWidth();
            $widthIsInUnits = $widthDim->isRelative();
            
            if ($widthDim->isUndefined()){
                $widget->setWidth(1);
                $widthIsInUnits = true;    
            }
            
            if ($modeOfCurrentContainerIsInUnits !== $widthIsInUnits
                && $modeOfCurrentContainerIsInUnits !== null){ 
                
                    if ($modeOfCurrentContainerIsInUnits === false){
                        $remainingWidthInRow = $this->getRemainingWidthInContainerNo($counter);
                        if ($remainingWidthInRow !== ''){
                            $widget->setWidth($remainingWidthInRow);   
                            $modeOfCurrentContainerIsInUnits = $widthIsInUnits;
                            $this->assignWidgetToContainerNo($widget, $counter);
                            $counter++;
                            continue;
                        }
                    }
                    
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
    protected function buildJsLayoutGridContainer(string $oControllerJs) : string
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
                        {$this->buildDashboardContentWrapper($oControllerJs, $no)}
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
        return $this->widgetsAssignedToContainers[$index][0]->getWidth()->isRelative();
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
    
    /**
     * 
     * @param int $no
     * @return string
     */
    protected function getRemainingWidthInContainerNo(int $no) : string
    {
        $widthSum = 0;
        
        foreach ($this->widgetsAssignedToContainers[$no] as $widget){
            $width = $widget->getWidth()->getValue();
            $widthVal = str_replace('%', '', $width);
            $widthSum += $widthVal;
            
            switch ($widthSum){
                case $widthSum == 100:
                    $widthSum = 0;
                    break;
                case $widthSum > 100:
                    $widthSum = $widthVal;
            }
        }
        
        if ($widthSum == 0){
            return '';
        } else {
            return (100-$widthSum) . '%';
        }
    }
}