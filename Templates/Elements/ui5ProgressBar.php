<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\ProgressBar;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\HtmlProgressBarTrait;

/**
 *
 * @method ProgressBar getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class ui5ProgressBar extends ui5Display
{
    use HtmlProgressBarTrait;
}
?>