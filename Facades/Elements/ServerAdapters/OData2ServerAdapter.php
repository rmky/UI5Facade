<?php
namespace exface\UI5Facade\Facades\Elements\ServerAdapters;

use exface\UI5Facade\Facades\Elements\UI5AbstractElement;
use exface\UI5Facade\Facades\Interfaces\UI5ServerAdapterInterface;
use exface\UrlDataConnector\DataConnectors\OData2Connector;
use exface\Core\Exceptions\Facades\FacadeLogicError;
use exface\Core\Interfaces\Model\MetaObjectInterface;
use exface\Core\Interfaces\Model\MetaAttributeInterface;
use exface\Core\DataTypes\BooleanDataType;
use exface\Core\DataTypes\DateDataType;
use exface\Core\Factories\QueryBuilderFactory;
use exface\UrlDataConnector\QueryBuilders\OData2JsonUrlBuilder;
use exface\Core\Factories\ConditionFactory;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Actions\ReadData;
use exface\Core\Actions\ReadPrefill;
use exface\UrlDataConnector\Actions\CallOData2Operation;
use exface\Core\Actions\DeleteObject;
use exface\Core\Interfaces\Widgets\iHaveQuickSearch;
use exface\Core\Actions\ExportXLSX;
use exface\Core\Actions\ExportCSV;
use exface\Core\Actions\UpdateData;
use exface\Core\CommonLogic\QueryBuilder\QueryPart;
use exface\Core\CommonLogic\QueryBuilder\QueryPartAttribute;
use exface\Core\Actions\SaveData;
use exface\Core\Actions\CreateData;
use exface\Core\DataTypes\TimeDataType;
use exface\Core\DataTypes\DateTimeDataType;
use exface\UI5Facade\Exceptions\UI5ExportUnsupportedActionException;
use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Widgets\Data;
use exface\UI5Facade\Exceptions\UI5ExportUnsupportedWidgetException;

/**
 * 
 * @author rml
 * 
 * Known issues:
 * 
 * - Local filtering will not yield expected results if pagination is enabled. However,
 * this seems to be also true for th OData2JsonUrlBuilder. Perhaps, it would be better
 * to disable pagination as soon as at least one filter is detected, that cannot be applied
 * remotely.
 * 
 * - $inlinecount=allpages is allways used, not only if it is explicitly enabled in the
 * data adress property `odata_$inlinecount` (this property has no effect here!). This is
 * due to the fact, that the "read+1" pagination would significantly increase the complexity
 * of the adapter logic.
 * 
 * - for now QuickSearch must only include filters that are filtered locally as there seems to be an issue
 * with the way the URL parameters are build using "substringof()". Either the oData Service needs to support
 * the substring() function or there is another problem, the issue needs some more digging into
 *
 */
class OData2ServerAdapter implements UI5ServerAdapterInterface
{
    private $element = null;
    
    private $useConnectionCredentials = false;
    
    public function __construct(UI5AbstractElement $element)
    {
        $this->element = $element; 
        
        $facadeConfig = $element->getFacade()->getConfig();
        if ($facadeConfig->hasOption('WEBAPP_EXPORT.USE_CONNECTION_CREDENTIALS')) {
            $this->useConnectionCredentials = $facadeConfig->getOption('WEBAPP_EXPORT.USE_CONNECTION_CREDENTIALS');
        }
        
        $this->checkWidgetExportable($element->getWidget());
    }
    
    /**
     * 
     * @param WidgetInterface $widget
     * @throws UI5ExportUnsupportedWidgetException
     */
    protected function checkWidgetExportable(WidgetInterface $widget)
    {
        switch (true) {
            case ($widget instanceof Data):
                foreach ($widget->getColumns() as $col) {
                    if ($col->hasFooter()) {
                        throw new UI5ExportUnsupportedWidgetException($widget, 'Cannot export data widgets with column footers!');
                    }
                }
                break;
        }
    }
    
    /**
     * 
     * @return bool
     */
    protected function getUseConnectionCredentials() : bool
    {
        return $this->useConnectionCredentials;
    }
    
    /**
     * 
     * @return UI5AbstractElement
     */
    public function getElement() : UI5AbstractElement
    {
        return $this->element;
    }
    
