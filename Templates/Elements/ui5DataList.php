<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\DataList;

/**
 *
 * @method DataList getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class ui5DataList extends ui5DataTable
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5DataTable::isUiTable()
     */
    protected function isUiTable()
    {
        return false;    
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5DataTable::isMTable()
     */
    protected function isMTable()
    {
        return true;
    }
}
?>