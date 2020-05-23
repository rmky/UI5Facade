<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\NavTiles;

/**
 * Renders a default container for NavTiles.
 * 
 * @method NavTiles getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5NavTiles extends UI5Container
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Container::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        // If the NavTiles is the root widget of a view, it will have a header with the caption
        // of the first tile group - so just hide the caption of that group to avoid duplicates.
        $widget = $this->getWidget();
        if ($widget->hasParent() === false && $widget->hasWidgets()) {
            $widget->getWidgetFirst()->setHideCaption(true);
        }
        return parent::buildJsConstructor($oControllerJs);
    }
}