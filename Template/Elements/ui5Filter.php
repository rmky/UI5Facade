<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryFilterTrait;

/**
 * Generates OpenUI5 filters
 * 
 * @method ui5AbstractElement getInputElement()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Filter extends ui5AbstractElement
{
    use JqueryFilterTrait;
    
    public function generateJsConstructor()
    {
        return $this->getInputElement()->generateJsConstructor();
    }
}
?>