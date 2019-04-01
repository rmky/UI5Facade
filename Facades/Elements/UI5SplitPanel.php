<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\SplitPanel;
use exface\Core\Widgets\SplitHorizontal;

/**
 * 
 * @method SplitPanel getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5SplitPanel extends UI5Panel
{
    public function buildJsProperties()
    {
        $widget = $this->getWidget();
        $sizeDimension = $widget->getParent() instanceof SplitHorizontal ? $widget->getWidth() : $widget->getHeight();
        switch (true) {
            case $sizeDimension->isFacadeSpecific() === true:
                $size = $sizeDimension->getValue();
            default:
                $size = 'auto';
        }
        return parent::buildJsProperties() . '
                    layoutData: new sap.ui.layout.SplitterLayoutData({
                        size: "' . $size . '"
                    })';
    }
}
