<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\ContextBar;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryContextBarAjaxTrait;

/**
 * Generates AJAX data for OpenUI5 context bar
 * 
 * @method ContextBar getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5ContextBar extends ui5AbstractElement
{
    use JqueryContextBarAjaxTrait {
        buildHtml as buildHtmlViaTrait;
        buildJs as buildJsViaTrait;
    }
    
    public function buildJsonContextData() : string
    {
        return $this->buildHtmlViaTrait();
    }
}
?>