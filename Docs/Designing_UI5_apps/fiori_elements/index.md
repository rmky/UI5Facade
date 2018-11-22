# Building common SAP Fiori elements with UXON

## Floorplans

Fiori Floorplan | UXON Widgets | Examples | Comments |
--------------- | ------------ | -------- | -------- |
<a href="https://experience.sap.com/fiori-design-web/list-report-floorplan-sap-fiori-element/" target="_blank">List Report</a>	| DataTable, DataTableResponsive | [Basic](floorplan_list_report.md) | A Data widget placed in the root of a page will produce a List Report floorplan. |
<a href="https://experience.sap.com/fiori-design-web/list-report-floorplan-sap-fiori-element/" target="_blank">Object Page</a>	| Dialog with DialogHeader and Tabs | [Basic](floorplan_object_page.md) | A Dialog widget with a DialogHeader, a Tabs widget as content and `maximized = true` will produce an Object Page. Core-actions ShowObjectXXXDialog automatically produce object pages. |
Split Screen | SplitHorizontal, DataCarousel | | A DataCarousel produces a simple split screen |
Split Screen (drill-down) | SplitHorizontal | | A SplitHorizontal widget with a tabular widget on the left and a data widget on the right will produce a split app with drill-down functionality if the left data widget is referenced by filters of the right data widget |

## Other UI elements

UI5 control | UXON Widgets | Examples | Comments |
----------- | ------------ | -------- | -------- |
sap.m.Button | Button, DataButton, DialogButton, MenuButton |  |  |
sap.m.CheckBox | InputCheckBox |  |  |
sap.m.ComboBox | InputSelect |  |  |
sap.m.DatePicker | InputDate, InputDateTime |  |  |
sap.m.Dialog | Dialog | | |
sap.m.GenericTile | Tile |  |  |
sap.m.IconTabBar | Tabs |  |  |
sap.m.IconTabFilter | Tab |  |  |
sap.m.Input | Input, InputHidden |  |  |
sap.m.Input | InputComboTable | | InputComboTable widgets produce inputs with tabluar autosuggest |
sap.m.Image | Image |  |  |
sap.m.List | Menu |  |  |
sap.m.MultiComboBox | InputSelect |  | If `multi_select = true` |
sap.m.OverflowToolbar | Toolbar |  |  |
sap.m.P13nDialog | DataConfigurator |  |  |
sap.m.Page | Dialog | | If `maximized = true` |
sap.m.Panel | NavTiles | | |
sap.m.Panel | Panel | | |
sap.m.ProgressBar | ProgressBar | | |
sap.m.StepInput | InputNumber |  |  |
sap.m.Table | DataTableResponsive | | |
sap.m.Table | DataList | | |
sap.m.Text | Text, Display | | |
sap.m.TextArea | InputText | | |
sap.ui.layourt.form.SimpleForm | Form, Panel | | |
sap.ui.layout.Splitter | SplitVertical, SplitHorizontal | | |
sap.ui.table.Table | DataTable | | |

## Widgets using external libraries

UXON Widget | Library | Comments |
----------- | ------- | -------- |
Chart | jQuery Flot | |
InputJson | JsonEditor | |
InputPropertyTable | JsonEditor | |