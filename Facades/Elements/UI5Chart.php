<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\EChartsTrait;
use exface\Core\Widgets\Chart;
use exface\UI5Facade\Facades\Elements\Traits\UI5DataElementTrait;
use exface\Core\Widgets\Data;
use exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface;

/**
 * 
 * @method Chart getWidget()
 * @method UI5ControllerInterface getController()
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5Chart extends UI5AbstractElement
{
    use EChartsTrait;
    use UI5DataElementTrait {
        buildJsConfiguratorButtonConstructor as buildJsConfiguratorButtonConstructorViaTrait;
        buildJsDataLoaderOnLoaded as buildJsDataLoaderOnLoadedViaTrait;
        UI5DataElementTrait::buildJsRowCompare insteadof EChartsTrait;
        EChartsTrait::buildJsDataResetter insteadof UI5DataElementTrait;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructorForControl($oControllerJs = 'oController') : string
    {
        $this->getFacade()->getElement($this->getWidget()->getConfiguratorWidget())->registerFiltersWithApplyOnChange($this);
        
        $controller = $this->getController();        
        $controller->addMethod($this->buildJsDataLoadFunctionName(), $this, '', $this->buildJsDataLoadFunctionBody());
        $controller->addMethod($this->buildJsRedrawFunctionName(), $this, 'oData', $this->buildJsRedrawFunctionBody('oData'));
        $controller->addMethod($this->buildJsSelectFunctionName(), $this, 'oSelection', $this->buildJsSelectFunctionBody('oSelection') . $this->getController()->buildJsEventHandler($this, self::EVENT_NAME_CHANGE, false));
        $controller->addMethod($this->buildJsClicksFunctionName(), $this, 'oParams', $this->buildJsClicksFunctionBody('oParams'));
        $controller->addMethod($this->buildJsSingleClickFunctionName(), $this, 'oParams', $this->buildJsSingleClickFunctionBody('oParams') . $this->getController()->buildJsEventHandler($this, self::EVENT_NAME_CHANGE, false));
        
        $this->registerExternalModules($controller);
        
        $chart = <<<JS

                new sap.ui.core.HTML("{$this->getId()}", {
                    content: "<div id=\"{$this->getId()}_echarts\" style=\"height:100%; min-height: 100px; overflow: hidden;\"></div>",
                    afterRendering: function(oEvent) { 
                        {$this->buildJsEChartsInit($this->getFacade()->getConfig()->getOption('LIBS.ECHARTS.THEME_NAME'))}
                        {$this->buildJsEventHandlers()}
                        
                        setTimeout(function(){
                            {$this->buildJsEChartsResize()}
                        }, 0);
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
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::registerExternalModules()
     */
    public function registerExternalModules(UI5ControllerInterface $controller) : UI5AbstractElement
    {
        $f = $this->getFacade();
        $controller->addExternalModule('libs.exface.charts.ECharts', $f->buildUrlToSource('LIBS.ECHARTS.ECHARTS_JS'), null, 'echarts');
        $controller->addExternalModule('libs.exface.charts.Theme', $f->buildUrlToSource('LIBS.ECHARTS.THEME_JS'), null);        
        
        foreach ($this->getWidget()->getData()->getColumns() as $col) {
            $f->getElement($col)->registerExternalModules($controller);
        }
        return $this;
    }
        
    /**
     * 
     * @return array
     */
    protected function getJsIncludes() : array
    {
        $htmlTagsArray = $this->buildHtmlHeadDefaultIncludes();
        $htmlTagsArray[] = '<script type="text/javascript" src="' . $this->getFacade()->buildUrlToSource('LIBS.ECHARTS.THEME_JS') . '"></script>';
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
    protected function buildJsBindToClickHandler($oControllerJsVar = 'oController') : string
    {        
        $widget = $this->getWidget();
        $output = '';
        
        if ($this->isGraphChart() === true) {
            // click actions for graph charts
            // for now you can only call an action when clicking on a node
            if ($dblclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_DOUBLE_CLICK)[0]) {
                $output .= <<<JS
                
            {$this->buildJsEChartsVar()}.on('dblclick', function(params){
                if (params.dataType === 'node') {
                    {$this->buildJsEChartsVar()}._oldSelection = {$this->buildJsGetSelectedRowFunction('params.data')};
                    {$this->getFacade()->getElement($dblclick_button)->buildJsClickEventHandlerCall($oControllerJsVar)}
                }
            });
            
JS;
                    
            }
            /*if ($dblclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_DOUBLE_CLICK)[1]) {
             $output .= <<<JS
             
             {$this->buildJsEChartsVar()}.on('dblclick', {dataType: 'edge'}, function(params){
             {$this->buildJsEChartsVar()}._oldSelection = params.data
             {$this->getFacade()->getElement($dblclick_button)->buildJsClickEventHandlerCall($oControllerJsVar)}
             });
             
             JS;
             
             }*/
            
            if ($rightclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_RIGHT_CLICK)[0]) {
                $output .= <<<JS
                
            {$this->buildJsEChartsVar()}.on('contextmenu', function(params){
                if (params.dataType === 'node') {
                    {$this->buildJsEChartsVar()}._oldSelection = {$this->buildJsGetSelectedRowFunction('params.data')};
                    {$this->getFacade()->getElement($rightclick_button)->buildJsClickEventHandlerCall($oControllerJsVar)}
                    params.event.event.preventDefault();
                }
            });
            
JS;
            }
            
            if ($leftclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_LEFT_CLICK)[0]) {
                $output .= <<<JS
                
            {$this->buildJsEChartsVar()}.on('click', function(params){
                if (params.dataType === 'node') {
                    {$this->buildJsEChartsVar()}._oldSelection = {$this->buildJsGetSelectedRowFunction('params.data')};
                    {$this->getFacade()->getElement($leftclick_button)->buildJsClickEventHandlerCall($oControllerJsVar)}
                }
            });
            
JS;
            }
            
        } else {
            
            // Double click actions for not graph charts
            // Currently only supports one double click action - the first one in the list of buttons
            if ($dblclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_DOUBLE_CLICK)[0]) {
                $output .= <<<JS
                
                {$this->buildJsEChartsVar()}.on('dblclick', function(params){
                    {$this->buildJsEChartsVar()}._oldSelection = {$this->buildJsGetSelectedRowFunction('params.data')};
                    {$this->getFacade()->getElement($dblclick_button)->buildJsClickEventHandlerCall($oControllerJsVar)}
                });
                
JS;
                    
            }
            
            if ($leftclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_LEFT_CLICK)[0]) {
                $output .= <<<JS
                
                {$this->buildJsEChartsVar()}.on('click', function(params){
                    {$this->buildJsEChartsVar()}._oldSelection = {$this->buildJsGetSelectedRowFunction('params.data')};
                    {$this->getFacade()->getElement($leftclick_button)->buildJsClickEventHandlerCall($oControllerJsVar)}
                });
                
JS;
                    
            }
            
            if ($rightclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_RIGHT_CLICK)[0]) {
                $output .= <<<JS
                
                {$this->buildJsEChartsVar()}.on('contextmenu', function(params){
                    {$this->buildJsEChartsVar()}._oldSelection = {$this->buildJsGetSelectedRowFunction('params.data')};
                    {$this->getFacade()->getElement($rightclick_button)->buildJsClickEventHandlerCall($oControllerJsVar)}
                    params.event.event.preventDefault();
                });
                
JS;
                    
            }
        }
        return $output;    
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
        return $this->buildJsDataLoaderOnLoadedViaTrait($oModelJs) . $this->buildJsRedraw($oModelJs . '.getData().rows');
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsQuickSearchConstructor() : string
    {
        return <<<JS

                    new sap.m.OverflowToolbarButton({
                        icon: "sap-icon://refresh",
                        press: {$this->getController()->buildJsMethodCallFromView('onLoadData', $this)}
                    })

JS;
    }
    
    /**
     * 
     * @see UI5DataElementTrait
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
    
    /**
     * 
     * {@inheritdoc}
     * @see UI5DataElementTrait::buildJsBusyCheck()
     */
    public function buildJsBusyCheck() : string
    {
        return 'sap.ui.getCore().byId("' . $this->getId() . '").getParent().getBusy()';
    }
    
    /**
     * {@inheritdoc}
     * @see \exface\UI5Facade\Facades\Elements\Traits\UI5DataElementTrait::buildJsShowMessageOverlay()
     */
    protected function buildJsShowMessageOverlay(string $message) : string
    {
        return $this->buildJsDataResetter() . ';' . $this->buildJsMessageOverlayShow($message);
    }
    
    protected function isEditable()
    {
        return false;
    }
}
