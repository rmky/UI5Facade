# Using translations with the OpenUI5 template

UI5 has it's own translation engine based on .properties files. Best practice is to make translations for the current language available to the app in the model name `i18n`. Thus, translations can be easily referenced by binding to this model: e.g. `text={i18n>your.translation.key}`.

On the other hand, the workbench let's every app have it's own translator implementation, which, however must adhere to the `TranslationInterface`. It is common practice to do the translation when generating the UI by calling `$template->translate()` in the PHP code. 

The OpenUI5 template brings both approaches together. It converts the translation files of the modeled apps into i18n.properties in UI5 syntax, so you can access the same keys both ways.

For example, in `OpenUI5Template/Translations/exface.OpenUI5Template.de.json` there is a key named `WEBAPP.ROUTING.NOTFOUND.TITLE`. This key can either be used in the PHP code of template elements (e.g. `$template->translate('WEBAPP.ROUTING.NOTFOUND.TITLE')`) or in the Javascript of views (e.g. `text: "{i18n>WEBAPP.ROUTING.NOTFOUND.TEXT}"` like in the `NotFound.view.js`).