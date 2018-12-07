<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryContainerTrait;
use exface\Core\Widgets\NavTiles;

/**
 * Renders a default container for NavTiles.
 * 
 * @method NavTiles getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5NavTiles extends ui5Container
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Container::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        // If the NavTiles is the root widget of a view, it will have a header with the caption
        // of the first tile group - so just hide the caption of that group to avoid duplicates.
        if ($this->getWidget()->hasParent() === false) {
            $this->getWidget()->getWidgetFirst()->setHideCaption(true);
        }
        return parent::buildJsConstructor($oControllerJs);
    }
}