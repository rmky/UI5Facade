<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\Dialog;
use exface\Core\Interfaces\Widgets\iLayoutWidgets;
use exface\Core\Widgets\AbstractWidget;
use exface\Core\Interfaces\Widgets\iContainOtherWidgets;
use exface\Core\Factories\DataSheetFactory;

/**
 *
 * @method Dialog getWidget()
 *        
 * @author aka
 *        
 */
class ui5Dialog extends ui5Container
{
    public function generateJsConstructor()
    {
        return $this->buildJsPage('');
    }
    
    protected function buildJsPage($content)
    {
        return <<<JS
        
        new sap.m.Page("{$this->getId()}", {
            title: "{$this->getCaption()}",
            showNavButton: true,
            navButtonPress: function(){
                var oDialogStackTop = oDialogStack.pop();
        		oShell.removeAllContent();
                for (var i in oDialogStackTop.content) {
                    oShell.addContent(
                        oDialogStackTop.content[i]
                    );
                }
                oShell.removeAllHeadItems()
                for (var i in oDialogStackTop.head) {
                    oShell.addHeadItem(
                        oDialogStackTop.head[i]
                    );
                }
                oDialogStackTop.dialog.destroy(true);
                delete oDialogStackTop;
            },
            content: [
                {$this->buildJsObjectHeader()}
                {$this->buildJsChildrenConstructors()}
            ]
        }).addStyleClass("sapUiFioriObjectPage")
JS;
    }
                
    protected function buildJsObjectHeader()
    {
        if (($uid_widget = $this->getWidget()->findChildrenByAttribute($this->getMetaObject()->getUidAttribute())[0]) && !is_null($uid_widget->getValue())) {
            $uid_data_sheet = DataSheetFactory::createFromObject($this->getMetaObject());
            $uid_data_sheet->getColumns()->addFromAttribute($this->getMetaObject()->getLabelAttribute());
            $uid_data_sheet->addFilterFromString($this->getMetaObject()->getUidAttributeAlias(), $uid_widget->getValue());
            $uid_data_sheet->dataRead();
            $label = $uid_data_sheet->getCellValue($this->getMetaObject()->getLabelAttribute()->getAlias(), 0);
        }
        $heading = $this->getWidget()->getMetaObject()->getName() . ($label ? ': ' : '') . $label;
        return <<<JS

                new sap.m.ObjectHeader({
    				title: "{$heading}",
    				backgroundDesign: "Solid",
                    /*condensed: true,*/
                    responsive: true,
    				attributes: [

    				]
                }),

JS;
    }
                
    public function getViewName()
    {
        return 'view.' . $this->getId();
    }
}
?>