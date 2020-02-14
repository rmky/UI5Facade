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
use exface\Core\DataTypes\DateTimeDataType;
use exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface;
use exface\Core\Factories\DataTypeFactory;
use exface\Core\Interfaces\Model\CompoundAttributeInterface;


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
        $this->registerExternalModules($element);
    }
    
    /**
     * 
     * @param UI5AbstractElement $element
     * @return OData2ServerAdapter
     */
    protected function registerExternalModules(UI5AbstractElement $element) : OData2ServerAdapter
    {
        $dateTimeDataType = DataTypeFactory::createFromPrototype($element->getWorkbench(), DateTimeDataType::class);
        $dateTimeBindingFormatter = $element->getFacade()->getDataTypeFormatterForUI5Bindings($dateTimeDataType);
        $dateTimeBindingFormatter->registerExternalModules($element->getController());
        return $this;
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
        $test = '';
        switch (true) {
            case get_class($action) === ReadPrefill::class:
                return $this->buildJsPrefillLoader($oModelJs, $oParamsJs, $onModelLoadedJs, $onErrorJs, $onOfflineJs);
            case get_class($action) === ReadData::class:
            case get_class($action) === Autosuggest::class:
                return $this->buildJsDataLoader($oModelJs, $oParamsJs, $onModelLoadedJs, $onErrorJs, $onOfflineJs);
            case get_class($action) === CallOData2Operation::class:
                return $this->buildJsCallFunctionImport($action, $oModelJs, $oParamsJs, $onModelLoadedJs, $onErrorJs, $onOfflineJs);
            case get_class($action) === DeleteObject::class:
                return $this->buildJsDataDelete($action, $oModelJs, $oParamsJs, $onModelLoadedJs, $onErrorJs, $onOfflineJs);
            case get_class($action) === UpdateData::class:
                return $this->buildJsDataWrite($action, $oModelJs, $oParamsJs, $onModelLoadedJs, $onErrorJs, $onOfflineJs);
            case get_class($action) === CreateData::class:
                return $this->buildJsDataWrite($action, $oModelJs, $oParamsJs, $onModelLoadedJs, $onErrorJs, $onOfflineJs);
            case get_class($action) === SaveData::class:
                return $this->buildJsDataWrite($action, $oModelJs, $oParamsJs, $onModelLoadedJs, $onErrorJs, $onOfflineJs);
            default:
                //throw new UI5ExportUnsupportedActionException('Action "' . $action->getAliasWithNamespace() . '" cannot be used with Fiori export!');
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
        $compoundAttributes = $this->getCompoundAttributePropertiesForObject($object);
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
        $compoundAttributesJson = json_encode($compoundAttributes);
        
        $opISNOT = EXF_COMPARATOR_IS_NOT;
        $opEQ = EXF_COMPARATOR_EQUALS;
        $opNE = EXF_COMPARATOR_EQUALS_NOT;
        $dateTimeFormat = DateTimeDataType::DATETIME_ICU_FORMAT_INTERNAL;
        
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
            var compoundAttributes = {$compoundAttributesJson};
            
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
                    if (compoundAttributes[cond.expression] !== undefined) {
                        {$this->buildJsCompoundAttributeAddFilters('compoundAttributes', 'cond', 'oAttrsByDataType', 'oDataReadFiltersSearch', $onErrorJs)}
                    } else {
                        {$this->buildJsAddConditionToFilter('oAttrsByDataType', 'oDataReadFiltersSearch', 'cond')}
                    }

                    //QuickSearch
                    if ({$oParamsJs}.q !== undefined && {$oParamsJs}.q !== "" ) {
                        if (oQuickSearchFilters[0] !== undefined) {
                            if (oQuickSearchFilters.includes(cond.expression) && !oLocalFilters.includes(cond.expression)) {
                                if (compoundAttributes[cond.expression] !== undefined) {
                                    var compound = compoundAttributes[cond.expression];
                                    var alias = compound.aliases;
                                    for (var j = 0; j < aliases.length; j++) {
                                        var filterQuickSearchItem = new sap.ui.model.Filter({
                                            path: aliases[j],
                                            operator: "Contains",
                                            value1: {$oParamsJs}.q
                                        });
                                        oDataReadFiltersQuickSearch.push(filterQuickSearchItem);
                                    }
                                } else {
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
            }

            // Settings menu Filters 
            if ({$oParamsJs}.data && {$oParamsJs}.data.filters && {$oParamsJs}.data.filters.nested_groups) {
                var groupsCount = {$oParamsJs}.data.filters.nested_groups.length;
                for (var j = 0; j < groupsCount; j++) {
                    var conditions = {$oParamsJs}.data.filters.nested_groups[j].conditions 
                    var conditionsCount = conditions.length;              
                    for (var i = 0; i < conditionsCount; i++) {
                        var cond = conditions[i];
                        if (compoundAttributes[cond.expression] !== undefined) {                        
                            {$this->buildJsCompoundAttributeAddFilters('compoundAttributes', 'cond', 'oAttrsByDataType', 'oDataReadFiltersTempGroup', $onErrorJs)}
                        } else {                        
                            {$this->buildJsAddConditionToFilter('oAttrsByDataType', 'oDataReadFiltersTempGroup', 'cond')}
                        }
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
                    var sorter = sorters[i];
                    var direction = directions[i];
                    if (compoundAttributes[sorter] !== undefined) {
                        var comp = compoundAttributes[sorter];
                        var compAliases = comp['aliases'];
                        for (var i = 0; i < compAliases.length; i++) {
                            var alias = compAliases[i];
                            if (direction === "desc") {
                                var sortObject = new sap.ui.model.Sorter(alias, true);
                            } else {
                                var sortObject = new sap.ui.model.Sorter(alias, false);
                            }
                            oDataReadSorters.push(sortObject);
                            
                        }
                    } else {
                        if (direction === "desc") {
                            var sortObject = new sap.ui.model.Sorter(sorter, true);
                        } else {
                            var sortObject = new sap.ui.model.Sorter(sorter, false);
                        }
                        oDataReadSorters.push(sortObject);
                    }
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
                                    var date = moment.utc(d);
                                    var newVal = exfTools.date.format(date, '{$dateTimeFormat}');                                 
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

                    //adding compound attribute columns
                    if (Object.keys(compoundAttributes).length > 0) {
                         for (var i = 0; i < resultRows.length; i++) {
                            var row = resultRows[i];
                            {$this->buildJsCompoundAttributeMergeValues('compoundAttributes', 'row')}
                            resultRows[i] = row;
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
                    var test = {$oModelJs}.getData();
                    {$onModelLoadedJs}
                },
                error: function(oError){                    
                    {$this->buildJsServerResponseError('oError')}
                    {$onErrorJs}
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
                    if ({$condJs}.value !== "" && {$condJs}.value !== undefined) {
                        var filterPush = true;
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
                            var d = exfTools.date.parse({$condJs}.value);
                            if (d === null) {
                                filterPush = false;
                            } else {
                                var date = d.toISOString();
                                var datestring = date.replace(/\.[0-9]{3}/, '');
                                var value = datestring;
                                if (sOperator === "Contains") {
                                    sOperator = "EQ";
                                }
                            }
                        } else {
                            value = {$condJs}.value;
                        }
                        if (filterPush === true) {
                            var filter = new sap.ui.model.Filter({
                                path: {$condJs}.expression,
                                operator: sOperator,
                                value1: value
                            });
                            {$filterArrayJs}.push(filter);
                        }
                    }

JS;
    }
    
    protected function buildJsCompoundAttributeAddFilters(string $compoundAttributesJs, string $conditionJs, string $oAttrsByDataTypeJs, string $filterJs, string $onErrorJs) : string
    {
        return <<<JS
            var compound = {$compoundAttributesJs}[{$conditionJs}.expression];
            var value = {$conditionJs}.value;
            var delimiter = compound.delimiter;
            var splitValues = [];
            {$this->buildJsCompoundAttributeSplitValue('value', 'delimiter', 'splitValues', $onErrorJs)}
            var aliases = compound.aliases;
            if (aliases.length !== splitValues.length) {
                var error = "Can not filter compound \"" + cond.expression + "\": amount of split values does not fit amount of components!";
                {$this->getElement()->buildJsShowError('error', '"ERROR"')};
                {$onErrorJs}
            }
            for (var j = 0; j < aliases.length; j++) {
                var splitCond = [];
                splitCond['value'] = splitValues[j];
                splitCond['comparator'] = {$conditionJs}.comparator;
                splitCond['expression'] = aliases[j];
                {$this->buildJsAddConditionToFilter($oAttrsByDataTypeJs, $filterJs, 'splitCond')}
            }
JS;
    }
    
    /**
     * Build JS to split a compound attribute value into an array of the value split by the delimiters
     * 
     * @param string $valueJs
     * @param string $delimiterJs
     * @param string $splitValuesArrayJs
     * @return string
     */
    protected function buildJsCompoundAttributeSplitValue(string $valueJs, string $delimiterJs, string $splitValuesArrayJs, string $onErrorJs) : string
    {
        return <<<JS
        
                //cut off first prefix
                var compValue = {$valueJs};
                compValue = compValue.substring({$delimiterJs}[0].length, compValue.length);
                for (var j = 1; j < {$delimiterJs}.length; j++) {
                    var array = compValue.split({$delimiterJs}[j]);
                    array.push(array.splice(1).join({$delimiterJs}[j]));
                    {$splitValuesArrayJs}.push(array[0]);
                    compValue = array[1];
                }
                if (compValue !== '') {
                    var error = "Can not split compound attribute value \"{$valueJs}\": non-empty remainder \"" + compValue + "\" after processing all components";
                    {$this->getElement()->buildJsShowError('error', '"ERROR"')};
                    {$onErrorJs}
                }
JS;
    }
    
    protected function buildJsCompoundAttributeMergeValues(string $compoundAttributesJs, string $rowJs) : string
    {
        return <<<JS

                            var compAttr = Object.keys({$compoundAttributesJs});
                            for (var k = 0; k < compAttr.length; k++) {
                                var compAlias = compAttr[k];
                                var comp = {$compoundAttributesJs}[compAlias];
                                var splitAliases = comp['aliases'];
                                var delim = comp['delimiter'];
                                var value = '';
                                for (var l = 0; l < splitAliases.length; l++) {
                                    var col = splitAliases[l]
                                    value += delim[l] + {$rowJs}[col];
                                }
                                var delimCount = delim.length;
                                value += delim[delimCount-1];
                                {$rowJs}[compAlias] = value;
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
        $filters = '';
        $check = '';
        
        if ($uidAttr instanceof CompoundAttributeInterface) {
            foreach($uidAttr->getComponents() as $comp) {
                $check .= <<<JS
            
            var value = oFirstRow["{$comp->getAttribute()->getAlias()}"];
            if (value === undefined) {
                var error = "Can not set prefill filters: Make sure prefill data contains value for attribute \"{$comp->getAttribute()->getAlias()}\" of object \"{$object->getAlias()}\" !";
                {$this->getElement()->buildJsShowError('error', '"ERROR"')}
                {$onErrorJs}
            }
JS;
                
                $filters .= <<<JS

                    {
                        comparator: "{$opEQ}",
                        expression: "{$comp->getAttribute()->getAlias()}",
                        object_alias: "{$object->getAliasWithNamespace()}",
                        value: oFirstRow["{$comp->getAttribute()->getAlias()}"]
                    },
JS;
            }            
        } else {
            $check = <<<JS
            
            var value = oFirstRow["{$uidAttr->getAlias()}"];
            if (value === undefined) {
                var error = "Can not set prefill filters: Make sure prefill data contains value for attribute \"{$uidAttr->getAlias()}\" of object \"{$object->getAlias()}\" !";
                {$this->getElement()->buildJsShowError('error', '"ERROR"')}
                {$onErrorJs}
            }
JS;
            
            $filters .= <<<JS

                    {
                        comparator: "{$opEQ}",
                        expression: "{$uidAttr->getAlias()}",
                        object_alias: "{$object->getAliasWithNamespace()}",
                        value: oFirstRow["{$uidAttr->getAlias()}"]
                    }
JS;
        }
        
        return <<<JS
        
            var oFirstRow = {$oParamsJs}.data.rows[0];
            if (oFirstRow === undefined) {
                console.error('No data to filter the prefill!');
                {$this->getElement()->buildJsShowError('"No data to filter the prefill!"', '"ERROR"')}
                {$onErrorJs}
            }
            {$check}

            {$oParamsJs}.data.filters = {
                conditions: [
                    {$filters}
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
     * Build an array containing properties for each compound attribute of an object with the alias of the compound attribute as key.
     * Properties are `aliases` of the compound attribute components and `delimiter`.
     *
     * 
     * @param MetaObjectInterface $object
     * @return array
     */
    protected function getCompoundAttributePropertiesForObject (MetaObjectInterface $object) : array
    {
        $compoundAttributes = [];
        foreach ($object->getAttributes() as $qpart) {
            if ($qpart instanceof CompoundAttributeInterface) {
                $components = $qpart->getComponents();
                $properties = [];
                $aliases = [];
                $delimiter = [];
                foreach ($components as $comp) {
                    $aliases[] = $comp->getAttribute()->getAlias();
                }
                $properties['aliases'] = $aliases;
                $delimiter[] = $components[0]->getValuePrefix() ?? '';
                foreach ($qpart->getComponentDelimiters() as $del) {
                    $delimiter[] = $del;
                }
                $delimiter[] = $components[count($components)-1]->getValueSuffix() ?? '';
                $properties['delimiter'] = $delimiter;
                $compoundAttributes[$qpart->getAlias()] = $properties;
            }            
        }
        return $compoundAttributes;
    }
    
    protected function buildJsGetODataValue (string $valueJs, string $typeJs, string $oDataValueJs) : string
    {
        return <<<JS

                            switch ({$typeJs}) {
                                case 'Edm.DateTimeOffset':
                                    var d = exfTools.date.parse({$valueJs});
                                    var datestring = exfTools.date.format(d,'yyyy-MM-ddTHH:mm:ss')
                                    {$oDataValueJs} = datestring;
                                    break;
                                case 'Edm.DateTime':
                                    var d = exfTools.date.parse({$valueJs}); 
                                    var datestring = exfTools.date.format(d,'yyyy-MM-ddTHH:mm:ss')
                                    {$oDataValueJs} = datestring;
                                    break;                        
                                case 'Edm.Time':
                                    var d = {$valueJs};
                                    var timeParts = d.split(':');
                                    if (timeParts[3] === undefined || timeParts[3]=== null || timeParts[3] === "") {
                                        timeParts[3] = "00";
                                    }
                                    for (var i = 0; i < timeParts.length; i++) {
                                        timeParts[i] = ('0'+(timeParts[i])).slice(-2);
                                    }                            
                                    var timeString = "PT" + timeParts[0] + "H" + timeParts[1] + "M" + timeParts[3] + "S";
                                    {$oDataValueJs} = timeString;
                                    break;
                                case 'Edm.Decimal':
                                    {$oDataValueJs} = {$valueJs}.toString();
                                    break; 
                                case 'Edm.Boolean':
                                    {$oDataValueJs} = ({$valueJs} === 'true');
                                    break; 
                                default:
                                    {$oDataValueJs} = {$valueJs};
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
    protected function buildJsDataWrite(ActionInterface $action, string $oModelJs, string $oParamsJs, string $onModelLoadedJs, string $onErrorJs = '', string $onOfflineJs = '') : string
    {
        $widget = $this->getElement()->getWidget();
        $object = $widget->getMetaObject();
        $uidAttribute = $object->getUidAttribute();
        $uidAttributeAlias = $object->getUidAttributeAlias();
        $uidAliases = [];
        $compoundAttributes = $this->getCompoundAttributePropertiesForObject($object);
        $compoundAttributesJson = json_encode($compoundAttributes);
        if ($uidAttribute instanceof CompoundAttributeInterface) {
            foreach ($uidAttribute->getComponents() as $comp) {
                $uidAliases[] = $comp->getAttribute()->getAlias();
            }
        }
        $attributes = $object->getAttributes();
        $attributesType = (object)array();
        foreach ($attributes as $attr) {
            if (! ($attr instanceof CompoundAttributeInterface)) {
                $key = $attr->getAlias();
                $attributesType->$key= $attr->getDataAddressProperty('odata_type');
            }
        }
        $attributesType = json_encode($attributesType);
        $uidAliasesJson = json_encode($uidAliases);
        $bUseBatchJs = $this->getUseBatchWrites() ? 'true' : 'false';
        
        if ($action instanceof CreateData) {
            $serverCall = <<<JS
            
            oDataModel.create("/{$object->getDataAddress()}", oData, mParameters);

JS;
        }
        elseif ($action instanceof UpdateData || $action instanceof SaveData) {
            
            $serverCall = <<<JS
            
            var oDataUid = '';
            {$this->buildJsGetUrlUidValue($action, 'uidAlias', 'uidAliases', 'attributesType', 'compoundAttributes', 'data', 'oDataUid', $onErrorJs)}
            oDataModel.update("/{$object->getDataAddress()}(" + oDataUid + ")", oData, mParameters);

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

            var uidAliases = {$uidAliasesJson};
            var uidAlias = '{$uidAttributeAlias}';            
            var compoundAttributes = {$compoundAttributesJson};
            var attributesType = {$attributesType};
            
            for (var i = 0; i < rowCount; i++) {
                var data = {$oParamsJs}.data.rows[i];            
                var oData = {};
                Object.keys(data).forEach(key => {
                    if ({$attributesType}[key] !== undefined) {
                        if (data[key] !== "") {
                            var type = {$attributesType}[key];
                            var value = data[key];
                            var oDataValue = '';
                            {$this->buildJsGetODataValue('value', 'type', 'oDataValue')}
                            oData[key] = oDataValue;
                        }
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
    protected function buildJsDataDelete(ActionInterface $action, string $oModelJs, string $oParamsJs, string $onModelLoadedJs, string $onErrorJs = '', string $onOfflineJs = '') : string
    {
        $widget = $this->getElement()->getWidget();
        $object = $widget->getMetaObject();
        $uidAttribute = $object->getUidAttribute();
        $uidAttributeAlias = $uidAttribute->getUidAttributeAlias();
        $uidAliases = [];
        $attributesType = (object)array();
        $compoundAttributes = $this->getCompoundAttributePropertiesForObject($object);
        $compoundAttributesJson = json_encode($compoundAttributes);
        if ($uidAttribute instanceof CompoundAttributeInterface) {
            foreach ($uidAttribute->getComponents() as $comp) {
                $alias = $comp->getAttribute()->getAlias();
                $uidAliases[] = $alias;
                $attributesType->$alias = $comp->getAttribute()->getDataAddressProperty('odata_type');
            }
        } else {
            $attributesType->$uidAttributeAlias = $uidAttribute->getDataAddressProperty('odata_type');
        }
        $attributesType = json_encode($attributesType);
        $uidAliasesJson = json_encode($uidAliases);
        $bUseBatchJs = $this->getUseBatchDeletes() ? 'true' : 'false';
        
        return <<<JS
            
            var oDataModel = new sap.ui.model.odata.v2.ODataModel({$this->getODataModelParams($object)});
            oDataModel.setUseBatch({$bUseBatchJs});
            var rowCount = {$oParamsJs}.data.rows.length;
            var aResponses = [];
            var mParameters = {};
            mParameters.groupId = "batchGroup";
            {$this->buildJsServerResponseHandling($onModelLoadedJs, 'mParameters', 'aResponses', 'rowCount')}

            var uidAliases = {$uidAliasesJson};
            var uidAlias = '{$uidAttributeAlias}';
            var attributesType = {$attributesType};
            var compoundAttributes = {$compoundAttributesJson};
            var 
            
            for (var i = 0; i < rowCount; i++) {
                var data = {$oParamsJs}.data.rows[i];
                var oDataUid = '';
                {$this->buildJsGetUrlUidValue($action, 'uidAlias', 'uidAliases', 'attributesType', 'compoundAttributes', 'data', 'oDataUid', $onErrorJs)}            
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
                                var oDataValue = '';
                                {$this->buildJsGetODataValue('value', 'type', 'oDataValue')}
                                oDataActionParams[param] = oDataValue;
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
     * Get the string part for the UID in the URL.
     * 
     * @param string $uidAliasJs
     * @param string $uidAliasJs
     * @param string $attributesTypeJs
     * @param string $compoundAttributesJs
     * @param string $dataJs
     * @return string
     */
    protected function buildJsGetUrlUidValue(ActionInterface $action, string $uidAliasJs, string $uidAliasesJs, string $attributesTypeJs, string $compoundAttributesJs, string $dataJs, string $uidStringJs, string $onErrorJs) : string
    {
        return <<<JS

        if ({$uidAliasesJs}.length === 0) {
                if ({$uidAliasJs} in {$dataJs}) {
                    var value = {$dataJs}[{$uidAliasJs}];
                    var oDataValue = ''
                    var type = {$attributesTypeJs}[{$uidAliasJs}];                    
                    {$this->buildJsGetODataValue('value', 'type', 'oDataValue')}
                    switch (type) {
                        case 'Edm.Guid':
                            {$uidStringJs} = "guid" + "'" + oDataValue + "'";
                            break;
                        case 'Edm.Binary':
                            {$uidStringJs} = "binary" + "'" + oDataValue + "'";
                            break;
                        default:
                            {$uidStringJs} = "'" + oDataValue + "'";
                    }
                } else {
                    var {$uidStringJs} = '';
                }
            } else {
                var value = {$dataJs}[{$uidAliasJs}];
                var splitValues = [];
                var delimiter = compoundAttributes[{$uidAliasJs}]['delimiter'];
                {$this->buildJsCompoundAttributeSplitValue('value', 'delimiter', 'splitValues', $onErrorJs)}
                if ({$uidAliasesJs}.length !== splitValues.length) {
                    var error = "Can not perform action \"{$action->getName()}\": amount of split values for attribute \"{$uidAliasJs}\" does not fit amount of components!";                    
                    {$this->getElement()->buildJsShowError('error', '"ERROR"')};
                    {$onErrorJs}
                }
                var oDataUid = '';
                for (var i = 0; i < {$uidAliasesJs}.length; i++) {
                    var uidAlias = {$uidAliasesJs}[i];
                    var type = {$attributesTypeJs}[uidAlias];
                    var uidPart = uidAlias + '=';
                    var value = splitValues[i];
                    var oDataValue = '';
                    {$this->buildJsGetODataValue('value', 'type', 'oDataValue')}
                    switch (type) {
                        case 'Edm.Guid':
                            uidPart += "guid" + "'" + oDataValue + "'";
                            break;
                        case 'Edm.Binary':
                            uidPart += "binary" + "'" + oDataValue + "'";
                            break;
                        default:
                            uidPart += "'" + oDataValue + "'";
                    }
                    {$uidStringJs} += uidPart + ',';                    
                }
                {$uidStringJs} = {$uidStringJs}.substr(0, {$uidStringJs}.length - 1);               
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
                success: function() {                    
                },
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
                    var response = {};
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
                    var response = $.parseJSON({$oErrorJs}.responseText);
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
