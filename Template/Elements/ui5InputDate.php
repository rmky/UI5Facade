<?php
namespace exface\OpenUI5Template\Template\Elements;

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
    
    public function buildJsControlConstructor()
    {
        return <<<JS

        new sap.m.DatePicker("{$this->getId()}", {
            {$this->buildJsProperties()}
			valueFormat: "yyyy-MM-dd",
            displayFormat: "short"
		}){$this->buildJsPseudoEventHandlers()}

JS;
    }
    
}
?>
