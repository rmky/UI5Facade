<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Interfaces\Widgets\iFillEntireContainer;
use exface\UI5Facade\Facades\Interfaces\UI5ControlWithToolbarInterface;
use exface\Core\Widgets\Panel;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryLayoutTrait;
use exface\UI5Facade\Facades\Elements\Traits\UI5HelpButtonTrait;
use exface\Core\Widgets\Card;

/**
 * Generates a sap.f.Card for a Box widget.
 * 
 * @author Andrej Kabachnik
 * 
 * @method Box getWidget()
 *
 */
class UI5Card extends UI5Panel
{
    use JqueryLayoutTrait;
    use UI5HelpButtonTrait;
    
    private $layoutData = null;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Container::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        return <<<JS
                    new sap.f.Card({
                        {$this->buildJsPropertyHeight()}
                        {$this->buildJsPropertyWidth()}
                        {$this->buildJsPropertyLayoutData()}
                        content: [
                            {$this->buildJsChildrenConstructors()}
                        ]
                    })
                            
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Container::buildJsPropertyHeight()
     */
    protected function buildJsPropertyHeight() : string
    {
        $js = 'height: ';
        $widget = $this->getWidget();
        if ($widget->hasParent() === false) {
            return $js . '"100%",';
        }
        
        $dim = $widget->getHeight();
        if ($dim->isUndefined() === true) {
            $height = '"300px"';
        } elseif ($dim->isMax()) {
            $height = '"100%"';
        } else {
            $height = '"' . $dim->getValue() . '"';
        }
        
        return $js . $height . ',';
    }
    
    /**
     * Returns height: "xxx" if required by the container control
     *
     * @return string
     */
    protected function buildJsPropertyWidth() : string
    {
        $js = 'width: ';
        $widget = $this->getWidget();
        if ($widget->hasParent() === false){
            return $js . '"100%",';
        }
        
        $dim = $widget->getWidth();
        if ($dim->isMax()){
            return $js . '"100%",';
        } else {
            return $js . '"' . $dim->getValue() . '",';
        }
    }
}