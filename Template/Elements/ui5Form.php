<?php
namespace exface\OpenUI5Template\Template\Elements;

/**
 * Generates OpenUI5 inputs
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Form extends ui5Panel
{
    
    protected function buildJsPropertyEditable()
    {
        return 'editable: true,';
    }
    
    public function buildJsConstructor()
    {
        return  $this->buildJsLayoutForm($this->buildJsChildrenConstructors());
    }
    
}
?>