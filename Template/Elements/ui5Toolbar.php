<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryToolbarTrait;

/**
 * The AdminLTE implementation of the Toolbar widget
 *
 * @author Andrej Kabachnik
 *        
 * @method Toolbar getWidget()
 */
class ui5Toolbar extends ui5AbstractElement
{
    use JqueryToolbarTrait;
    
    public function generateHtml()
    {
        return '';
    }
}
?>