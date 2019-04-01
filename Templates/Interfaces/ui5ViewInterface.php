<?php
namespace exface\UI5Facade\Facades\Interfaces;

use exface\UI5Facade\Facades\Elements\ui5AbstractElement;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
interface ui5ViewInterface {
    
    public function buildJsView() : string;
    
    public function getRootElement() : ui5AbstractElement;
    
    /**
     * Returns the name of the view: e.g. my.app.root.view.my.app.Page
     *
     * @return string
     */
    public function getName() : string;
    
    /**
     * Returns the path to the controller: e.g. my.app.root/view/my/app/Page.view.js
     *
     * @return string
     */
    public function getPath() : string;
    
    /**
     * Returns the name of the default route of the view: e.g. my.app.Page
     * 
     * @return string
     */
    public function getRouteName(): string;
    
    public function isBuilt() : bool;
    
    public function getController() : ?ui5ControllerInterface;
    
    public function setController(ui5ControllerInterface $controller) : ui5ViewInterface; 
    
    public function buildJsViewGetter(ui5AbstractElement $fromElement) : string;
    
    /**
     * 
     * @param string $name
     * @return ui5ModelInterface
     */
    public function getModel(string $name = '') : ui5ModelInterface; 
    
    /**
     * Returns TRUE if this view is the root view for the it's webap
     * 
     * @return bool
     */
    public function isWebAppRoot() : bool;
    
}