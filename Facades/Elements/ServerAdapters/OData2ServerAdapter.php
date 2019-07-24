<?php
namespace exface\UI5Facade\Facades\Elements\ServerAdapters;

use exface\UI5Facade\Facades\Elements\UI5AbstractElement;
use exface\UI5Facade\Facades\Interfaces\UI5ServerAdapterInterface;
use exface\UrlDataConnector\DataConnectors\OData2Connector;
use exface\Core\Exceptions\Facades\FacadeLogicError;
use exface\Core\Interfaces\Model\MetaObjectInterface;
use exface\Core\Interfaces\Model\MetaAttributeInterface;
use exface\Core\DataTypes\BooleanDataType;

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
        
        $localFilterAliases = [];
        foreach ($object->getAttributes()->getAll() as $attr) {
            if ($this->needsLocalFiltering($attr)) {
                $localFilterAliases[] = $attr->getAlias();
            }
        }
        $localFilters = json_encode($localFilterAliases);
        
        return <<<JS

                console.log({$oParamsJs});
                
                var oDataModel = new sap.ui.model.odata.v2.ODataModel({$this->getODataModelParams($object)});  
                var oDataReadParams = {};
                var oDataReadFilters = [];
                
                // Pagination
                if ({$oParamsJs}.length) {
                    oDataReadParams.\$top = {$oParamsJs}.length;
                    if ({$oParamsJs}.start) {
                        oDataReadParams.\$skip = {$oParamsJs}.start;        
                    }
                }

                // Filters
                if ({$oParamsJs}.data && {$oParamsJs}.data.filters && {$oParamsJs}.data.filters.conditions) {
                    var conditions = {$oParamsJs}.data.filters.conditions;               
                    for (var i = 0; i < conditions.length; i++) {
                        switch (conditions[i].comparator) {
                            case '=':
                                var oOperator = "Contains";
                                break;
                            case '!=':
                                var oOperator = "NotContains";
                                break;
                            case '==':
                                var oOperator = "EQ";
                                break;                            
                            case '!==':
                                var oOperator = "NE";
                                break;
                            case '<':
                                var oOperator = "LT";
                                break;
                            case '<=':
                                var oOperator = "LE";
                                break;
                            case '>':
                                var oOperator = "GT";
                                break;
                            case '>=':
                                var oOperator ="GE";
                                break;
                            default:
                                var oOperator = "EQ";
                        }
                        if (conditions[i].value !== "") {
                            var filter = new sap.ui.model.Filter({
                                path: conditions[i].expression,
                                operator: oOperator,
                                value1: conditions[i].value
                            });
                            oDataReadFilters.push(filter);
                        }
                                                
                    }
                }
                console.log({$localFilters});               

                oDataModel.read('/{$object->getDataAddress()}', {
                    urlParameters: oDataReadParams,
                    filters: oDataReadFilters,
                    success: function(oData, response) {
                        console.log(oData);
                        var resultRows = oData.results;
                        
                        //Local Filtering
                        if ({$oParamsJs}.data && {$oParamsJs}.data.filters && {$oParamsJs}.data.filters.conditions) {
                            var oLocalFilters = {$localFilters};
                            if (oLocalFilters.length !== 0) {
                                var conditions = {$oParamsJs}.data.filters.conditions;
                                for (var i = 0; i < oLocalFilters.length; i++) {
                                    var filterAttr = oLocalFilters[i];
                                    var cond = {};
                                    for (var j = 0; j < conditions.length; j++) {
                                        if (conditions[j].expression === filterAttr) {
                                            cond = conditions[j];
                                        }
                                    }
                                    if (cond.value === undefined || cond.value === null || cond.value === '') continue;
                                    switch (cond.comparator) {
                                        case '==':
                                            resultRows = resultRows.filter(row => {
                                                return row[cond.expression] == cond.value
                                            });
                                            break;
                                        case '!==':
                                            resultRows = resultRows.filter(row => {
                                                return row[cond.expression] !== cond.value
                                            });
                                            break;
                                        case '!=':
                                            var val = cond.value.toString().toLowerCase();
                                            resultRows = resultRows.filter(row => {
                                                if (row[cond.expression] === undefined) return true;
                                                return ! row[cond.expression].toString().toLowerCase().includes(val);
                                            });
                                            break;
                                        case '=':
                                        default:
                                            var val = cond.value.toString().toLowerCase();
                                            resultRows = resultRows.filter(row => {
                                                if (row[cond.expression] === undefined) return false;
                                                return row[cond.expression].toString().toLowerCase().includes(val);
                                            });
                                    }
                                }
                            }
                        }
                        var oRowData = {
                            rows: resultRows
                        };
                        {$oModelJs}.setData(oRowData);
                        {$onModelLoadedJs}
                        {$this->getElement()->buildJsBusyIconHide()}
                    },
                    error: function(oError) {
                        console.error(oError);
                    }
                });
                
JS;
    }
    
    protected function needsLocalFiltering(MetaAttributeInterface $attr) : bool
    {
        return BooleanDataType::cast($attr->getDataAddressProperty('filter_locally')) ?? false;
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