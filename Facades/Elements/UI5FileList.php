<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\FileList;
use exface\UI5Facade\Facades\Elements\Traits\UI5DataElementTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryDataTableTrait;
use exface\Core\Factories\ActionFactory;
use exface\Core\Actions\SaveData;
use exface\Core\Widgets\DataColumn;
use exface\Core\DataTypes\BinaryDataType;
use exface\Core\DataTypes\WidgetVisibilityDataType;
use exface\Core\Actions\DeleteObject;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Interfaces\Actions\iReadData;

/**
 * Generates sap.m.upload.UploadSet for a FileList widget.
 * 
 * @method FileList getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5FileList extends UI5AbstractElement
{
    const EVENT_NAME_AFTER_ITEM_ADDED = 'afterItemAdded';
    const EVENT_NAME_BEFORE_ITEM_REMOVED = 'beforeItemRemoved';
    
    use UI5DataElementTrait {
        buildJsDataLoaderOnLoaded as buildJsDataLoaderOnLoadedViaTrait;
    }
    
    use JqueryDataTableTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructorForControl($oControllerJs = 'oController') : string
    {
        $widget = $this->getWidget();
        
        $controller = $this->getController();
        $controller->addOnEventScript($this, self::EVENT_NAME_AFTER_ITEM_ADDED, $this->buildJsEventHandlerUpload('oEvent'));
        $controller->addOnEventScript($this, self::EVENT_NAME_BEFORE_ITEM_REMOVED, $this->buildJsEventHandlerDelete('oEvent'));
        
        $specialCols = [
            $widget->getFilenameColumn(),
            $widget->getMimeTypeColumn(),
            $widget->getFileContentColumn(),
        ];
        if ($widget->hasDownloadUrlColumn()) {
            $specialCols[] = $widget->getDownloadUrlColumn();
        }
        if ($widget->hasThumbnailColumn()) {
            $specialCols[] = $widget->getThumbnailColumn();
        }
        
        $attributesConstructors = '';
        foreach ($widget->getColumns() as $col) {
            if (in_array($col, $specialCols) || $col->isHidden()) {
                continue;
            }
            
            $cellWidget = $col->getCellWidget();
            if ($col->getVisibility() === WidgetVisibilityDataType::OPTIONAL) {
                $cellWidget->setHidden(true);
            }
            
            $objectAttribute = new UI5ObjectAttribute($cellWidget, $this->getFacade());
            $objectAttribute->setValueBindingPrefix('');
            $attributesConstructors .= $objectAttribute->buildJsConstructor($oControllerJs) . ',';
        }
        
        $uploadEnabled = $widget->isUploadEnabled() ? 'true' : 'false';
        $maxFilenameLength = $widget->getUploader()->getMaxFilenameLength() ?? 'null';
        
        return <<<JS

        new sap.m.upload.UploadSet('{$this->getId()}', {
            uploadEnabled: {$uploadEnabled},
    		terminationEnabled: true,
    		showIcons: true,
            {$this->buildJsPropertyFileTypes()}
            {$this->buildJsPropertyMediaTypes()}
    		maxFileNameLength: {$maxFilenameLength},
    		maxFileSize: {$widget->getUploader()->getMaxFileSizeMb()},
            afterItemAdded: {$controller->buildJsEventHandler($this, self::EVENT_NAME_AFTER_ITEM_ADDED, true)},
            beforeItemRemoved: {$controller->buildJsEventHandler($this, self::EVENT_NAME_BEFORE_ITEM_REMOVED, true)},
            items: {
    			path: '/rows',
                template: new sap.m.upload.UploadSetItem({
                    fileName: "{{$widget->getFilenameColumn()->getDataColumnName()}}",
					mediaType: "{{$widget->getMimeTypeColumn()->getDataColumnName()}}",
                    visibleEdit: false,
					{$this->buildJsItemPropertyUrl()}
					{$this->buildJsItemPropertyThumbnail()}
					attributes: [
                        $attributesConstructors
					]
                })
    		},
            toolbar: {$this->buildJsToolbar($oControllerJs)}
        })

JS;
    }
    
    protected function buildJsPropertyFileTypes() : string
    {
        $types = $this->getWidget()->getUploader()->getAllowedFileExtensions();
        if (! empty($types)) {
            return 'fileTypes: "' . mb_strtolower(implode(',', $types)) . '",';
        }
        return '';
    }
    
    protected function buildJsPropertyMediaTypes() : string
    {
        $types = $this->getWidget()->getUploader()->getAllowedMimeTypes();
        if (! empty($types)) {
            return 'mediaTypes: "' . mb_strtolower(implode(',', $types)) . '",';
        }
        return '';
    }
    
    protected function buildJsEventHandlerUpload(string $oEventJs) : string
    {
        $widget = $this->getWidget();
        $uploadAction = ActionFactory::createFromString($this->getWorkbench(), SaveData::class, $widget);
        
        $fileModificationColumnJs = '';
        if ($widget->hasFileModificationTimeColumn()) {
            $fileModificationColumnJs = "{$widget->getMimeTypeColumn()->getDataColumnName()}: file.lastModified,";
        }
        
        $onUploadCompleteJs = <<<JS
        
            {$this->buildJsRefresh()};
            try {
                oUploadSet.removeIncompleteItem(oItem);
            } catch (e) {
                // silence errors - the data will be refreshed anyway.
            }
JS;
            
        return <<<JS

                var oItem = $oEventJs.getParameters().item;
                var oUploadSet = $oEventJs.getSource();

                var file = oItem.getFileObject();
                var fileReader = new FileReader( );

                // Check extension
                var sError;
                var aFileTypes = oUploadSet.getFileTypes();
                if (aFileTypes && aFileTypes.length > 0) {
                    var fileExt = (/(?:\.([^.]+))?$/).exec(file.name)[1];
                    if (! aFileTypes.includes(fileExt)) {
                        sError = "{$this->translate('WIDGET.FILELIST.ERROR_EXTENSION_NOT_ALLOWED', ['%ext%' => ' +"\"" + fileExt  + "\"" + '])}";
                    }
                }
                // Check mime type
                var aMediaTypes = oUploadSet.getMediaTypes();
                if (aMediaTypes && aMediaTypes.length > 0) {
                    if (! aMediaTypes.includes(file.type)) {
                        sError = "{$this->translate('WIDGET.FILELIST.ERROR_MIMETYPE_NOT_ALLOWED', ['%type%' => ' +"\"" + file.type  + "\"" + '])}";
                    }
                }
                // Check size
                var iMaxSize = oUploadSet.getMaxFileSize();
                if (iMaxSize && iMaxSize > 0) {
                    if (iMaxSize * 1000000 < file.size) {
                        sError = "{$this->translate('WIDGET.FILELIST.ERROR_FILE_TOO_BIG', ['%mb%' => '" + iMaxSize + "'])}";
                    }
                }
                // Check filename length
                var iMaxLength = oUploadSet.getMaxFileNameLength();
                if (iMaxLength && iMaxLength > 0) {
                    if (iMaxLength < file.name.length) {
                        sError = "{$this->translate('WIDGET.FILELIST.ERROR_FILE_NAME_TOO_LONG', ['%length%' => '" + iMaxLength + "'])}";
                    }
                }
                if (sError !== undefined) {
                    {$this->buildJsShowError('sError')}
                    oUploadSet.removeIncompleteItem(oItem);
                    return;
                }

                fileReader.onload = function () { 
                    var sContent = {$this->buildJsFileContentEncoder($widget->getFileContentColumn(), 'fileReader.result')};

                    var oResponseModel = new sap.ui.model.json.JSONModel({
                        oId: "{$widget->getMetaObject()->getId()}",
                        rows: [
                            {
                                {$widget->getFilenameColumn()->getDataColumnName()}: file.name,
                                {$widget->getMimeTypeColumn()->getDataColumnName()}: file.type,
                                {$widget->getFileContentColumn()->getDataColumnName()}: sContent,
                                {$fileModificationColumnJs}
                            }
                        ] 
                    });
                    {$this->buildJsDataLoaderOnLoadedHandleWidgetLinks('oResponseModel')}
                    var oUploadParams = {
                        action: "{$uploadAction->getAliasWithNamespace()}",
    					resource: "{$this->getPageId()}",
    					element: "{$widget->getId()}",
    					object: "{$widget->getMetaObject()->getId()}",
                        data: oResponseModel.getData()
                    };
                    {$this->getServerAdapter()->buildJsServerRequest($uploadAction, 'oResponseModel', 'oUploadParams', $onUploadCompleteJs, $onUploadCompleteJs)}
                };
                fileReader.readAsBinaryString(file);
JS;
    }
    
    protected function buildJsEventHandlerDelete(string $oEventJs) : string
    {
        $widget = $this->getWidget();
        
        $this->getController()->addOnInitScript("sap.ui.getCore().byId('{$this->getId()}').getList().setMode(sap.m.ListMode.SingleSelectMaster);");
        
        $deleteAction = ActionFactory::createFromString($this->getWorkbench(), DeleteObject::class, $widget);
        
        return <<<JS

                var oItem = $oEventJs.getParameters().item; 
                var oUploadSet = $oEventJs.getSource();
                var oContext = oItem.getBindingContext();
                var bError = false;
                var oRow;

                if (oContext === undefined) {
                    bError = true;
                } else {
                    oRow = oContext.getObject();
                    if (oRow === undefined) bError = true;
                }
                if (bError === true) {
                    {$this->buildJsShowError('"' . $this->translate('WIDGET.FILELIST.ERROR_DELETE') . '"')};
                    return;
                }

                setTimeout(function() {
                    var oConfDialog = sap.ui.getCore().byId(oUploadSet.getId() + '-deleteDialog');
                    var oButtonOK = oConfDialog.getButtons()[0];
                    oButtonOK.setType(sap.m.ButtonType.Emphasized);
                    oButtonOK.attachPress(function(oEventPress){
                        var oResponseModel = new sap.ui.model.json.JSONModel();
                        var oParams = {
                            action: "{$deleteAction->getAliasWithNamespace()}",
        					resource: "{$this->getPageId()}",
        					element: "{$widget->getId()}",
        					object: "{$widget->getMetaObject()->getId()}",
                            data: {
                                oId: "{$widget->getMetaObject()->getId()}",
                                rows: [
                                    oItem.getBindingContext().getObject()
                                ] 
                            }
                        }
                        {$this->getServerAdapter()->buildJsServerRequest($deleteAction, 'oResponseModel', 'oParams', $this->buildJsRefresh())}
                    });
                },0);
JS;
    }
    
    protected function buildJsFileContentEncoder(DataColumn $contentCol, string $fileContentJs) : string
    {
        $type = $contentCol->getDataType();
        switch (true) {
            case $type instanceof BinaryDataType && $type->getEncoding() === BinaryDataType::ENCODING_BASE64:
                return "btoa($fileContentJs)";
            case $type instanceof BinaryDataType && $type->getEncoding() === BinaryDataType::ENCODING_HEX:
                return <<<JS

                    function (s){
                        var v,i, f = 0, a = [];  
                        s += '';  
                        f = s.length;  
                          
                        for (i = 0; i<f; i++) {  
                            a[i] = s.charCodeAt(i).toString(16).replace(/^([\da-f])$/,"0$1");  
                        }  
                          
                        return 'x' + a.join('');  
                    }($fileContentJs); 
JS;
        }
        return $fileContentJs;
    }
    
    protected function isEditable() : bool
    {
        return false;
    }
    
    protected function buildJsItemPropertyThumbnail() : string
    {
        $widget = $this->getWidget();
        if ($widget->hasThumbnailColumn()) {
            return "thumbnailUrl: '{{$widget->getThumbnailColumn()->getDataColumnName()}}',";
        }
        return '';
    }
    
    protected function buildJsItemPropertyUrl() : string
    {
        $widget = $this->getWidget();
        if ($widget->hasDownloadUrlColumn()) {
            return "url: '{{$widget->getDownloadUrlColumn()->getDataColumnName()}}',";
        }
        return '';
    }
    
    protected function buildJsDataLoaderOnLoaded(string $oModelJs = 'oModel') : string
    {
        $widget = $this->getWidget();
        
        if ($widget->isUploadEnabled() && ($maxFiles = $widget->getUploader()->getMaxFiles()) > 0) {
            $checkMaxFilesJs = <<<JS

            (function(){
                var oUploadSet = sap.ui.getCore().byId('{$this->getId()}');
                if ($oModelJs.getData() && $oModelJs.getData().rows && $oModelJs.getData().rows.length < $maxFiles) {
                    oUploadSet.setUploadEnabled(true);
                } else {
                    oUploadSet.setUploadEnabled(false);
                }
            })();
JS;
        }
        
        return $this->buildJsDataLoaderOnLoadedViaTrait($oModelJs)
                . $checkMaxFilesJs;
    }
    
    public function buildJsDataGetter(ActionInterface $action = null)
    {
        if ($action === null) {
            $rows = "sap.ui.getCore().byId('{$this->getId()}').getModel().getData().rows";
        } elseif ($action instanceof iReadData) {
            // If we are reading, than we need the special data from the configurator
            // widget: filters, sorters, etc.
            return $this->getConfiguratorElement()->buildJsDataGetter($action);
        } else {
            $rows = '[];' . <<<JS
                
        var aSelectedItems = oTable.getSelectedItems();
        var oModelData = oTable.getModel().getData();
        aSelectedItems.forEach(function(oItem){
            rows.push(oModelData.rows[oTable.indexOfItem(oItem)]);
        });
        
JS;
        }
        return <<<JS
    function() {
        var oTable = sap.ui.getCore().byId('{$this->getId()}').getList();
        var rows = {$rows};
        return {
            oId: '{$this->getWidget()->getMetaObject()->getId()}',
            rows: (rows === undefined ? [] : rows)
        };
    }()
JS;
    }
}