<?php
namespace exface\UI5Facade\Facades\Interfaces;

use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Interfaces\DataSheets\DataPointerInterface;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
interface ui5ModelInterface {
    
    public function getName() : string;
    
    public function getViewName() : string;
    
    /**
     *
     * @param WidgetInterface $widget
     * @param string $bindingName
     * @param DataPointerInterface $pointer
     * @return ui5ModelInterface
     */
    public function setBindingPointer(WidgetInterface $widget, string $bindingName, DataPointerInterface $pointer) : ui5ModelInterface;
    
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
    
}