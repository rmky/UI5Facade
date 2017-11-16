<?php
namespace exface\OpenUI5Template\Template\Elements;

class ui5Tabs extends ui5Container
{

    public function generateJsConstructor()
    {
        return <<<JS
    new sap.m.IconTabBar("{$this->getId()}", {
        expanded: "{device>/isNoPhone}",
        /*stretchContentHeight: true,*/ // makes header of ObjectPage inivsible if set
        items: [
            {$this->buildJsChildrenConstructors()}
        ]
    })
JS;
    }
}
?>
