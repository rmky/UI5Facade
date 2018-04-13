<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryContainerTrait;
use exface\Core\Widgets\Container;
use exface\Core\Interfaces\Widgets\iShowSingleAttribute;

/**
 * 
 * @method Container getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5Container extends ui5AbstractElement
{
    use JqueryContainerTrait;
    
    public function buildJsConstructor()
    {
        return $this->buildJsChildrenConstructors();
    }
    
    public function buildJsChildrenConstructors()
    {
        $js = '';
        foreach ($this->getWidget()->getWidgets() as $widget) {
            $js .= ($js ? ",\n" : '') . $this->getTemplate()->getElement($widget)->buildJsConstructor();
        }
        
        return $js;
    }
    
    public function buildJsDataSetter($jsInput) : string
    {
        $setters = '';
        foreach ($this->getWidget()->getWidgets() as $child) {
            if (! ($child instanceof iShowSingleAttribute) || ! $child->hasAttributeReference()) {
                continue;
            }
            $setters .= <<<JS

                if (row['{$child->getAttributeAlias()}']) {
                    {$this->getTemplate()->getElement($child)->buildJsValueSetter('row["' . $child->getAttributeAlias() . '"]')};
                }
JS;
        }
        return <<<JS

            var data = {$jsInput};
            var row = data.rows[0];
            console.log(row);
            if (! row || row.length === 0) {
                return;
            }
            {$setters}

JS;
    }
}
?>