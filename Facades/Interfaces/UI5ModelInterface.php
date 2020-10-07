<?php
namespace exface\UI5Facade\Facades\Interfaces;

use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Interfaces\DataSheets\DataPointerInterface;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
interface UI5ModelInterface {
    
    public function getName() : string;
    
    public function getViewName() : string;
    
    /**
     *
     * @param WidgetInterface $widget
     * @param string $bindingName
     * @param DataPointerInterface $pointer
     * @return UI5ModelInterface
     */
    public function setBindingPointer(WidgetInterface $widget, string $bindingName, DataPointerInterface $pointer) : UI5ModelInterface;
    
    /**
     *
     * @param WidgetInterface $widget
     * @param string $bindingName
     * @return string
     */
    public function getBindingPath(WidgetInterface $widget, string $bindingName) : string;
    
    /**
     * 
     * @param WidgetInterface $widget
     * @param string $bindingName
     * @return bool
     */
    public function hasBinding(WidgetInterface $widget, string $bindingName) : bool;
    
    /**
     * Returns TRUE if the model contains other bindings with the same name (but for other widgets).
     * 
     * @param WidgetInterface $widget
     * @param string $bindingName
     * @return bool
     */
    public function hasBindingConflict(WidgetInterface $widget, string $bindingName) : bool;
    
}