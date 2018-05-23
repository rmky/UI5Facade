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
    
    public function getName() : string;
    
    public function isBuilt() : bool;
    
    public function getController() : ?ui5ControllerInterface;
    
    public function setController(ui5ControllerInterface $controller) : ui5ViewInterface; 
    
    public function buildJsViewGetter(ui5AbstractElement $fromElement) : string;
    
}