<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryButtonGroupTrait;

/**
 * The AdminLTE implementation of the ButtonGroup widget
 *
 * @author Andrej Kabachnik
 *        
 * @method Toolbar getWidget()
 */
class ui5ButtonGroup extends ui5AbstractElement
{
    use JqueryButtonGroupTrait;
}
?>