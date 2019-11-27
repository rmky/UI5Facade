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
use exface\Core\Actions\UpdateData;
use exface\Core\Actions\SaveData;
use exface\Core\Actions\CreateData;
use exface\Core\DataTypes\TimeDataType;
use exface\UI5Facade\Exceptions\UI5ExportUnsupportedActionException;
use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Widgets\Data;
use exface\UI5Facade\Exceptions\UI5ExportUnsupportedWidgetException;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\Actions\Autosuggest;
use exface\Core\Interfaces\Widgets\iHaveColumns;

/**
 * 
 * @author Ralf Mulansky
 * 
 * The OData2ServerAdapter performs actions by directly sending requests to an OData2 service.
 * To do so it evaluates what actions can be performed by widgets and builds the corresponding
 * java script codes for those actions. To do so it transforms the give parameter for that action
 * to fit the OData2 services. The adapter creates a new ODataModel object for each action
 * and adds the transformed parameters to that model. Then it calls the corresponding ODataModel
 * method to the wanted action and adds the success and error handler for the server response.
 * Read request are send to the server via the ODataModel 'read' method.
 * 'Create', 'Update', 'Delete' and 'Function Import' requests are send via the 'submitChanges' method.
 * By default each action request is send as a single server request, because the default OData2 service
 * implementation for CRUD operations does not support multiple operation requests send in a single
 * request, stacked via $batch.
 * It is possible to activate $batch requests my changing the following options in the
 * 'exface.UI5Facade.config.json' file:
 * 
 * "WEBAPP_EXPORT.ODATA.USE_BATCH_DELETES" : false
 * "WEBAPP_EXPORT.ODATA.USE_BATCH_WRITES" : false
 * "WEBAPP_EXPORT.ODATA.USE_BATCH_FUNCTION_IMPORTS" : false
 * 
 * Changing those options to true will enable the $batch requests for 'Delete' and/or 'Create'/'Update'
 * and/or 'Function Import' actions.
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
 */
class OData2ServerAdapter implements UI5ServerAdapterInterface
{
    private $element = null;
    
    private $useConnectionCredentials = false;
    
    private $useBatchWrites = false;
    
    private $useBatchDeletes = false;
    
    private $useBatchFunctionImports = false;
    