    /**
     * returns based on the given action the correct server request javascript code for that action
     * 
     * @param ActionInterface $action
     * @param string $oModelJs
     * @param string $oParamsJs
     * @param string $onModelLoadedJs
     * @param string $onErrorJs
     * @param string $onOfflineJs
     * @return string
     */
    public function buildJsServerRequest(ActionInterface $action, string $oModelJs, string $oParamsJs, string $onModelLoadedJs, string $onErrorJs = '', string $onOfflineJs = '') : string
    {
        switch (true) {
            case get_class($action) === ReadPrefill::class:
                return $this->buildJsPrefillLoader($oModelJs, $oParamsJs, $onModelLoadedJs, $onErrorJs, $onOfflineJs);
            case get_class($action) === ReadData::class:
                return $this->buildJsDataLoader($oModelJs, $oParamsJs, $onModelLoadedJs, $onErrorJs, $onOfflineJs);
            case get_class($action) === CallOData2Operation::class:
                return $this->buildJsCallFunctionImport($action, $oModelJs, $oParamsJs, $onModelLoadedJs, $onErrorJs, $onOfflineJs);
            case get_class($action) === DeleteObject::class:
                return $this->buildJsDataDelete($oModelJs, $oParamsJs, $onModelLoadedJs, $onErrorJs, $onOfflineJs);
            case get_class($action) === UpdateData::class:
                return $this->buildJsDataWrite($action, $oModelJs, $oParamsJs, $onModelLoadedJs, $onErrorJs, $onOfflineJs);
            case get_class($action) === CreateData::class:
                return $this->buildJsDataWrite($action, $oModelJs, $oParamsJs, $onModelLoadedJs, $onErrorJs, $onOfflineJs);
            case get_class($action) === SaveData::class:
                return $this->buildJsDataWrite($action, $oModelJs, $oParamsJs, $onModelLoadedJs, $onErrorJs, $onOfflineJs);
            default:
                // FIXME #fiori-export throw new UI5ExportUnsupportedActionException('Action "' . $action->getAliasWithNamespace() . '" cannot be used with Fiori export!');
                return <<<JS

console.error('Unsupported action {$action->getAliasWithNamespace()}', {$oParamsJs});

JS;
        }
        
        return '';
    }
    
