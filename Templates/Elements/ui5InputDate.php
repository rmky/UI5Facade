<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\InputDate;

/**
 * 
 * @method InputDate getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5InputDate extends ui5Input
{
    
    public function buildJsConstructorForMainControl()
    {
        return <<<JS

        new sap.m.DatePicker("{$this->getId()}", {
            {$this->buildJsProperties()}
			valueFormat: "yyyy-MM-dd HH:mm:ss",
            displayFormat: ""
		}){$this->buildJsPseudoEventHandlers()}

JS;
    }
    
}
?>
