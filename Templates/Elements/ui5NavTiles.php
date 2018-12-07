<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryContainerTrait;
use exface\Core\Widgets\Container;

/**
 * Renders a sap.m.Panel with no margins or paddings for a simple Container widget.
 * 
 * @method Container getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5NavTiles extends ui5Container
{
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