<?php
namespace exface\UI5Facade\Facades\Elements;

class UI5LoginPrompt extends UI5Container
{

    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Container::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $iconTabBar = $this->buildJsIconTabBar($oControllerJs);
        $captionJs = json_encode($this->getCaption());
        
        $panel = <<<JS

    new sap.m.Panel({
        headerText: $captionJs,
        content: [
            $iconTabBar
        ]
    }).addStyleClass('sapUiNoContentPadding exf-loginprompt-panel')

JS;
        if ($this->getView()->isWebAppRoot() === true) {
            return $this->buildJsCenterWrapper($panel);
        } else {
            return $panel;
        }
    }
            
    protected function buildJsIconTabBar(string $oControllerJs) : string
    {
        return <<<JS
            new sap.m.IconTabBar("{$this->getId()}", {
                showOverflowSelectList: true,
                stretchContentHeight: false,
                items: [
                    {$this->buildJsChildrenConstructors($oControllerJs)}
                ]
            })
JS;
    }
    
    public function buildJsChildrenConstructors(string $oControllerJs = 'oController') : string
    {
        $js = '';
        foreach ($this->getWidget()->getWidgets() as $loginForm) {
            $formEl = $this->getFacade()->getElement($loginForm);
            $js .= $this->buildJsIconTabFilter($formEl, $oControllerJs) . ',';
        }
        return $js;
    }
    
    
    
    /**
     *
     * @return string
     */
    protected function buildJsIconTabFilter(UI5Form $loginFormElement, string $oControllerJs) : string
    {
        $caption = json_encode($loginFormElement->getCaption());
        return <<<JS
                    new sap.m.IconTabFilter({
                        text: {$caption},
                        content: [
                            {$loginFormElement->buildJsConstructor($oControllerJs)}
                        ]
                    })
JS;
    }
    
    
    
    protected function buildJsCenterWrapper(string $content) : string
    {
        return <<<JS
        
                        new sap.m.FlexBox({
                            height: "100%",
                            width: "100%",
                            justifyContent: "Center",
                            alignItems: "Center",
                            items: [
                                {$content}
                            ]
                        }).addStyleClass('exf-loginprompt-flexbox')
                        
JS;
    }
}
