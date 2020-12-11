<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JsonEditorTrait;

/**
 * 
 * @method \exface\Core\Widgets\InputJson getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5InputJson extends UI5InputText
{    
    /* TODO use the new JsonEditorTrait here
     * TODO add dependency to composer.json
    use JsonEditorTrait;
    
    
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        // TODO create own control instead of using the HTML control in order to be able to destroy the JSONeditor
        // properly. The way the whole thing works now, the JS variable {$this->getId()}_JSONeditor lives even
        // after the control or it's view had been destroyed.
        $styles = $this::buildCssModalStyles();
        return <<<JS

        new sap.ui.core.HTML("{$this->getId()}_wrapper", {
            content: "{$this->escapeJsTextValue($this->buildHtmlJsonEditor())}",
            afterRendering: function() { 
                {$this->buildJsJsonEditor('oController')} 
                if ($('#{$this->getId()}_styles').length === 0) {
                    $('head').append('<style id="{$this->getId()}_styles">{$styles}</style>');
                }
            }
        })

JS;
    }
    
    public function registerExternalModules(UI5ControllerInterface $controller) : UI5AbstractElement
    {
        $facade = $this->getFacade();
        
        $controller->addExternalCss($facade->buildUrlToSource('LIBS.JSONEDITOR.CSS'));
        $controller->addExternalModule('exface.openui5.jsoneditor', $facade->buildUrlToSource('LIBS.JSONEDITOR.JS'), 'JSONEditor');
        $controller->addExternalModule('exface.openui5.picomodal', $facade->buildUrlToSource('LIBS.JSONEDITOR.PICOMODAL'), 'picomodal');
        
        return $this;
    }
    */    
}
