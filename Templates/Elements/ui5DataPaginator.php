<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\DataPaginator;

/**
 * Generates buttons an labels to be used in a sap.m.Toolbar for pagination.
 * 
 * @method DataPaginator getWidget()
 * 
 * @author Andrej Kabachnik
 *        
 */
class ui5DataPaginator extends ui5AbstractElement
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $buttonVisibility = $this->getWidget()->getDataWidget()->isPaged() ? '' : 'visible: false,';
        
        return <<<JS

        new sap.m.Label("{$this->getId()}_pager", {
            text: ""
        }),
        new sap.m.OverflowToolbarButton("{$this->getId()}_prev", {
            icon: "sap-icon://navigation-left-arrow",
            layoutData: new sap.m.OverflowToolbarLayoutData({priority: "Low"}),
            text: "{$this->translate('WIDGET.PAGINATOR.PREVIOUS_PAGE')}",
            {$buttonVisibility}
            enabled: false,
            press: function() {
                {$this->buildJsGetPaginator($oControllerJs)}.previous();
                {$this->buildJsDataRefresh(true, false, $oControllerJs)}
            }
        }),
        new sap.m.OverflowToolbarButton("{$this->getId()}_next", {
            icon: "sap-icon://navigation-right-arrow",
            layoutData: new sap.m.OverflowToolbarLayoutData({priority: "Low"}),
            text: "{$this->translate('WIDGET.PAGINATOR.NEXT_PAGE')}",
			{$buttonVisibility}
            enabled: false,
            press: function() {
                {$this->buildJsGetPaginator($oControllerJs)}.next();
                {$this->buildJsDataRefresh(true, false, $oControllerJs)}
            }
        }),     
JS;
    }
    
    /**
     * Returns the output of buildJsRefresh() of the connected data widget
     * 
     * @param boolean $keep_page_pos
     * @param boolean $growing
     * @param string $oControllerJsVar
     * @return string
     */
    protected function buildJsDataRefresh($keep_page_pos = false, $growing = false, string $oControllerJsVar = 'oController') : string
    {
        return $this->getTemplate()->getElement($this->getWidget()->getDataWidget())->buildJsRefresh($keep_page_pos, $growing, $oControllerJsVar);
    }
    
    /**
     * Returns the javascript paginator object stored in the controller
     * 
     * @param string $oControllerJs
     * @return string
     */
    public function buildJsGetPaginator(string $oControllerJs = 'oController') : string
    {
        return "{$oControllerJs}.{$this->getId()}_oPaginator";
    }
    
    public function buildJsSetTotal(string $valueJs, string $oControllerJs = 'oController') : string
    {
        return "{$this->buildJsGetPaginator($oControllerJs)}.total = {$valueJs}";
    }

    /**
     *
     * @return string
     */
    protected function buildJsOnPaginate(string $oControllerJs = 'oController') : string
    {
        return <<<JS
        
                    var oPaginator = {$this->buildJsGetPaginator($oControllerJs)};
                	if (oPaginator.start === 0) {
                        sap.ui.getCore().byId("{$this->getId()}_prev").setEnabled(false);
                	} else {
                        sap.ui.getCore().byId("{$this->getId()}_prev").setEnabled(true);
                	}
                	if (oPaginator.end() === (oPaginator.total - 1)) {
                        sap.ui.getCore().byId("{$this->getId()}_next").setEnabled(false);
                	} else {
                		sap.ui.getCore().byId("{$this->getId()}_next").setEnabled(true);
                	}
                    sap.ui.getCore().byId("{$this->getId()}_pager").setText((oPaginator.start + 1) + ' - ' + (oPaginator.end() + 1) + ' / ' + oPaginator.total);
                    
JS;
    }
        
    public function registerControllerMethods() : ui5DataPaginator
    {
        $controller = $this->getController();
        $controller->addProperty($this->getId() . '_oPaginator', $this->buildJsPaginatorObject());
        $controller->addMethod('onPaginate', $this, '', $this->buildJsOnPaginate('this'));
        return $this;
    }
    
    public function buildJsRefresh(string $oControllerJs = 'oController')
    {
        return $this->getController()->buildJsMethodCallFromController('onPaginate', $this, '', $oControllerJs);
    }
    
    /**
     * Returns JavaScript-Functions which are necessary for the pagination.
     *
     * @return string
     */
    protected function buildJsPaginatorObject()
    {
        $defaultPageSize = $this->getWidget()->getPageSize($this->getTemplate()->getConfig()->getOption('WIDGET.DATATABLE.PAGE_SIZE'));
        $enabled = $this->getWidget()->getDataWidget()->isPaged() ? 'true' : 'false';
        
        return <<<JS
        
                {
                	active: {$enabled},
                    start: 0,
                    pageSize: {$defaultPageSize},
                    total: 0,
                    end: function() {
                        return (this.active ? Math.min(this.start + this.pageSize - 1, this.total - 1) : (this.total-1));
                    },
                    previous: function() {
                        this.resetPageSize();
                        if (this.start >= this.pageSize) {
                            this.start -= this.pageSize;
                        } else {
                            this.start = 0;
                        }
                    },
                    next: function() {
                        if (this.start < this.total - this.pageSize) {
                            this.start += this.pageSize;
                        }
                        this.resetPageSize();
                    },
                    increasePageSize: function() {
                        this.pageSize += {$defaultPageSize};
                    },
                    resetPageSize: function() {
                        this.pageSize = {$defaultPageSize};
                    },
                    resetAll: function() {
                        this.start = 0;
                        this.pageSize = {$defaultPageSize};
                        this.total = 0;
                    },
                    growingLoadStart: function() {
                        return this.start + this.pageSize - {$defaultPageSize};
                    },
                    growingLoadPageSize: function() {
                        return {$defaultPageSize};
                    }
                },
                
JS;
    }
}
?>