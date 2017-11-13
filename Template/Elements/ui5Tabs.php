<?php
namespace exface\OpenUI5Template\Template\Elements;

class ui5Tabs extends ui5Container
{

    protected function buildHtmlTabBodies()
    {
        $output = '';
        foreach ($this->getWidget()->getChildren() as $tab) {
            $output .= $this->getTemplate()->getElement($tab)->buildHtmlBody();
        }
        return $output;
    }

    protected function buildHtmlTabHeaders()
    {
        $output = '';
        
        return $output;
    }
}
?>
