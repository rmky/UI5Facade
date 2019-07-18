<?php
namespace exface\UI5Facade\Facades\Elements\ServerAdapters;

use exface\UI5Facade\Facades\Elements\UI5AbstractElement;
use exface\UI5Facade\Facades\Interfaces\UI5ServerAdapterInterface;
use exface\UrlDataConnector\DataConnectors\OData2Connector;
use exface\Core\Exceptions\Facades\FacadeLogicError;
use exface\Core\Interfaces\Model\MetaObjectInterface;

class OData2ServerAdapter implements UI5ServerAdapterInterface
{
    private $element = null;
    
    public function __construct(UI5AbstractElement $element)
    {
        $this->element = $element;    
    }
    
    public function getElement() : UI5AbstractElement
    {
        return $this->element;
    }
    
    public function buildJsDataLoader(string $oModelJs, string $oParamsJs, string $onModelLoadedJs, string $onOfflineJs = '') : string
    {
        $widget = $this->getElement()->getWidget();
        $object = $widget->getMetaObject();
        
        return <<<JS
                
                var oDataModel = new sap.ui.model.odata.v2.ODataModel({$this->getODataModelParams($object)});  
                oDataModel.read('/salesOrderHeadSet', {
                    success: function(oData, response) {
                        var oRowData = {
                            data: oData.results
                        };
                        oModel.setData(oRowData);
                        {$onModelLoadedJs}
                        {$this->getElement()->buildJsBusyIconHide()}
                    },
                    error: function(oError) {
                        console.log(oError);
                    }
                });
                
JS;
    }
           
    protected function getODataModelParams(MetaObjectInterface $object) : string
    {
        $connection = $object->getDataConnection();
        
        if (! $connection instanceof OData2Connector) {
            throw new FacadeLogicError('Cannot use direct OData 2 connections with object "' . $object->getName() . '" (' . $object->getAliasWithNamespace() . ')!');
        }
        
        $params = [];
        $params['serviceUrl'] = rtrim($connection->getUrl(), "/") . '/';
        if ($connection->getUser()) {
            $params['user'] = $connection->getUser();
            $params['password'] = $connection->getPassword();
            $params['withCredentials'] = true;
            //$params['headers'] = ['Authorization' => 'Basic TU9WX0RFVjpzY2h1ZXJlcjVh'];
        }
        if ($fixedParams = $connection->getFixedUrlParams()) {     
            $fixedParamsArr = [];
            parse_str($fixedParams, $fixedParamsArr);
            $params['serviceUrlParams'] = array_merge($params['serviceUrlParams'] ?? [], $fixedParamsArr);
            $params['metadataUrlParams'] = array_merge($params['metadataUrlParams'] ?? [], $fixedParamsArr);
        }
        
        return json_encode($params);
    }
}