    /**
     * 
     * TODO check if filtering over relations an throw a JS error
     * IDEA add support for sparse fieldsets ($select URL parameter) and check for relations there
     * 
     * @param string $oModelJs
     * @param string $oParamsJs
     * @param string $onModelLoadedJs
     * @param string $onErrorJs
     * @param string $onOfflineJs
     * @return string
     */
    protected function buildJsDataLoader(string $oModelJs, string $oParamsJs, string $onModelLoadedJs, string $onErrorJs = '', string $onOfflineJs = '') : string
    {
        $widget = $this->getElement()->getWidget();
        $object = $widget->getMetaObject();
        
        $localFilters = json_encode($this->getAttributeAliasesForLocalFilters($object));
        $quickSearchFilters = [];
        if ($widget instanceof iHaveQuickSearch) {
            
            foreach ($widget->getAttributesForQuickSearch() as $attr) {
                $quickSearchFilters[] = $attr->getAlias();
            }
            if (count($quickSearchFilters) !== 0) {
                $quickSearchFilters = json_encode($quickSearchFilters);
            }          
        }
        $dateAttributes = [];
        $timeAttributes = [];
        foreach ($object->getAttributes() as $qpart) {
            if ($qpart->getDataType() instanceof DateDataType) {
                $dateAttributes[] = $qpart->getAlias();
            }
            if ($qpart->getDataType() instanceof TimeDataType) {
                $timeAttributes[] = $qpart->getAlias();
            }
        }
        $dateAttributes = json_encode($dateAttributes);
        $timeAttributes = json_encode($timeAttributes);
        
        $opIS = EXF_COMPARATOR_IS;
        $opISNOT = EXF_COMPARATOR_IS_NOT;
        $opEQ = EXF_COMPARATOR_EQUALS;
        $opNE = EXF_COMPARATOR_EQUALS_NOT;
        $opLT = EXF_COMPARATOR_LESS_THAN;
        $opLE = EXF_COMPARATOR_LESS_THAN_OR_EQUALS;
        $opGT = EXF_COMPARATOR_GREATER_THAN;
        $opGE = EXF_COMPARATOR_GREATER_THAN_OR_EQUALS;
        
        return <<<JS
            console.log('oParams:', {$oParamsJs});
            var oDataModel = new sap.ui.model.odata.v2.ODataModel({$this->getODataModelParams($object)});  
            var oDataReadParams = {};
            var oDataReadFiltersSearch = [];
            var oDataReadFiltersQuickSearch = [];
            var oDataReadFilters = [];
            var oDataReadFiltersArray = [];
            var oQuickSearchFilters = {$quickSearchFilters};
            var oLocalFilters = {$localFilters};
            
            // Pagination
            if ({$oParamsJs}.hasOwnProperty('length') === true) {
                oDataReadParams.\$top = {$oParamsJs}.length;
                if ({$oParamsJs}.hasOwnProperty('start') === true) {
                    oDataReadParams.\$skip = {$oParamsJs}.start;      
                }
                oDataReadParams.\$inlinecount = 'allpages';
            }

            // Filters
            if ({$oParamsJs}.data && {$oParamsJs}.data.filters && {$oParamsJs}.data.filters.conditions) {
                var conditions = {$oParamsJs}.data.filters.conditions               
                for (var i = 0; i < conditions.length; i++) {
                    switch (conditions[i].comparator) {
                        case '{$opIS}':
                            var oOperator = "Contains";
                            break;
                        case '{$opISNOT}':
                            var oOperator = "NotContains";
                            break;
                        case '{$opEQ}':
                            var oOperator = "EQ";
                            break;                            
                        case '{$opNE}':
                            var oOperator = "NE";
                            break;
                        case '{$opLT}':
                            var oOperator = "LT";
                            break;
                        case '{$opLE}':
                            var oOperator = "LE";
                            break;
                        case '{$opGT}':
                            var oOperator = "GT";
                            break;
                        case '{$opGE}':
                            var oOperator ="GE";
                            break;
                        default:
                            var oOperator = "EQ";
                    }
                    if (conditions[i].value !== "") {
                        if ({$timeAttributes}.indexOf(conditions[i].expression) > -1) {
                            var d = conditions[i].value;
                            var timeParts = d.split(':');
                            if (timeParts[3] === undefined || timeParts[3]=== null || timeParts[3] === "") {
                                timeParts[3] = "00";
                            }
                            for (var j = 0; j < timeParts.length; j++) {
                                timeParts[j] = ('0'+(timeParts[j])).slice(-2);
                            }                            
                            var timeString = "PT" + timeParts[0] + "H" + timeParts[1] + "M" + timeParts[3] + "S";
                            var value = timeString;
                        } else {
                            var value = conditions[i].value;
                        }
                        var filter = new sap.ui.model.Filter({
                            path: conditions[i].expression,
                            operator: oOperator,
                            value1: value
                        });
                        oDataReadFiltersSearch.push(filter);                            
                    }
                    if ({$oParamsJs}.q !== undefined && {$oParamsJs}.q !== "" ) {
                        if (oQuickSearchFilters[0] !== undefined) {
                            if (oQuickSearchFilters.includes(conditions[i].expression) && !oLocalFilters.includes(conditions[i].expression)) {
                                var filterQuickSearchItem = new sap.ui.model.Filter({
                                    path: conditions[i].expression,
                                    operator: "Contains",
                                    value1: {$oParamsJs}.q
                                });
                                oDataReadFiltersQuickSearch.push(filterQuickSearchItem);
                            }
                        } 
                    }                        
                }
            }
            
            if (oDataReadFiltersSearch.length !== 0) {
                var tempFilter = new sap.ui.model.Filter({filters: oDataReadFiltersSearch, and: true})
                oDataReadFiltersArray.push(tempFilter);
            }
            if (oDataReadFiltersQuickSearch.length !== 0) {
                var tempFilter2 = new sap.ui.model.Filter({filters: oDataReadFiltersQuickSearch, and: false})
                oDataReadFiltersArray.push(tempFilter2);
            }
            if (oDataReadFiltersArray.length !== 0) {
                var combinedFilter = new sap.ui.model.Filter({
                    filters: oDataReadFiltersArray,
                    and: true
                })
                oDataReadFilters.push(combinedFilter)
            }

            //Sorters
            var oDataReadSorters = [];
            if ({$oParamsJs}.sort !== undefined && {$oParamsJs}.sort !== "") {
                var sorters = {$oParamsJs}.sort.split(",");
                var directions = {$oParamsJs}.order.split(",");
                for (var i = 0; i < sorters.length; i++) {
                    if (directions[i] === "desc") {
                        var sortObject = new sap.ui.model.Sorter(sorters[i], true);
                    } else {
                        var sortObject = new sap.ui.model.Sorter(sorters[i], false);
                    }
                    oDataReadSorters.push(sortObject);
                }
            }

            oDataModel.read('/{$object->getDataAddress()}', {
                urlParameters: oDataReadParams,
                filters: oDataReadFilters,
                sorters: oDataReadSorters,
                success: function(oData, response) {
                    var resultRows = oData.results;

                    //Date Conversion
                    if ({$dateAttributes}[0] !== undefined) {
                        for (var i = 0; i < resultRows.length; i++) {
                            for (var j = 0; j < {$dateAttributes}.length; j++) {
                                var attr = {$dateAttributes}[j].toString();
                                var d = resultRows[i][attr];
                                if (d !== undefined && d !== "" && d !== null) {
                                    var oDateFormat = sap.ui.core.format.DateFormat.getDateTimeInstance();
                                    var newVal = oDateFormat.format(d);                                   
                                    resultRows[i][attr] = newVal;
                                }
                            }
                        }
                    }
                    //Time Conversion
                    if ({$timeAttributes}[0] !== undefined) {
                        for (var i = 0; i < resultRows.length; i++) {
                            for (var j = 0; j < {$timeAttributes}.length; j++) {
                                var attr = {$timeAttributes}[j].toString();
                                var d = resultRows[i][attr];
                                if (d.ms !== undefined && d.ms !== "" && d.ms !== null) {
                                    var hours = Math.floor(d.ms / (1000 * 60 * 60));
                                    var minutes = Math.floor(d.ms / 60000 - hours * 60);
                                    var seconds = Math.floor(d.ms / 1000 - hours * 60 * 60 - minutes * 60);
                                    var newVal = hours + ":" + minutes + ":" + seconds;
                                    resultRows[i][attr] = newVal;
                                }
                            }
                        }
                    }
                    
                    //Local Filtering
                    if ({$oParamsJs}.data && {$oParamsJs}.data.filters && {$oParamsJs}.data.filters.conditions) {                            
                        if (oLocalFilters.length !== 0) {
                            var conditions = {$oParamsJs}.data.filters.conditions;
                            
                            //QuickSearchFilter Local
                            if ({$oParamsJs}.q !== undefined && {$oParamsJs}.q !== "" && oQuickSearchFilters[0] !== undefined) {
                                var quickSearchVal = {$oParamsJs}.q.toString().toLowerCase();
                                resultRows = resultRows.filter(row => {
                                        var filtered = false;
                                        for (var i = 0; i < oQuickSearchFilters.length; i++) {
                                            if (oLocalFilters.includes(oQuickSearchFilters[i]) && row[oQuickSearchFilters[i]].toString().toLowerCase().includes(quickSearchVal)) {
                                                filtered = true;
                                            }
                                            if (!oLocalFilters.includes(oQuickSearchFilters[i])) {
                                                filtered = true;
                                            }
                                        }
                                        return filtered;
                                });
                            }
                            
                            for (var i = 0; i < oLocalFilters.length; i++) {
                                var filterAttr = oLocalFilters[i];
                                var cond = {};
                                for (var j = 0; j < conditions.length; j++) {
                                    if (conditions[j].expression === filterAttr) {
                                        cond = conditions[j];
                                    }
                                }
                                if (cond.value === undefined || cond.value === null || cond.value === '') {
                                    continue;
                                }
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

                    // Pagination
                    if (oData.__count !== undefined) {
                        oRowData.recordsFiltered = oData.__count;
                    }
                    
                    {$oModelJs}.setData(oRowData);
                    {$onModelLoadedJs}
                    {$this->getElement()->buildJsBusyIconHide()}
                },
                error: function(oError){
                    {$onErrorJs}
                    {$this->buildJsResponseErrorHandling('oError')}
                }
            });
                
JS;
    }

    /**
     * 
     * @param string $oModelJs
     * @param string $oParamsJs
     * @param string $onModelLoadedJs
     * @param string $onErrorJs
     * @param string $onOfflineJs
     * @throws FacadeLogicError
     * @return string
     */
    protected function buildJsPrefillLoader(string $oModelJs, string $oParamsJs, string $onModelLoadedJs, string $onErrorJs = '', string $onOfflineJs = '') : string
    {
        $object = $this->getElement()->getMetaObject();
        if ($object->hasUidAttribute() === false) {
            throw new FacadeLogicError('TODO');
        } else {
            $uidAttr = $object->getUidAttribute();
        }
        
        $takeFirstRowOnly = <<<JS

            if (Object.keys({$oModelJs}.getData()).length !== 0) {
                {$oModelJs}.setData({});
            }
            if (Array.isArray(oRowData.rows) && oRowData.rows.length === 1) {
                {$oModelJs}.setData(oRowData.rows[0]);
            }

JS;
        $onModelLoadedJs = $takeFirstRowOnly . $onModelLoadedJs;
        
        return <<<JS
        
            var oFirstRow = {$oParamsJs}.data.rows[0];
            if (oFirstRow === undefined) {
                console.error('No data to filter the prefill!');
            }
    
            {$oParamsJs}.data.filters = {
                conditions: [
                    {
                        comparator: "==",
                        expression: "{$uidAttr->getAlias()}",
                        object_alias: "{$object->getAliasWithNamespace()}",
                        value: oFirstRow["{$object->getUidAttribute()->getAlias()}"]
                    }
                ]
            };
            {$this->buildJsDataLoader($oModelJs, $oParamsJs, $onModelLoadedJs, $onErrorJs, $onOfflineJs)}
JS;
    }
    
    /**
     * 
     * @param MetaAttributeInterface $attr
     * @return bool
     */
    protected function needsLocalFiltering(MetaAttributeInterface $attr) : bool
    {
        return BooleanDataType::cast($attr->getDataAddressProperty('filter_locally')) ?? false;
    }
           
    /**
     * 
     * @param MetaObjectInterface $object
     * @throws FacadeLogicError
     * @return string
     */
    protected function getODataModelParams(MetaObjectInterface $object) : string
    {
        $connection = $object->getDataConnection();
        
        if (! $connection instanceof OData2Connector) {
            throw new FacadeLogicError('Cannot use direct OData 2 connections with object "' . $object->getName() . '" (' . $object->getAliasWithNamespace() . ')!');
        }
        
        $params = [];
        $params['serviceUrl'] = rtrim($connection->getUrl(), "/") . '/';
        if ($connection->getUser()) {
            if ($this->getUseConnectionCredentials() === true) {
                $params['user'] = $connection->getUser();
                $params['password'] = $connection->getPassword();
            }
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
    
    /**
     * 
     * @param MetaObjectInterface $object
     * @throws FacadeLogicError
     * @return array
     */
    protected function getAttributeAliasesForLocalFilters(MetaObjectInterface $object) : array
    {
        $localFilterAliases = [];
        $dummyQueryBuilder = QueryBuilderFactory::createForObject($object);
        if (! $dummyQueryBuilder instanceof OData2JsonUrlBuilder) {
            throw new FacadeLogicError('TODO');
        }
        foreach ($object->getAttributes()->getAll() as $attr) {
            $filterCondition = ConditionFactory::createFromExpressionString($object, $attr->getAlias(), '');
            $filterQpart = $dummyQueryBuilder->addFilterCondition($filterCondition);
            if ($filterQpart->getApplyAfterReading()) {
                $localFilterAliases[] = $attr->getAlias();
            }
        }
        return $localFilterAliases;
    }
    
    /**
     * 
     * @param ActionInterface $action
     * @param string $oModelJs
     * @param string $oParamsJs
     * @param string $onModelLoadedJs
     * @param string $onErrorJs
     * @param string $onOfflineJs
     * @return string
     */
    protected function buildJsDataWrite(ActionInterface $action, string $oModelJs, string $oParamsJs, string $onModelLoadedJs, string $onErrorJs = '', string $onOfflineJs = '') : string
    {
        $widget = $this->getElement()->getWidget();
        $object = $widget->getMetaObject();
        $uidAttribute = $object->getUidAttributeAlias();
        $attributes = $object->getAttributes();
        $attributesType = (object)array();
        foreach ($attributes as $attr) {
            $key = $attr->getAlias();
            $attributesType->$key= $attr->getDataAddressProperty('odata_type');
        }
        $attributesType = json_encode($attributesType);
        $uidAttributeType = $object->getUidAttribute()->getDataAddressProperty('odata_type');
        if ($action instanceof CreateData) {
            $serverCall = <<<JS
            
            oDataModel.create("/{$object->getDataAddress()}", oData, {
                success: {$onModelLoadedJs},
                error: function(oError) {
                    {$onErrorJs}
                    {$this->buildJsResponseErrorHandling('oError')}
                }
            });

JS;
        }
        elseif ($action instanceof UpdateData || $action instanceof SaveData) {
            $serverCall = <<<JS
            
            if ('{$uidAttribute}' in oData) {
                var oDataUid = oData.{$uidAttribute};
                var type = '{$uidAttributeType}';
                switch (type) {
                    case 'Edm.Guid':
                        oDataUid = "guid" + "'" + data['{$uidAttribute}']+ "'";
                        break;
                    case 'Edm.Binary':
                        oDataUid = "binary" + "'" + data['{$uidAttribute}'] + "'";
                        break;
                    default:
                        oDataUid = "'" + oDataUid + "'";
                }
            } else {
                var oDataUid = '';
            }

            oDataModel.update("/{$object->getDataAddress()}(" + oDataUid+ ")", oData, {
                success: {$onModelLoadedJs},
                error: function(oError) {
                    {$onErrorJs}
                    {$this->buildJsResponseErrorHandling('oError')}
                }
            });

JS;
        }
        
        return <<<JS
            console.log('Params: ',{$oParamsJs})
            var oDataModel = new sap.ui.model.odata.v2.ODataModel({$this->getODataModelParams($object)});
            //oDataModel.setUseBatch(false);
            for (var i = 0; i < {$oParamsJs}.data.rows.length; i++) {
                var data = {$oParamsJs}.data.rows[i];            
                var oData = {};
                Object.keys(data).forEach(key => {
                    if (data[key] != "") {
                        var type = {$attributesType}[key];
                        switch (type) {
                            case 'Edm.DateTimeOffset':
                                //console.log('RawDate: ',data[key]);
                                //var oDateFormat = sap.ui.core.format.DateFormat.getDateTimeInstance();
                                //var d = oDateFormat.format(data[key]);
                                var d = new Date(data[key]);
                                console.log('Datum: ',d)
                                var date = d.toISOString();
                                var datestring = date.replace(/\.[0-9]{3}/, '');
                                oData[key] = datestring;
                                break;
                            case 'Edm.DateTime':
                                //console.log('RawDate: ',data[key]);
                                //var oDateFormat = sap.ui.core.format.DateFormat.getDateTimeInstance();
                                //var d = oDateFormat.format(data[key]);
                                var d = new Date(data[key]);
                                console.log('Datum: ',d)                            
                                var date = d.toISOString();
                                var datestring = date.substring(0,19);
                                oData[key] = datestring;
                                break;                        
                            case 'Edm.Time':
                                var d = data[key];
                                var timeParts = d.split(':');
                                if (timeParts[3] === undefined || timeParts[3]=== null || timeParts[3] === "") {
                                    timeParts[3] = "00";
                                }
                                for (var i = 0; i < timeParts.length; i++) {
                                    timeParts[i] = ('0'+(timeParts[i])).slice(-2);
                                }                            
                                var timeString = "PT" + timeParts[0] + "H" + timeParts[1] + "M" + timeParts[3] + "S";
                                oData[key] = timeString;
                                break;
                            case 'Edm.Decimal':
                                oData[key] = data[key].toString();
                                break; 
                            default:
                                oData[key] = data[key];
                        }
                    }
                });
                {$serverCall}
            }

JS;
    }
    
    /**
     * 
     * @param string $oModelJs
     * @param string $oParamsJs
     * @param string $onModelLoadedJs
     * @param string $onErrorJs
     * @param string $onOfflineJs
     * @return string
     */
    protected function buildJsDataDelete(string $oModelJs, string $oParamsJs, string $onModelLoadedJs, string $onErrorJs = '', string $onOfflineJs = '') : string
    {
        $widget = $this->getElement()->getWidget();
        $object = $widget->getMetaObject();
        $uidAttribute = $object->getUidAttributeAlias();
        $uidAttributeType = $object->getUidAttribute()->getDataAddressProperty('odata_type');
        
        return <<<JS
            console.log($oParamsJs)
            var oDataModel = new sap.ui.model.odata.v2.ODataModel({$this->getODataModelParams($object)});
            //oDataModel.setUseBatch(false);
            for (var i = 0; i < {$oParamsJs}.data.rows.length; i++) {
                var data = {$oParamsJs}.data.rows[i];
                if ('{$uidAttribute}' in data) {
                    var oDataUid = data.{$uidAttribute};
                    var type = '{$uidAttributeType}';
                    switch (type) {
                        case 'Edm.Guid':
                            oDataUid = "guid" + "'" + oDataUid + "'";
                            break;
                        case 'Edm.Binary':
                            oDataUid = "binary" + "'" + oDataUid + "'";
                            break;
                        case 'Edm.DateTimeOffset':
                            var d = new Date(oDataUid);
                            var date = d.toISOString();
                            var datestring = date.replace(/\.[0-9]{3}/, '');
                            oDataUid = "'" + datestring + "'";
                            break;
                        case 'Edm.DateTime':
                            var d = new Date(oDataUid);
                            var date = d.toISOString();
                            var datestring = date.substring(0,19);
                            oDataUid = "'" + datestring + "'";
                            break;                        
                        case 'Edm.Time':
                            var d = oDataUid;
                            var timeParts = d.split(':');
                            if (timeParts[3] === undefined || timeParts[3]=== null || timeParts[3] === "") {
                                timeParts[3] = "00";
                            }
                            for (var i = 0; i < timeParts.length; i++) {
                                timeParts[i] = ('0'+(timeParts[i])).slice(-2);
                            }                            
                            var timeString = "PT" + timeParts[0] + "H" + timeParts[1] + "M" + timeParts[3] + "S";
                            oDataUid = "'" + timeString + "'";
                            break;
                        case 'Edm.Decimal':
                            oDataUid = "'" + oDataUid.toString() + "'";
                            break;
                        default:
                            oDataUid = "'" + oDataUid + "'";
                    }
                } else {
                    var oDataUid = '';
                }
                oDataModel.remove("/{$object->getDataAddress()}(" + oDataUid + ")" , {
                    success: {$onModelLoadedJs},
                    error: function(oError) {
                        {$onErrorJs}
                        {$this->buildJsResponseErrorHandling('oError')}
                    }
                });
            }

JS;
        
    }
    
    /**
     * 
     * @param ActionInterface $action
     * @param string $oModelJs
     * @param string $oParamsJs
     * @param string $onModelLoadedJs
     * @param string $onErrorJs
     * @param string $onOfflineJs
     * @return string
     */
    protected function buildJsCallFunctionImport(ActionInterface $action, string $oModelJs, string $oParamsJs, string $onModelLoadedJs, string $onErrorJs = '', string $onOfflineJs = '') : string
    {
        $widget = $this->getElement()->getWidget();
        $object = $widget->getMetaObject();
        $parameters = $action->getParameters();
        $requiredParams = [];
        $defaultValues = (object)array();
        foreach ($parameters as $param) {
            if ($param->isRequired() === true) {
                $requiredParams[] = $param->getName();
                if ($param->hasDefaultValue()) {
                    $key = $param->getName();
                    $defaultValues->$key= $param->getDefaultValue();
                }
            }
        }
        $requiredParams = json_encode($requiredParams);
        $defaultValues = json_encode($defaultValues);
        $attributes = $object->getAttributes();
        $attributesType = (object)array();
        foreach ($attributes as $attr) {
            $key = $attr->getAlias();
            $attributesType->$key= $attr->getDataAddressProperty('odata_type');
        }
        $attributesType = json_encode($attributesType);
        
        
        return <<<JS

            console.log('Params: ',$oParamsJs);
            var oDataModel = new sap.ui.model.odata.v2.ODataModel({$this->getODataModelParams($object)});
            var requiredParams = {$requiredParams};
            var defaultValues = {$defaultValues};

            var oDataActionParams = {};
            if ({$oParamsJs}.data.rows.length !== 0) {
                var callAction = true;
                if (requiredParams[0] !== undefined) {
                    for (var i = 0; i < requiredParams.length; i++) {
                        var param = requiredParams[i];
                        if ({$oParamsJs}.data.rows[0][param] != undefined && {$oParamsJs}.data.rows[0][param] != "") {                           
                            var type = {$attributesType}[param];
                            var value = {$oParamsJs}.data.rows[0][param];
                            switch (type) {
                                case 'Edm.DateTimeOffset':
                                    var d = new Date(value);
                                    var date = d.toISOString();
                                    var datestring = date.replace(/\.[0-9]{3}/, '');
                                    oDataActionParams[param] = datestring;
                                    break;
                                case 'Edm.DateTime':
                                    var d = new Date(value);                            
                                    var date = d.toISOString();
                                    var datestring = date.substring(0,19);
                                    oDataActionParams[param] = datestring;
                                    break;                        
                                case 'Edm.Time':
                                    var d = value;
                                    var timeParts = d.split(':');
                                    if (timeParts[3] === undefined || timeParts[3]=== null || timeParts[3] === "") {
                                        timeParts[3] = "00";
                                    }
                                    for (var i = 0; i < timeParts.length; i++) {
                                        timeParts[i] = ('0'+(timeParts[i])).slice(-2);
                                    }                            
                                    var timeString = "PT" + timeParts[0] + "H" + timeParts[1] + "M" + timeParts[3] + "S";
                                    oDataActionParams[param] = timeString;
                                    break;
                                case 'Edm.Decimal':
                                    oDataActionParams[param] = value.toString();
                                    break; 
                                default:
                                    oDataActionParams[param] = value;
                            }
                        } else if (defaultValues.hasOwnProperty(param)) {
                            oDataActionParams[param] = defaultValues[param];
                        } else {
                            oDataActionParams[param] = "";
                            callAction = false;
                            console.error('No value given for required parameter: ', param);
                            {$this->getElement()->buildJsShowError('"No value given for parameter:  " + param', '"ERROR"')}
                        }
                    }
                }
                if (callAction === true) {
                    oDataModel.callFunction('/{$action->getServiceName()}', {
                        urlParameters: oDataActionParams,
                        success: {$onModelLoadedJs},
                        error: function(oError) { 
                            {$onErrorJs}
                            {$this->buildJsResponseErrorHandling('oError')}
                        }
                    });
                }
            } else {                
                {$this->getElement()->buildJsShowError('"No row selected!"', '"ERROR"')}
                {$this->getElement()->buildJsBusyIconHide()}
            }
            
JS;
    }
    
    /**
     * 
     * @param string $oErrorJs
     * @return string
     */
    protected function buildJsResponseErrorHandling(string $oErrorJs = 'oError') : string
    {
        return <<<JS
        
        var response = {};
        try {
            response = $.parseJSON(oError.responseText);
            var errorText = response.error.message.value;
        } catch (e) {
            var errorText = '<p> No Error description send! </p>';
        }
        {$this->getElement()->buildJsShowError('errorText', "{$oErrorJs}.statusCode + ' ' + {$oErrorJs}.statusText")}
        {$this->getElement()->buildJsBusyIconHide()}

JS;
        
    }
}