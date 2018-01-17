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
    public function generateJs()
    {
        return $this->getWidget()->getJavascript();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor()
    {
        return $this->buildJsLabelWrapper($this->buildJsControlConstructor());
    }
    
    /**
     * Returns the constructor of the text/input element without the label
     * @return string
     */
    protected function buildJsControlConstructor()
    {
        $widget = $this->getWidget();
        $html = $widget->getHtml();
        foreach ($this->getScriptTagsFromHtml($html) as $tag => $script) {
            $scripts .= $script;
            $html = str_replace($tag, '', $html);
        }
        $content = $this->escapeLinebreaks($this->escapeJsTextValue($html));
        return <<<JS
        new sap.ui.core.HTML("{$this->getId()}", {
            content: "{$content}",
            afterRendering: function() {
                console.log('init'); 
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
        
    protected function escapeLinebreaks($text)
    {
        return str_replace("\n","\\n", $text);
    }
    
    public function generateHeaders()
    {
        $widget = $this->getWidget();
        $headers = [];
        $headers[] = $widget->getHeadTags();
        if ($widget->getCss()) {
            $headers[] = '<style>' . $widget->getCss() . '</style>';
        }/*
        foreach ($this->getScriptTagsFromHtml($widget->getHtml()) as $script){
            $headers[] = $script;
        }*/
        return $headers;
    }
}
?>