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
            $this->getController()->addOnInitScript($js);
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
        
        // Extract <script></script>
        foreach ($this->getTagsFromHtml($html, 'script') as $tag => $script) {
            $scripts .= $script;
            $html = str_replace($tag, '', $html);
        }
        
        // Extract <style></style>
        foreach ($this->getTagsFromHtml($html, 'style') as $tag => $style) {
            $styles .= str_replace("\n", "\\n", $style);
            $html = str_replace($tag, '', $html);
        }
        
        if ($this->isValueBoundToModel()) {
            $content = '{' . $this->getValueBindingPath() . '}';
        } else {
            $content = $this->escapeJsTextValue($html);
        }
        
        return <<<JS
        new sap.ui.core.HTML("{$this->getId()}", {
            content: "<div class=\"exf-html\">{$content}</div>",
            afterRendering: function() {
                {$scripts}
                if ($('#{$this->getId()}_styles').length === 0) {
                    $('head').append('<style id="{$this->getId()}_styles">{$styles}</style>');
                }
            }
        })
JS;
    }
                
    protected function escapeJsTextValue($text)
    {
        $text = parent::escapeJsTextValue($text);
        $text = str_replace(['{', '}'], ['&#123;', '&#125;'], $text);
        return $text;
    }
    
    protected function getTagsFromHtml($html, $tag) : array
    {
        $tags = [];
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
        preg_match_all("/<{$tag}.*?>(.*?)<\/{$tag}>/si", $html, $tags);
        return array_combine($tags[0], $tags[1]);
    }
    
    public function buildCssInlineStyles() : string
    {
        return $this->getWidget()->getCss();
    }
}
?>