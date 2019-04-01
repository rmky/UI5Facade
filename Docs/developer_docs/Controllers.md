# Controllers

Each facade element has a method called `getController()`, that returns a helper-class to manage the contents of the generated  JavaScript controller. It is defined by the `ui5ControllerInterface`. Here is an overview, of how it works, and what it is good for.

## Adding and calling controller methods

The controller is supposed to contain all the UI logic in UI5. In general, developers should not write JS code in views, but only call controller methods. The UI5 Facade attempts to do the same. However, it is not so easy to call controller methods as the actual code to do this largely depends on where you call it from. 

The `ui5Controller` class provides methods to generate controller methods and call them.

### Create a controller method

If your control needs contoller methods, you can generate them in the element's `init()` method or `buildJsConstructor()` and add them to the controller via `getController()->addMethod()`. 

When the controller is rendered, it will contain this method.

### Calling controller methods

You can call your method via `getController()->buildJsMethodCallFromView()` or `getController()->buildJsMethodCallFromController()` depending on where the generated method call is to be placed. This is particularly helpful as it even works if you don't know, how to access the JS controller in the specific situation.

## Using built-in controller methods

Each generated JS controller has a couple of built-in methods, that you can inject code into: see add-methods like `addOnDefineScript()`, `addOnInitScript()`, etc.

## Registering event handlers

Event handlers are treated a little different, than ordinary methods. The JS controller will have a method for every registered event name, but the method's body will be built at compile-time of the controller. All the scripts added via `addOnEventScript()` are occumulated and combined to a handler method at the end. In the mean time, the method name is reserved and can easily be retrieved via `buildJsEventHanlder()` or `buildJsEventHandlerMethodName()`.

## Adding dependent controls

TODO

## Storing arbitrary data as dependent object

TODO