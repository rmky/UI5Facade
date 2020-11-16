<?php
namespace exface\UI5Facade\Facades\Elements;

/**
 * @method \exface\Core\Widgets\Split getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5Split extends UI5Container
{
    private $sizesInitial = [];
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Container::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $this->getController()->addOnInitScript($this->buildJsSetSizesInitial("sap.ui.getCore().byId('{$this->getId()}')"));
        $splitter = <<<JS

    new sap.ui.layout.Splitter("{$this->getId()}", {
        height: "100%",
        width: "100%",
        orientation: "{$this->getOrientation()}",
        contentAreas: [
            {$this->buildJsChildrenConstructors()}
        ]
    })
    {$this->buildJsPseudoEventHandlers()}

JS;
        if ($this->hasPageWrapper() === true) {
            return $this->buildJsPageWrapper($splitter);
        }
        
        return $splitter;
    }
        
    /**
     * 
     * @return string
     */
    protected function getOrientation()
    {
        return $this->getWidget()->isSideBySide() === true  ? 'Horizontal' : 'Vertical';
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsSetSizesInitial(string $oSplitJs) : string
    {
        // Calculate initial sizes of the split areas:
        // 1) collect height/width dimensions of split panels depending on orientation
        // 2) calculate UI5 sizes from them and remember the results
        // 3) replace panel dimensions with standard values to avoid percentual values
        // being applied multiple times (e.g. 30% of 30%)
        // NOTE: caching UI5 sizes is important because the original widths of split
        // panels are lost after first run and the method should always yield
        // identical results!
        if (empty($this->sizesInitial)) {
            $widget = $this->getWidget();
            
            foreach ($widget->getPanels() as $panel) {
                if ($widget->isSideBySide()) {
                    $dims[] = $panel->getWidth();
                    if (! $panel->getWidth()->isUndefined()) {
                        $panel->setWidth('100%');
                    }
                } else {
                    $dims[] = $panel->getHeight();
                    if (! $panel->getHeight()->isUndefined()) {
                        $panel->setHeight(null);
                    }
                }
                
            }
            
            foreach ($dims as $dim) {
                switch (true) {
                    case $dim->isUndefined():
                    case $dim->isMax():
                        $this->sizesInitial[] = null;
                        break;
                    case $dim->isRelative():
                        $this->sizesInitial[] = (($widget->isSideBySide() ? $this->getWidthRelativeUnit() : $this->getHeightRelativeUnit()) * $dim->getValue()) . 'px';
                        break;
                    default:
                        $this->sizesInitial[] = $dim->getValue();
                }
            }
        }
        
        $sizesJson = json_encode($this->sizesInitial);
        
        return <<<JS
        
            // Restore initial sizes of split areas
            (function(){
                var aSizes = $sizesJson;
                $oSplitJs.getContentAreas().forEach(function(oControl, i){
                    if (aSizes.length > i) {
                        oControl.setLayoutData(
                            new sap.ui.layout.SplitterLayoutData({size: aSizes[i]})
                        );
                    }
                });
            })();
            
JS;
        
    }
}