    /**
     * 
     * @param UI5AbstractElement $element
     */
    public function __construct(UI5AbstractElement $element)
    {
        $this->element = $element; 
        
        $facadeConfig = $element->getFacade()->getConfig();
        if ($facadeConfig->hasOption('WEBAPP_EXPORT.ODATA.USE_CONNECTION_CREDENTIALS')) {
            $this->useConnectionCredentials = $facadeConfig->getOption('WEBAPP_EXPORT.ODATA.USE_CONNECTION_CREDENTIALS');
        }
        if ($facadeConfig->hasOption('WEBAPP_EXPORT.ODATA.USE_BATCH_DELETES')) {
            $this->setUseBatchDeletes($facadeConfig->getOption('WEBAPP_EXPORT.ODATA.USE_BATCH_DELETES'));
        }
        if ($facadeConfig->hasOption('WEBAPP_EXPORT.ODATA.USE_BATCH_WRITES')) {
            $this->setUseBatchWrites($facadeConfig->getOption('WEBAPP_EXPORT.ODATA.USE_BATCH_WRITES'));
        }
        if ($facadeConfig->hasOption('WEBAPP_EXPORT.ODATA.USE_BATCH_FUNCTION_IMPORTS')) {
            $this->setUseBatchFunctionImports($facadeConfig->getOption('WEBAPP_EXPORT.ODATA.USE_BATCH_FUNCTION_IMPORTS'));
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
     * Based on the given action returns the correct server request javascript code for that action
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
            case get_class($action) === Autosuggest::class:
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
                throw new UI5ExportUnsupportedActionException('Action "' . $action->getAliasWithNamespace() . '" cannot be used with Fiori export!');
                return <<<JS

        console.error('Unsupported action {$action->getAliasWithNamespace()}', {$oParamsJs});

JS;
        }
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
        
        if ($widget instanceof iHaveColumns) {
            foreach ($widget->getColumns() as $col) {
                if ($col->getExpression()->isMetaAttribute() === false) {
                    throw new UI5ExportUnsupportedWidgetException($col, 'Cannot export column "' . $col->getCaption() . '" (' . $col->getAttributeAlias() . ') - only columns referencing meta attributes currently work in exported apps.');
                }
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
        $dateAttributesJson = json_encode($dateAttributes);
        $timeAttributesJson = json_encode($timeAttributes);
        
        $opISNOT = EXF_COMPARATOR_IS_NOT;
        $opEQ = EXF_COMPARATOR_EQUALS;
        $opNE = EXF_COMPARATOR_EQUALS_NOT;
        
        return <<<JS
            var oDataModel = new sap.ui.model.odata.v2.ODataModel({$this->getODataModelParams($object)});
            var oDataReadParams = {};
            var oDataReadFiltersSearch = [];
            var oDataReadFiltersQuickSearch = [];
            var oDataReadFiltersTempGroup = [];
            var oDataReadFiltersGroups = [];
            var oDataReadFilters = [];
            var oDataReadFiltersArray = [];
            var oQuickSearchFilters = {$quickSearchFilters};
            var oLocalFilters = {$localFilters};
            var oAttrsByDataType = {
                date: $dateAttributesJson,
                time: $timeAttributesJson
            };
            
            // Pagination
            if ({$oParamsJs}.hasOwnProperty('length') === true) {
                oDataReadParams.\$top = {$oParamsJs}.length;
                if ({$oParamsJs}.hasOwnProperty('start') === true) {
                    oDataReadParams.\$skip = {$oParamsJs}.start;      
                }
                oDataReadParams.\$inlinecount = 'allpages';
            }

            // Header Filters 
            if ({$oParamsJs}.data && {$oParamsJs}.data.filters && {$oParamsJs}.data.filters.conditions) {
                var conditions = {$oParamsJs}.data.filters.conditions;
                var conditionsCount = conditions.length;               
                for (var i = 0; i < conditionsCount; i++) {
                    var cond = conditions[i];
                    {$this->buildJsAddConditionToFilter('oAttrsByDataType', 'oDataReadFiltersSearch', 'cond')}

                    //QuickSearch
                    if ({$oParamsJs}.q !== undefined && {$oParamsJs}.q !== "" ) {
                        if (oQuickSearchFilters[0] !== undefined) {
                            if (oQuickSearchFilters.includes(cond.expression) && !oLocalFilters.includes(cond.expression)) {
                                var filterQuickSearchItem = new sap.ui.model.Filter({
                                    path: cond.expression,
                                    operator: "Contains",
                                    value1: {$oParamsJs}.q
                                });
                                oDataReadFiltersQuickSearch.push(filterQuickSearchItem);
                            }
                        } 
                    }                        
                }
            }

            // Settings menu Filters 
            if ({$oParamsJs}.data && {$oParamsJs}.data.filters && {$oParamsJs}.data.filters.nested_groups) {
                var groupsCount = {$oParamsJs}.data.filters.nested_groups.length;
                for (var j = 0; j < groupsCount; j++) {
                    var conditions = {$oParamsJs}.data.filters.nested_groups[j].conditions 
                    var conditionsCount = conditions.length;              
                    for (var i = 0; i < conditionsCount; i++) {
                        var cond = conditions[i];
                        {$this->buildJsAddConditionToFilter('oAttrsByDataType', 'oDataReadFiltersTempGroup', 'cond')}
                    }
                    if (oDataReadFiltersTempGroup.length !== 0) {
                        var tempFilter = new sap.ui.model.Filter({filters: oDataReadFiltersTempGroup, and: true})
                        oDataReadFiltersGroups.push(tempFilter);
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
            if (oDataReadFiltersGroups.length !== 0) {
                var tempFilter = new sap.ui.model.Filter({filters: oDataReadFiltersGroups, and: true})
                oDataReadFiltersArray.push(tempFilter);
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
                    if (oAttrsByDataType.date[0] !== undefined) {
                        for (var i = 0; i < resultRows.length; i++) {
                            for (var j = 0; j < oAttrsByDataType.date.length; j++) {
                                var attr = oAttrsByDataType.date[j].toString();
                                var d = resultRows[i][attr];
                                if (d !== undefined && d !== "" && d !== null) {
                                    //var oDateFormat = sap.ui.core.format.DateFormat.getDateTimeInstance({pattern:'yyyy-MM-dd HH:mm:ss'});
                                    //var newVal = oDateFormat.format(d);
                                    var newVal = exfTools.date.format(d, 'Y-m-d H:i:s');                                 
                                    resultRows[i][attr] = newVal;
                                }
                            }
                        }
                    }
                    //Time Conversion
                    if (oAttrsByDataType.time[0] !== undefined) {
                        for (var i = 0; i < resultRows.length; i++) {
                            for (var j = 0; j < oAttrsByDataType.time.length; j++) {
                                var attr = oAttrsByDataType.time[j].toString();
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
                                    case '{$opEQ}':
                                        resultRows = resultRows.filter(row => {
                                            return row[cond.expression] == cond.value
                                        });
                                        break;
                                    case '{$opNE}':
                                        resultRows = resultRows.filter(row => {
                                            return row[cond.expression] !== cond.value
                                        });
                                        break;
                                    case '{$opISNOT}':
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
                },
                error: function(oError){
                    {$onErrorJs}
                    {$this->buildJsServerResponseError('oError')}
                }
            });
                
JS;
    }
    
    protected function buildJsAddConditionToFilter (string $oAttrsByDataTypeJs, string $filterArrayJs, string $condJs = 'cond') : string
    {
        $opIS = EXF_COMPARATOR_IS;
        $opISNOT = EXF_COMPARATOR_IS_NOT;
        $opEQ = EXF_COMPARATOR_EQUALS;
        $opNE = EXF_COMPARATOR_EQUALS_NOT;
        $opLT = EXF_COMPARATOR_LESS_THAN;
        $opLE = EXF_COMPARATOR_LESS_THAN_OR_EQUALS;
        $opGT = EXF_COMPARATOR_GREATER_THAN;
        $opGE = EXF_COMPARATOR_GREATER_THAN_OR_EQUALS;
        
        return <<<JS
                    
                    var sOperator, value;
                    switch ({$condJs}.comparator) {
                        case '{$opIS}':
                            sOperator = "Contains";
                            break;
                        case '{$opISNOT}':
                            sOperator = "NotContains";
                            break;
                        case '{$opEQ}':
                            sOperator = "EQ";
                            break;                            
                        case '{$opNE}':
                            sOperator = "NE";
                            break;
                        case '{$opLT}':
                            sOperator = "LT";
                            break;
                        case '{$opLE}':
                            sOperator = "LE";
                            break;
                        case '{$opGT}':
                            sOperator = "GT";
                            break;
                        case '{$opGE}':
                            sOperator ="GE";
                            break;
                        default:
                            var sOperator = "EQ";
                    }
                    if ({$condJs}.value !== "") {
                        if ({$oAttrsByDataTypeJs}.time.indexOf({$condJs}.expression) > -1) {
                            var d = {$condJs}.value;
                            var timeParts = d.split(':');
                            if (timeParts[3] === undefined || timeParts[3]=== null || timeParts[3] === "") {
                                timeParts[3] = "00";
                            }
                            for (var j = 0; j < timeParts.length; j++) {
                                timeParts[j] = ('0'+(timeParts[j])).slice(-2);
                            }                            
                            var timeString = "PT" + timeParts[0] + "H" + timeParts[1] + "M" + timeParts[3] + "S";
                            value = timeString;
                            if (sOperator === "Contains") {
                                sOperator = "EQ";
                            }
                        } else if ({$oAttrsByDataTypeJs}.date.indexOf({$condJs}.expression) > -1) {
                            value = {$condJs}.value + ' GMT+0000';
                            if (sOperator === "Contains") {
                                sOperator = "EQ";
                            }
                        } else {
                            value = {$condJs}.value;
                        }
                        var filter = new sap.ui.model.Filter({
                            path: {$condJs}.expression,
                            operator: sOperator,
                            value1: value
                        });
                        {$filterArrayJs}.push(filter); 
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
     * @throws FacadeLogicError
     * @return string
     */
    protected function buildJsPrefillLoader(string $oModelJs, string $oParamsJs, string $onModelLoadedJs, string $onErrorJs = '', string $onOfflineJs = '') : string
    {
        $object = $this->getElement()->getMetaObject();
        if ($object->hasUidAttribute() === false) {
            throw new FacadeLogicError('No Uid attribute found for object "' . $object->getName() . '" (' . $object->getAliasWithNamespace() . ')!');
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
        $opEQ = EXF_COMPARATOR_EQUALS;
        
        return <<<JS
        
            var oFirstRow = {$oParamsJs}.data.rows[0];
            if (oFirstRow === undefined) {
                console.error('No data to filter the prefill!');
            }
    
            {$oParamsJs}.data.filters = {
                conditions: [
                    {
                        comparator: "{$opEQ}",
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
        
        $dataSourceAlias = $object->getDataSource()->getId();
        $config = $this->getElement()->getFacade()->getConfig();
        /* @var $sourcesUxon \exface\Core\CommonLogic\UxonObject */
        $sourcesUxon = $config->getOption('WEBAPP_EXPORT.MANIFEST.DATASOURCES');
        $url = rtrim($connection->getUrl(), "/") . '/';
        if ($config->getOption('WEBAPP_EXPORT.MANIFEST.DATASOURCES_USE_RELATIVE_URLS')) {
            $url = parse_url($url, PHP_URL_PATH);
        }
        $sourcesUxon->setProperty($dataSourceAlias, new UxonObject([
            'uri' => $url
        ]));
        $config->setOption('WEBAPP_EXPORT.MANIFEST.DATASOURCES', $sourcesUxon);
        
        $params = '';
        $serivceUrl = <<<JS
            function(){
                var sConfigUrl = {$this->getElement()->getController()->buildJsComponentGetter()}.getManifestEntry("/.../{$dataSourceAlias}/uri");
                return sConfigUrl || '{$url}';
            }()

JS;
        $auth = '';
        if ($connection->getUser()) {
            if ($this->getUseConnectionCredentials() === true) {
                $auth = "user: '{$connection->getUser()}',";
                $auth .= "password: '{$connection->getPassword()}',";
            }
            $auth .= "withCredentials: true,";
        }
        $metadataUrlParams = '';
        $serviceUrlParams = '';
        if ($fixedParams = $connection->getFixedUrlParams()) {     
            $fixedParamsArr = [];
            parse_str($fixedParams, $fixedParamsArr);
            $serviceUrlParams = json_encode(array_merge($params['serviceUrlParams'] ?? [], $fixedParamsArr));
            $metadataUrlParams = json_encode(array_merge($params['metadataUrlParams'] ?? [], $fixedParamsArr));
            $serviceUrlParamsJs = "serviceUrlParams: {$serviceUrlParams},";
            $metadataUrlParamsJs = "metadataUrlParams: {$metadataUrlParams}";
        }
        
        
        
        return <<<JS
                {
                    serviceUrl: {$serivceUrl},
                    {$auth}
                    {$serviceUrlParamsJs}
                    {$metadataUrlParamsJs}
                }

JS;
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
            throw new FacadeLogicError('Unsupported QueryBuilder used for object "' . $object->getName() . '" (' . $object->getAliasWithNamespace() . ')!');
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
        $bUseBatchJs = $this->getUseBatchWrites() ? 'true' : 'false';
        
        if ($action instanceof CreateData) {
            $serverCall = <<<JS
            
            oDataModel.create("/{$object->getDataAddress()}", oData, mParameters);

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

            oDataModel.update("/{$object->getDataAddress()}(" + oDataUid+ ")", oData, mParameters);

JS;
        }
        
        return <<<JS

            var oDataModel = new sap.ui.model.odata.v2.ODataModel({$this->getODataModelParams($object)});
            oDataModel.setUseBatch({$bUseBatchJs});
            var aResponses = [];
            var rowCount = {$oParamsJs}.data.rows.length;
            var mParameters = {};
            mParameters.groupId = "batchGroup";
            {$this->buildJsServerResponseHandling($onModelLoadedJs, 'mParameters', 'aResponses', 'rowCount')}
            
            for (var i = 0; i < rowCount; i++) {
                var data = {$oParamsJs}.data.rows[i];            
                var oData = {};
                Object.keys(data).forEach(key => {
                    if (data[key] != "") {
                        var type = {$attributesType}[key];
                        switch (type) {
                            case 'Edm.DateTimeOffset':
                                var d = exfTools.date.parse(data[key]);
                                var date = d.toISOString();
                                var datestring = date.replace(/\.[0-9]{3}/, '');
                                oData[key] = datestring;
                                break;
                            case 'Edm.DateTime':
                                var d = exfTools.date.parse(data[key]);                       
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
                    } else {
                        oData[key] = data[key];
                    }
                });
                {$serverCall}
            }
            {$this->buildJsServerSendRequest('oDataModel', $bUseBatchJs, $onModelLoadedJs, $onErrorJs)};

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
        $bUseBatchJs = $this->getUseBatchDeletes() ? 'true' : 'false';
        
        return <<<JS

            var oDataModel = new sap.ui.model.odata.v2.ODataModel({$this->getODataModelParams($object)});
            oDataModel.setUseBatch({$bUseBatchJs});
            var rowCount = {$oParamsJs}.data.rows.length;
            var aResponses = [];
            var mParameters = {};
            mParameters.groupId = "batchGroup";
            {$this->buildJsServerResponseHandling($onModelLoadedJs, 'mParameters', 'aResponses', 'rowCount')}
            
            for (var i = 0; i < rowCount; i++) {
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
                            var d = exfTools.date.parse(oDataUid);
                            var date = d.toISOString();
                            var datestring = date.replace(/\.[0-9]{3}/, '');
                            oDataUid = "'" + datestring + "'";
                            break;
                        case 'Edm.DateTime':
                            var d = exfTools.date.parse(oDataUid);
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
                oDataModel.remove("/{$object->getDataAddress()}(" + oDataUid + ")", mParameters);
            }

            {$this->buildJsServerSendRequest('oDataModel', $bUseBatchJs, $onModelLoadedJs, $onErrorJs)};

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
        $bUseBatchJs = $this->getUseBatchFunctionImports() ? 'true' : 'false';
        
        
        return <<<JS

            var oDataModel = new sap.ui.model.odata.v2.ODataModel({$this->getODataModelParams($object)});
            oDataModel.setUseBatch({$bUseBatchJs});
            var requiredParams = {$requiredParams};
            var defaultValues = {$defaultValues};
            var mParameters = {};
            var aResponses = [];
            var callActions = true;
            var oDataActionParams = {};
            var rowCount = {$oParamsJs}.data.rows.length;
            mParameters.groupId = "batchGroup";
            {$this->buildJsServerResponseHandling($onModelLoadedJs, 'mParameters', 'aResponses', 'rowCount')}

            if (rowCount !== 0) {                
                for (var j = 0; j < rowCount; j++) {
                    var addAction = true;
                    if (requiredParams[0] !== undefined) {
                        for (var i = 0; i < requiredParams.length; i++) {
                            var param = requiredParams[i];
                            if ({$oParamsJs}.data.rows[j][param] != undefined && {$oParamsJs}.data.rows[j][param] != "") {                           
                                var type = {$attributesType}[param];
                                var value = {$oParamsJs}.data.rows[j][param];
                                switch (type) {
                                    case 'Edm.DateTimeOffset':
                                        var d = exfTools.date.parse(value);
                                        var date = d.toISOString();
                                        var datestring = date.replace(/\.[0-9]{3}/, '');
                                        oDataActionParams[param] = datestring;
                                        break;
                                    case 'Edm.DateTime':
                                        var d = exfTools.date.parse(value);                            
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
                                addAction = false;
                                callActions = false;
                                console.error('No value given for required parameter: ', param);
                                {$this->getElement()->buildJsShowError('"No value given for parameter \"" + param + "\" at selected row: " + j', '"ERROR"')}
                                {$onErrorJs}
                            }
                        }
                    }
                    if (addAction === true) {                        
                        mParameters.urlParameters = oDataActionParams;
                        oDataModel.callFunction('/{$action->getServiceName()}', mParameters);
                    }
                }
                if (callActions === true) {                    
                    {$this->buildJsServerSendRequest('oDataModel', $bUseBatchJs, $onModelLoadedJs, $onErrorJs)};
                }
            } else {                
                {$this->getElement()->buildJsShowError('"No row selected!"', '"ERROR"')}
                {$onErrorJs}
            }
            
JS;
    }
    
    /**
     * 
     * @param string $oDataModelJs
     * @param string $bUseBatchJs
     * @param string $onModelLoadedJs
     * @param string $onErrorJs
     * @return string
     */
    protected function buildJsServerSendRequest (string $oDataModelJs, string $bUseBatchJs, string $onModelLoadedJs, string $onErrorJs) : string
    {
        return <<<JS

            {$oDataModelJs}.setDeferredGroups(["batchGroup"]);
            {$oDataModelJs}.submitChanges({
                groupId: "batchGroup",
                error: function(oError) {
                    {$onErrorJs}
                    {$this->buildJsServerResponseError('oError')}
                }
            });

JS;
    }

    /**
     * 
     * @param string $onModelLoadedJs
     * @param string $mParameters
     * @param string $aResponses
     * @param string $rowCount
     * @return string
     */
    protected function buildJsServerResponseHandling (string $onModelLoadedJs, string $mParameters = 'mParameters', string $aResponses = 'aResponses', string $rowCount = 'rowCount') :string
    {
        return <<<JS

            {$mParameters}.success = function(oData) {
                {$aResponses}.push(oData);
                if ({$aResponses}.length === {$rowCount}) {
                    {$onModelLoadedJs}
                }
            };
            {$mParameters}.error = function(oError) { 
                {$aResponses}.push(oError);
                {$this->buildJsServerResponseError('oError')} 
            };

JS;
    }
    
    /**
     *
     * @param string $oErrorJs
     * @return string
     */
    protected function buildJsServerResponseError(string $oErrorJs = 'oError') : string
    {
        return <<<JS
        
                var response = {};
                try {
                    response = $.parseJSON({$oErrorJs}.responseText);
                    var errorText = response.error.message.value;
                } catch (e) {
                    var errorText = 'No error description send!';
                }
                {$this->getElement()->buildJsShowError('errorText', "{$oErrorJs}.statusCode + ' ' + {$oErrorJs}.statusText")}
                
JS;
                
    }
    
    /**
     * 
     * @return bool
     */
    protected function getUseBatchDeletes() : bool
    {
        return $this->useBatchDeletes;
    }
    
    /**
     * Set if OData2ServerAdapter should use batch to send delete requests or should send each request seperately
     * 
     * @param bool $trueOrFalse
     * @return OData2ServerAdapter
     */
    public function setUseBatchDeletes(bool $trueOrFalse) : OData2ServerAdapter
    {
        $this->useBatchDeletes = $trueOrFalse;
        return $this;
    }
    
    /**
     *
     * @return bool
     */
    protected function getUseBatchWrites() : bool
    {
        return $this->useBatchWrites;
    }
    
    /**
     * Set if OData2ServerAdapter should use batch to send write requests or should send each request seperately
     *
     * @param bool $trueOrFalse
     * @return OData2ServerAdapter
     */
    public function setUseBatchWrites(bool $trueOrFalse) : OData2ServerAdapter
    {
        $this->useBatchWrites = $trueOrFalse;
        return $this;
    }
    
    /**
     *
     * @return bool
     */
    protected function getUseBatchFunctionImports() : bool
    {
        return $this->useBatchFunctionImports;
    }
    
    /**
     * Set if OData2ServerAdapter should use batch to send delete requests or should send each request seperately
     *
     * @param bool $trueOrFalse
     * @return OData2ServerAdapter
     */
    public function setUseBatchFunctionImports(bool $trueOrFalse) : OData2ServerAdapter
    {
        $this->useBatchFunctionImports = $trueOrFalse;
        return $this;
    }
}
