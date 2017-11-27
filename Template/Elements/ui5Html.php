<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\Html;

/**
 * Generates OpenUI5 HTML
 * 
 * @method Html getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Html extends ui5Text
{
    function generateJs()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor()
    {
        return $this->buildJsLabelWrapper($this->buildJsElementConstructor());
    }
    
    /**
     * Returns the constructor of the text/input element without the label
     * @return string
     */
    protected function buildJsElementConstructor()
    {
        $widget = $this->getWidget();
        $html = $widget->getHtml();
        $content = $this->escapeLinebreaks($this->buildJsTextValue($html));
        return <<<JS
        new sap.ui.core.HTML("{$this->getId()}", {
            content: "{$content}"
        })
JS;
    }
        
    protected function escapeLinebreaks($text)
    {
        return str_replace("\n","\\n", $text);
    }
    
    public function generateHeaders()
    {
        $widget = $this->getWidget();
        return $widget->getCss() ? '<style>' . $widget->getCss() . '</style>' : '';
    }
}
?>