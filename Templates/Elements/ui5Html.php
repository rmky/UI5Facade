<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\Html;
use exface\Core\Exceptions\RuntimeException;

/**
 * Generates OpenUI5 HTML
 * 
 * @method Html getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Html extends ui5Value
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        if ($js = $this->getWidget()->getJavascript()) {
            $this->addOnInitScript($js);
        }
        return $this->buildJsLabelWrapper($this->buildJsConstructorForMainControl($oControllerJs));
    }
    
    /**
     * Returns the constructor of the text/input element without the label
     * @return string
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        $widget = $this->getWidget();
        $html = $widget->getHtml();
        foreach ($this->getScriptTagsFromHtml($html) as $tag => $script) {
            $scripts .= $script;
            $html = str_replace($tag, '', $html);
        }
        $content = $this->escapeJsTextValue($html);
        return <<<JS
        new sap.ui.core.HTML("{$this->getId()}", {
            content: "<div class=\"exf-html\">{$content}</div>",
            afterRendering: function() {
                {$scripts}
            }
        })
JS;
    }
        
    protected function getScriptTagsFromHtml($html)
    {
        $script_tags = [];
        // Fetch all <script> tags into a multidimensional array:
        // [
        //  0 => [
        //      0 => <script> first script </script>
        //      1 => <script> second script </script>
        //      ...
        //  ],
        //  1 => [
        //      0 => first script
        //      1 => second script
        //      ...
        //  ]
        preg_match_all("/<script.*?>(.*?)<\/script>/si", $html, $script_tags);
        return array_combine($script_tags[0], $script_tags[1]);
    }
    
    public function buildCssInlineStyles() : string
    {
        return $this->getWidget()->getCss();
    }
}
?>