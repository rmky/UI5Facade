<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\EChartsTrait;
use exface\Core\Widgets\Chart;
use exface\Core\DataTypes\StringDataType;
use exface\UI5Facade\Facades\Elements\Traits\UI5DataElementTrait;
use exface\Core\Widgets\Data;
use exface\Core\Widgets\DataButton;
use exface\Core\Widgets\Button;
use exface\Core\Widgets\ButtonGroup;
use exface\Core\Widgets\MenuButton;

/**
 * 
 * @method Chart getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5Chart extends UI5AbstractElement
{
    use EChartsTrait;
    use ui5DataElementTrait {
        buildJsConfiguratorButtonConstructor as buildJsConfiguratorButtonConstructorViaTrait;
        buildJsDataLoaderOnLoaded as buildJsDataLoaderOnLoadedViaTrait;
        ui5DataElementTrait::buildJsRowCompare insteadof EChartsTrait; 
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructorForControl($oControllerJs = 'oController') : string
    {
        // TODO #chart-configurator Since there is no extra chart configurator yet, we use the configurator
        // of the data widget and make it refresh this chart when it's apply-on-change-filters change. 
        $this->getFacade()->getElement($this->getWidget()->getData()->getConfiguratorWidget())->registerFiltersWithApplyOnChange($this);
        
        $controller = $this->getController();        
        $controller->addMethod($this->buildJsDataLoadFunctionName(), $this, '', $this->buildJsDataLoadFunctionBody());
        $controller->addMethod($this->buildJsRedrawFunctionName(), $this, 'oData', $this->buildJsRedrawFunctionBody('oData'));
        $controller->addMethod($this->buildJsSelectFunctionName(), $this, 'oSelection', $this->buildJsSelectFunctionBody('oSelection') . $this->getController()->buildJsEventHandler($this, 'change', false));
        $controller->addMethod($this->buildJsClicksFunctionName(), $this, 'oParams', $this->buildJsClicksFunctionBody('oParams'));
        $controller->addMethod($this->buildJsSingleClickFunctionName(), $this, 'oParams', $this->buildJsSingleClickFunctionBody('oParams') . $this->getController()->buildJsEventHandler($this, 'change', false));
        
        foreach ($this->getJsIncludes() as $path) {
            $controller->addExternalModule(StringDataType::substringBefore($path, '.js'), $path, null, $path);
        }
        
        $chart = <<<JS

                new sap.ui.core.HTML("{$this->getId()}", {
                    content: "<div id=\"{$this->getId()}_echarts\" style=\"height:100%; min-height: 100px; overflow: hidden;\"></div>",
                    afterRendering: function(oEvent) { 
                        {$this->buildJsEChartsInit('ui5theme')}
                        {$this->buildJsEventHandlers()}

                        sap.ui.core.ResizeHandler.register(sap.ui.getCore().byId('{$this->getId()}').getParent(), function(){
                            {$this->buildJsEChartsResize()}
                        });
                    }
                })

JS;
                        
        return $this->buildJsPanelWrapper($chart, $oControllerJs);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see exface\Core\Facades\AbstractAjaxFacade\Elements\EChartsTrait::buildJsEChartsInit()
     */
    public function buildJsEChartsInit(string $theme) : string
    {
        return <<<JS
        
    echarts.init(document.getElementById('{$this->getId()}_echarts'), '{$theme}');
    
JS;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see exface\Core\Facades\AbstractAjaxFacade\Elements\EChartsTrait::buildJsEChartsVar()
     */
    protected function buildJsEChartsVar() : string
    {
        
        return "echarts.getInstanceByDom(document.getElementById('{$this->getId()}_echarts'))";
        //return "document.getElementById('{$this->getId()}_echarts')._echarts_instance_";
    }
        
    /**
     * 
     * @return array
     */
    protected function getJsIncludes() : array
    {
        $htmlTagsArray = $this->buildHtmlHeadDefaultIncludes();
        //TODO ui5theme nicht im Paket enthalten, Datei wurde manuell erzeugt und in lokalem Ordner abgelegt!
        $htmlTagsArray[] = '<script type="text/javascript" src="exface/vendor/npm-asset/echarts/theme/ui5theme.js"></script>';
        $tags = implode('', $htmlTagsArray);
        $jsTags = [];
        preg_match_all('#<script[^>]*src="([^"]*)"[^>]*></script>#is', $tags, $jsTags);
        return $jsTags[1];
    }
        
    /**
     *
     * {@inheritDoc}
     * @see exface\Core\Facades\AbstractAjaxFacade\Elements\EChartsTrait::buildJsRefresh()
     */
    public function buildJsRefresh() : string
    {
        return $this->getController()->buildJsMethodCallFromController($this->buildJsDataLoadFunctionName(), $this, '');
    }
    
    /**
     *
     * {@inheritDoc}
     * @see exface\Core\Facades\AbstractAjaxFacade\Elements\EChartsTrait::buildJsRedraw()
     */
    protected function buildJsRedraw(string $oDataJs) : string
    {
        return $this->getController()->buildJsMethodCallFromController($this->buildJsRedrawFunctionName(), $this, $oDataJs);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see exface\Core\Facades\AbstractAjaxFacade\Elements\EChartsTrait::buildJsSelect()
     */
    protected function buildJsSelect(string $oRowJs = '') : string
    {
        return $this->getController()->buildJsMethodCallFromController($this->buildJsSelectFunctionName(), $this, $oRowJs);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see exface\Core\Facades\AbstractAjaxFacade\Elements\EChartsTrait::buildJsClicks()
     */
    protected function buildJsClicks(string $oParams = '') : string
    {
        return $this->getController()->buildJsMethodCallFromController($this->buildJsClicksFunctionName(), $this, $oParams);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see exface\Core\Facades\AbstractAjaxFacade\Elements\EChartsTrait::buildJsSingleClick()
     */
    protected function buildJsSingleClick(string $oParams = '') : string
    {
        return $this->getController()->buildJsMethodCallFromController($this->buildJsSingleClickFunctionName(), $this, $oParams);
    }
    
    /**
     * function to handle a double click on a chart, when a button is bound to double click
     * 
     * @param string $oControllerJsVar
     * @return string
     */
    protected function buildJsOnDoubleClickHandler($oControllerJsVar = 'oController') : string
    {
        $widget = $this->getWidget();        
        $js = '';        
        // Double click. Currently only supports one double click action - the first one in the list of buttons
        if ($dblclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_DOUBLE_CLICK)[0]) {
            $js .= <<<JS
            
            {$this->buildJsEChartsVar()}.on('dblclick', function(params){
                {$this->buildJsEChartsVar()}._oldSelection =  params.data
                {$this->getFacade()->getElement($dblclick_button)->buildJsClickEventHandlerCall($oControllerJsVar)};
            })

JS;
        }        
        return $js;
    }
    
    
    
    /**
     * 
     * @return string
     */
    protected function buildJsDataRowsSelector() : string
    {
        return '.data';
    }
    
    /**
     * 
     * {@inheritdoc}
     * @see EChartsTrait::buildJsDataLoadFunctionBody()
     */
    protected function buildJsDataLoadFunctionBody() : string
    {
        // Use the data loader of the UI5DataElementTrait
        return $this->buildJsDataLoader();
    }

    /**
     * 
     * {@inheritdoc}
     * @see UI5DataElementTrait::buildJsDataLoaderOnLoaded()
     */
    protected function buildJsDataLoaderOnLoaded(string $oModelJs = 'oModel') : string
    {
        return $this->buildJsDataLoaderOnLoadedViaTrait($oModelJs) . $this->buildJsRedraw($oModelJs . '.getData().data');
    }

    /**
     * 
     * @param string $oControllerJs
     * @return string
     */
    protected function buildJsConfiguratorButtonConstructor(string $oControllerJs = 'oController') : string
    {
        return <<<JS
        
                    new sap.m.OverflowToolbarButton({
                        icon: "sap-icon://refresh",
                        press: {$this->getController()->buildJsMethodCallFromView('onLoadData', $this)}
                    }),
                    {$this->buildJsConfiguratorButtonConstructorViaTrait($oControllerJs)}
                        
JS;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsQuickSearchConstructor() : string
    {
        return '';
    }
    
    /**
     * 
     * @see ui5DataElementTrait
     */
    protected function getDataWidget() : Data
    {
        return $this->getWidget()->getData();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsBusyIconShow()
     */
    public function buildJsBusyIconShow($global = false) : string
    {
        if ($global) {
            return parent::buildJsBusyIconShow($global);
        } else {
            return 'sap.ui.getCore().byId("' . $this->getId() . '").getParent().setBusyIndicatorDelay(0).setBusy(true);';
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsBusyIconHide()
     */
    public function buildJsBusyIconHide($global = false) : string
    {
        if ($global) {
            return parent::buildJsBusyIconShow($global);
        } else {
            return 'sap.ui.getCore().byId("' . $this->getId() . '").getParent().setBusy(false);';
        }
    }
}