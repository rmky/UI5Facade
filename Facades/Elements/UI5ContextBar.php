<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\ContextBar;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryContextBarAjaxTrait;

/**
 * Generates AJAX data for OpenUI5 context bar
 * 
 * @method ContextBar getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5ContextBar extends UI5AbstractElement
{
    use JqueryContextBarAjaxTrait;
}
?>