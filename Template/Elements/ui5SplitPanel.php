<?php
namespace exface\OpenUI5Template\Template\Elements;

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
    public function buildJsInitOptions()
    {
        $widget = $this->getWidget();
        $sizeDimension = $widget->getParent() instanceof SplitHorizontal ? $widget->getWidth() : $widget->getHeight();
        $size = $sizeDimension->isUndefined() ? 'auto' : $sizeDimension->getValue();
        return parent::buildJsInitOptions() . '
                    layoutData: new sap.ui.layout.SplitterLayoutData({
                        size: "' . $size . '"
                    })';
    }
}
