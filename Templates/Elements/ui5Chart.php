<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryFlotTrait;
use exface\Core\Widgets\Chart;
use exface\Core\DataTypes\StringDataType;

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
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $controller = $this->getController();
        $controller->addMethod('onPlot', $this, 'data', $this->buildJsPlotter());
        $controller->addMethod('onLoadData', $this, '', $this->buildJsDataLoader());
        
        foreach ($this->getJsIncludes() as $path) {
            $controller->addExternalModule(StringDataType::substringBefore($path, '.js'), $path, null, $path);
        }
        
        return <<<JS

        new sap.ui.core.HTML("{$this->getId()}", {
            content: "<div style=\"height: calc(100% - 10px); margin: 5px 0; overflow: hidden; position: relative;\"></div>",
            afterRendering: function() { {$this->buildJsRefresh()} }
        })

JS;
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
                $post_data .= 'data.order = [];' . "\n";
                foreach ($widget->getData()->getSorters() as $sorter) {
                    $post_data .= 'data.order.push({attribute_alias: "' . $sorter->getProperty('attribute_alias') . '", dir: "' . $sorter->getProperty('direction') . '"});';
                }
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
}
?>
