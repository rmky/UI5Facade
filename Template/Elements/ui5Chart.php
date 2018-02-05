<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\ChartSeries;
use exface\Core\Widgets\ChartAxis;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryFlotTrait;
use exface\Core\Widgets\Chart;

/**
 * 
 * @method Chart getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5Chart extends ui5AbstractElement
{
    use JqueryFlotTrait {
        generateHeaders as generateHeadersByTrait;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor()
    {
        return <<<JS

        new sap.ui.core.HTML("{$this->getId()}", {
            content: "<div style=\"height: calc(100% - 10px); margin: 5px 0; overflow: hidden; position: relative;\"></div>",
            afterRendering: function() { {$this->buildJsRefresh()} }
        })

JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::generateJs()
     */
    public function generateJs()
    {
        $widget = $this->getWidget();
        
        $output = $this->buildJsPlotFunction();
        
        // Add JS code for the configurator
        $output .= $this->getTemplate()->getElement($widget->getConfiguratorWidget())->generateJs();
        // Add JS for all buttons
        $output .= $this->buildJsButtons();
        
        return $output;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsButtons()
    {
        $js = '';
        foreach ($this->getWidget()->getButtons() as $btn) {
            $js .= $this->getTemplate()->getElement($btn)->generateJs();
        }
        return $js;
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
    protected function buildJsAjaxLoaderFunction()
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
                $post_data .= 'data.order = [];' . "\n";
                foreach ($widget->getData()->getSorters() as $sorter) {
                    $post_data .= 'data.order.push({attribute_alias: "' . $sorter->getProperty('attribute_alias') . '", dir: "' . $sorter->getProperty('direction') . '"});';
                }
            }
            
            // send pagination/limit information. Charts currently do not support real pagination, but just a TOP-X display.
            if ($widget->getData()->getPaginate()) {
                $post_data .= 'data.start = 0;';
                $post_data .= 'data.length = ' . (! is_null($widget->getData()->getPaginatePageSize()) ? $widget->getData()->getPaginatePageSize() : $this->getTemplate()->getConfig()->getOption('WIDGET.CHART.PAGE_SIZE')) . ';';
            }
            
            // Loader function
            $output .= '
				function ' . $this->buildJsFunctionPrefix() . 'load(){
					' . $this->buildJsBusyIconShow() . '
					var data = { };
					' . $post_data . '
                    //data.data = ' . $this->getTemplate()->getElement($widget->getConfiguratorWidget())->buildJsDataGetter() . '
					$.ajax({
						url: "' . $this->getAjaxUrl() . '",
                        method: "POST",
						data: data,
						success: function(data){
							' . $this->buildJsFunctionPrefix() . 'plot($.parseJSON(data));
							' . $this->buildJsBusyIconHide() . '
						},
						error: function(jqXHR, textStatus, errorThrown){
							' . $this->buildJsShowError('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText') . '
							' . $this->buildJsBusyIconHide() . '
						}
					});
				}';
        }
        
        return $output;
    }
    
    public function generateHeaders()
    {
        $includes = $this->generateHeadersByTrait();
        
        $includes[] = '<script type="text/javascript" src="exface/vendor/exface/OpenUI5Template/Template/js/flot/plugins/axislabels/jquery.flot.axislabels.js"></script>';
        $includes[] = '<script type="text/javascript" src="exface/vendor/exface/OpenUI5Template/Template/js/flot/plugins/jquery.flot.orderBars.js"></script>';
        
        return $includes;
    }
    
}
?>
