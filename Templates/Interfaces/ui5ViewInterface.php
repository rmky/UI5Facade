<?php
namespace exface\OpenUI5Template\Templates\Interfaces;

use exface\OpenUI5Template\Templates\Elements\ui5AbstractElement;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
interface ui5ViewInterface {
    
    public function buildJsView() : string;
    
    public function getRootElement() : ui5AbstractElement;
    
    /**
     * Returns the name of the view: e.g. my.app.root.view.my.app.widgetPage
     *
     * @return string
     */
    public function getName() : string;
    
    /**
     * Returns the path to the controller: e.g. my/app/root/view/my/app/widgetPage.view.js
     *
     * @return string
     */
    public function getPath() : string;
    
    public function isBuilt() : bool;
    
    public function getController() : ?ui5ControllerInterface;
    
    public function setController(ui5ControllerInterface $controller) : ui5ViewInterface; 
    
    public function buildJsViewGetter(ui5AbstractElement $fromElement) : string;
    
}