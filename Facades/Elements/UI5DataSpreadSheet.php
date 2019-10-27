<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\DataTypes\StringDataType;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JExcelTrait;
use exface\UI5Facade\Facades\Elements\Traits\UI5DataElementTrait;

class UI5DataSpreadSheet extends UI5AbstractElement
{    
    use JExcelTrait;
    use UI5DataElementTrait;
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructorForControl($oControllerJs = 'oController') : string
    {
        $this->getFacade()->getElement($this->getWidget()->getConfiguratorWidget())->registerFiltersWithApplyOnChange($this);
        
        $this->registerReferencesAtLinkedElements();
        
        $controller = $this->getController();
        $controller->addOnDefineScript($this->buildJsFixJqueryImportUseStrict());
        
        $controller->addMethod('onFixedFooterSpread', $this, '', $this->buildJsFixedFootersSpreadFunctionBody());
        
        $controller->addExternalModule('exface.openui5.jexcel', $this->getFacade()->buildUrlToSource("LIBS.JEXCEL.JS"), 'jexcel');
        $controller->addExternalCss($this->getFacade()->buildUrlToSource('LIBS.JEXCEL.CSS'));
        $controller->addExternalModule('exface.openui5.jsuites', $this->getFacade()->buildUrlToSource("LIBS.JEXCEL.JS_JSUITES"), 'jsuites');
        $controller->addExternalCss($this->getFacade()->buildUrlToSource('LIBS.JEXCEL.CSS_JSUITES'));
        
        $chart = <<<JS
        
                new sap.ui.core.HTML("{$this->getId()}", {
                    content: "<div id=\"{$this->getId()}_jexcel\" class=\"{$this->buildCssElementClass()}\"></div>",
                    afterRendering: function(oEvent) {
                        {$this->buildJsDestroy()}
                        {$this->buildJsJExcelInit()}
                        {$this->buildJsRefresh()}
                        {$this->buildJsFixOverflowVisibility()}
                    }
                })
                
JS;
                            
        return $this->buildJsPanelWrapper($chart, $oControllerJs);
    }
    
    protected function buildJsFixOverflowVisibility() : string
    {
        return <<<JS

                        var aParents = {$this->buildJsJqueryElement()}.parents();
                        for (var i = 0; i < aParents.length; i++) {
                            var jqParent = $(aParents[i]);
                            if (jqParent.hasClass('sapUiRespGrid ') === true) {
                                break;
                            }
                            $(jqParent).css('overflow', 'visible');
                        }

JS;
    }
    
    /**
     * @see JExcelTrait::buildJsJqueryElement()
     */
    protected function buildJsJqueryElement() : string
    {
        return "$('#{$this->getId()}_jexcel')";
    }
    
    /**
     *
     * {@inheritdoc}
     * @see UI5DataElementTrait::buildJsDataLoaderOnLoaded()
     */
    protected function buildJsDataLoaderOnLoaded(string $oModelJs = 'oModel') : string
    {
        return <<<JS

        {$this->buildJsDataSetter('oModel.getData()')}
        {$this->buildJsFooterRefresh('data', 'jqSelf')}

JS;
    }
    
    protected function buildJsFixedFootersSpread() : string
    {
        return $this->getController()->buildJsMethodCallFromController('onFixedFooterSpread', $this, '');
    }
    
    /**
     *
     * @return array
     */
    protected function getJsIncludes() : array
    {
        $htmlTagsArray = $this->buildHtmlHeadTagsForJExcel();
        $tags = implode('', $htmlTagsArray);
        $jsTags = [];
        preg_match_all('#<script[^>]*src="([^"]*)"[^>]*></script>#is', $tags, $jsTags);
        return $jsTags[1];
    }
}