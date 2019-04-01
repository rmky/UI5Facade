# Using translations with the OpenUI5 facade

UI5 has it's own translation engine based on .properties files. Best practice is to make translations for the current language available to the app in the model name `i18n`. Thus, translations can be easily referenced by binding to this model: e.g. `text={i18n>your.translation.key}`.

On the other hand, the workbench let's every app have it's own translator implementation, which, however must adhere to the `TranslationInterface`. It is common practice to do the translation when generating the UI by calling `$facade->translate()` in the PHP code. 

The OpenUI5 facade brings both approaches together. It converts the translation files of the modeled apps into i18n.properties in UI5 syntax, so you can access the same keys both ways.

For example, in `UI5Facade/Translations/exface.UI5Facade.de.json` there is a key named `WEBAPP.ROUTING.NOTFOUND.TITLE`. This key can either be used in the PHP code of facade elements (e.g. `$facade->translate('WEBAPP.ROUTING.NOTFOUND.TITLE')`) or in the Javascript of views (e.g. `text: "{i18n>WEBAPP.ROUTING.NOTFOUND.TEXT}"` like in the `NotFound.view.js`).