<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryButtonGroupTrait;

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