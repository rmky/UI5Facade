<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\SplitPanel;
use exface\Core\Widgets\SplitHorizontal;

/**
 * 
 * @method SplitPanel getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5SplitPanel extends ui5Panel
{
    public function buildJsProperties()
    {
        $widget = $this->getWidget();
        $sizeDimension = $widget->getParent() instanceof SplitHorizontal ? $widget->getWidth() : $widget->getHeight();
        switch (true) {
            case $sizeDimension->isTemplateSpecific() === true:
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
