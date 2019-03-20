<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryFlotTrait;
use exface\Core\Widgets\Chart;
use exface\Core\DataTypes\StringDataType;
use exface\OpenUI5Template\Templates\Elements\Traits\ui5DataElementTrait;
use exface\Core\Widgets\Data;

/**
 * 
 * @method Chart getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5Chart extends ui5AbstractElement
{
    use JqueryFlotTrait;
    use ui5DataElementTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructorForControl($oControllerJs = 'oController') : string
    {
        $controller = $this->getController();
        $controller->addMethod('onPlot', $this, 'data', $this->buildJsPlotter());
        
        foreach ($this->getJsIncludes() as $path) {
            $controller->addExternalModule(StringDataType::substringBefore($path, '.js'), $path, null, $path);
        }
        
        $chart = <<<JS

                new sap.ui.core.HTML("{$this->getId()}", {
                    content: "<div class=\"exf-flot-wrapper\" style=\"height: 100%; overflow: hidden; position: relative;\"></div>",
                    afterRendering: function() { 
                        {$this->buildJsRefresh()} 
                    }
                })

JS;
                        
        return $this->buildJsPanelWrapper($chart, $oControllerJs);
    }
        
    protected function getJsIncludes() : array
    {
        $tags = implode('', $this->buildHtmlHeadDefaultIncludes());
        $jsTags = [];
        preg_match_all('#<script[^>]*src="([^"]*)"[^>]*></script>#is', $tags, $jsTags);
        return $jsTags[1];
    }
        
    public function buildJsRefresh()
    {
        return $this->getController()->buildJsMethodCallFromController('onLoadData', $this, '');
    }
    
    protected function buildJsRedraw(string $dataJs) : string
    {
        return $this->getController()->buildJsMethodCallFromController('onPlot', $this, $dataJs);
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsDataRowsSelector()
    {
        return '.data';
    }

    /**
     * Returns the definition of the function elementId_load(urlParams) which is used to fethc the data via AJAX
     * if the chart is not bound to another data widget (in that case, the data should be provided by that widget).
     *
     * @return string
     */
    protected function buildJsDataLoader()
    {
        $widget = $this->getWidget();
        $output = '';
        if (! $widget->getDataWidgetLink()) {
            
            $post_data = '
                            data.resource = "' . $widget->getPage()->getAliasWithNamespace() . '";
                            data.element = "' . $widget->getData()->getId() . '";
                            data.object = "' . $widget->getMetaObject()->getId() . '";
                            data.action = "' . $widget->getLazyLoadingActionAlias() . '";
            ';
            
            // send sort information
            if (count($widget->getData()->getSorters()) > 0) {
                foreach ($widget->getData()->getSorters() as $sorter) {
                    $sort .= ',' . urlencode($sorter->getProperty('attribute_alias'));
                    $order .= ',' . urldecode($sorter->getProperty('direction'));
                }
                $post_data .= '
                            data.sort = "' . substr($sort, 1) . '";
                            data.order = "' . substr($order, 1) . '";';
            }
            
            // send pagination/limit information. Charts currently do not support real pagination, but just a TOP-X display.
            if ($widget->getData()->isPaged()) {
                $post_data .= 'data.start = 0;';
                $post_data .= 'data.length = ' . $widget->getData()->getPaginator()->getPageSize($this->getTemplate()->getConfig()->getOption('WIDGET.CHART.PAGE_SIZE')) . ';';
            }
            
            // Loader function
            $output .= '
					' . $this->buildJsBusyIconShow() . '
					var data = { };
					' . $post_data . '
                    /*data.data = ' . $this->getTemplate()->getElement($widget->getConfiguratorWidget())->buildJsDataGetter() . ';*/
					$.ajax({
						url: "' . $this->getAjaxUrl() . '",
                        method: "POST",
						data: data,
						success: function(data){
							' . $this->buildJsRedraw('data') . ';
							' . $this->buildJsBusyIconHide() . ';
						},
						error: function(jqXHR, textStatus, errorThrown){
							' . $this->buildJsShowError('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText') . '
							' . $this->buildJsBusyIconHide() . '
						}
					});
				';
        }
        
        return $output;
    }
    
    protected function hasActionButtons() : bool
    {
        return false;
    }
    
    protected function buildJsConfiguratorButtonConstructor(string $oControllerJs = 'oController') : string
    {
        return <<<JS
        
                    new sap.m.OverflowToolbarButton({
                        icon: "sap-icon://refresh",
                        press: {$this->getController()->buildJsMethodCallFromView('onLoadData', $this)}
                    }),new sap.m.OverflowToolbarButton({
                        icon: "sap-icon://drop-down-list",
                        enabled: false
                    }),
                        
JS;
    }
        
    protected function buildJsDataResetter() : string
    {
        // TODO
        return '';
    }
    
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
}