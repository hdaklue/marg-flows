========================
CODE SNIPPETS
========================
TITLE: Initialize Hammer and Listen for Events
DESCRIPTION: Demonstrates the basic usage of Hammer.js by creating a new instance attached to an HTML element and listening for a 'pan' gesture event. This is the fundamental way to start using Hammer.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/getting-started.md#_snippet_0

LANGUAGE: javascript
CODE:
```
var hammertime = new Hammer(myElement, myOptions);
hammertime.on('pan', function(ev) {
	console.log(ev);
});
```

----------------------------------------

TITLE: Custom Recognizer Setup
DESCRIPTION: Demonstrates how to create a custom Hammer.Manager instance and add specific recognizers like 'Pan' with custom directions and thresholds, or a 'Tap' recognizer for multiple taps. This offers granular control over gesture recognition.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/getting-started.md#_snippet_4

LANGUAGE: javascript
CODE:
```
var mc = new Hammer.Manager(myElement, myOptions);

mc.add( new Hammer.Pan({ direction: Hammer.DIRECTION_ALL, threshold: 0 }) );
mc.add( new Hammer.Tap({ event: 'quadrupletap', taps: 4 }) );

mc.on("pan", handlePan);
mc.on("quadrupletap", handleTaps);
```

----------------------------------------

TITLE: Install and Run Demo
DESCRIPTION: Commands to install project dependencies and run the demo to generate documentation. This is the primary way to get started with the template.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc-template/README.md#_snippet_0

LANGUAGE: shell
CODE:
```
$ npm install
$ grunt demo
```

----------------------------------------

TITLE: Viewport Meta Tag Recommendation
DESCRIPTION: Provides the recommended viewport meta tag for web pages using Hammer.js. This tag helps disable default browser behaviors like double-tap zooming, giving more control back to the webpage, especially for older browsers.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/getting-started.md#_snippet_3

LANGUAGE: html
CODE:
```
<meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">
```

----------------------------------------

TITLE: Configure Gesture Directions
DESCRIPTION: Illustrates how to configure the directionality for gesture recognizers like 'pan' and 'swipe'. You can set them to recognize all directions or specific axes like vertical.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/getting-started.md#_snippet_2

LANGUAGE: javascript
CODE:
```
hammertime.get('pan').set({ direction: Hammer.DIRECTION_ALL });
hammertime.get('swipe').set({ direction: Hammer.DIRECTION_VERTICAL });
```

----------------------------------------

TITLE: Enable Disabled Recognizers
DESCRIPTION: Shows how to enable gesture recognizers that are disabled by default, such as 'pinch' and 'rotate'. This is done by accessing the recognizer instance and setting its 'enable' property to true.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/getting-started.md#_snippet_1

LANGUAGE: javascript
CODE:
```
hammertime.get('pinch').set({ enable: true });
hammertime.get('rotate').set({ enable: true });
```

----------------------------------------

TITLE: Hammer.Manager.on() Example
DESCRIPTION: Demonstrates listening to events triggered by Hammer.Manager.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/api.md#_snippet_6

LANGUAGE: javascript
CODE:
```
mc.on("pinch", function(ev) {
	console.log(ev.scale);
});
```

----------------------------------------

TITLE: Hammer Constructor Example
DESCRIPTION: Demonstrates how to create a new Hammer instance for a given HTML element.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/api.md#_snippet_0

LANGUAGE: javascript
CODE:
```
var myElement = document.getElementById('hitarea');
var mc = new Hammer(myElement);
```

----------------------------------------

TITLE: Hammer.Manager.get() and add() Examples
DESCRIPTION: Shows how to retrieve a recognizer by name or instance, and how to add new recognizers.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/api.md#_snippet_4

LANGUAGE: javascript
CODE:
```
// Get recognizer instance
var pinchRecognizer = mc.get('pinch');
var rotateRecognizer = mc.get(myRotateRecognizerInstance);
```

LANGUAGE: javascript
CODE:
```
// Add recognizer instance
var addedRecognizer = mc.add(myPinchRecognizer);
var addedMultiple = mc.add([mySecondRecogizner, myThirdRecognizer]);
```

----------------------------------------

TITLE: Hammer.Recognizer Constructor Example
DESCRIPTION: Demonstrates creating a new Recognizer instance and adding it to a Manager.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/api.md#_snippet_9

LANGUAGE: javascript
CODE:
```
var pinch = new Hammer.Pinch();
mc.add(pinch); // add it to the Manager instance
```

----------------------------------------

TITLE: Hammer.Manager.set() Example
DESCRIPTION: Demonstrates updating an option on a Hammer.Manager instance.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/api.md#_snippet_3

LANGUAGE: javascript
CODE:
```
mc.set({ enable: true });
```

----------------------------------------

TITLE: Hammer.Recognizer.set() Example
DESCRIPTION: Shows how to change an option on a Hammer.Recognizer instance.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/api.md#_snippet_10

LANGUAGE: javascript
CODE:
```
pinch.set({ enable: true });
```

----------------------------------------

TITLE: Hammer.Manager Constructor Example
DESCRIPTION: Shows how to instantiate Hammer.Manager with a specific HTML element.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/api.md#_snippet_1

LANGUAGE: javascript
CODE:
```
var mc = new Hammer.Manager(myElement);
```

----------------------------------------

TITLE: Example Usage
DESCRIPTION: A simple JavaScript function call as shown in the documentation.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/AttrRecognizer.defaults.html#_snippet_24

LANGUAGE: javascript
CODE:
```
prettyPrint();
```

----------------------------------------

TITLE: Configuration Options (conf.json)
DESCRIPTION: Example structure for the conf.json file, which allows customization of the generated documentation. Options include application name, disqus integration, Google Analytics ID, open graph properties, and meta tags.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc-template/README.md#_snippet_3

LANGUAGE: json
CODE:
```
{
    "templates": {
        "applicationName": "Demo",
        "disqus": "",
        "googleAnalytics": "",
        "openGraph": {
            "title": "",
            "type": "website",
            "image": "",
            "site_name": "",
            "url": ""
        },
        "meta": {
            "title": "",
            "description": "",
            "keyword": ""
        }
    }
}
```

----------------------------------------

TITLE: Utility Function Example
DESCRIPTION: A simple JavaScript function call, likely for formatting or displaying output.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/TapRecognizer.html#_snippet_24

LANGUAGE: javascript
CODE:
```
prettyPrint();
```

----------------------------------------

TITLE: Utility Function Example
DESCRIPTION: A simple JavaScript function call, likely for formatting or displaying output.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/PinchRecognizer.html#_snippet_23

LANGUAGE: javascript
CODE:
```
prettyPrint();
```

----------------------------------------

TITLE: Utility Function Example
DESCRIPTION: A simple JavaScript function call, likely for formatting or displaying output.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/SwipeRecognizer.html#_snippet_22

LANGUAGE: javascript
CODE:
```
prettyPrint();
```

----------------------------------------

TITLE: Example JavaScript Snippet
DESCRIPTION: A simple JavaScript function call, likely for utility or debugging purposes.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/PanRecognizer.defaults.html#_snippet_24

LANGUAGE: javascript
CODE:
```
prettyPrint();
```

----------------------------------------

TITLE: Hammer.Manager.destroy() Example
DESCRIPTION: Illustrates how to unbind all events and clean up a Hammer.Manager instance.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/api.md#_snippet_8

LANGUAGE: javascript
CODE:
```
mc.destroy();
```

----------------------------------------

TITLE: Hammer.Manager.stop() Example
DESCRIPTION: Shows how to stop the current input recognition session.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/api.md#_snippet_7

LANGUAGE: javascript
CODE:
```
mc.stop(); // Stop recognizing for the current input session
mc.stop(true); // Force stop immediately
```

----------------------------------------

TITLE: Calculate Scale Factor Between Pointer Sets (JavaScript)
DESCRIPTION: Calculates the scaling factor between two sets of pointers (start and end). It determines the ratio of the distance between the two pointers in the 'end' set to the distance in the 'start' set. A scale of 1 means no change.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/input.js.html#_snippet_34

LANGUAGE: javascript
CODE:
```
/**
 * calculate the scale factor between two pointersets
 * no scale is 1, and goes down to 0 when pinched together, and bigger when pinched out
 * @param {Array} start array of pointers
 * @param {Array} end array of pointers
 * @return {Number} scale
 */
function getScale(start, end) {
    return getDistance(end[0], end[1], PROPS_CLIENT_XY) / getDistance(start[0], start[1], PROPS_CLIENT_XY);
}
```

----------------------------------------

TITLE: Touch Emulator Bookmarklet
DESCRIPTION: A bookmarklet to dynamically load and initialize the Touch Emulator script from a CDN. It creates a script element, sets its source, and appends it to the document body.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/touch-emulator.md#_snippet_2

LANGUAGE: javascript
CODE:
```
javascript:!function(a){var b=a.createElement("script");b.onload=function(){TouchEmulator()},b.src="//cdn.rawgit.com/hammerjs/touchemulator/0.0.2/touch-emulator.js",a.body.appendChild(b)}(document);
```

----------------------------------------

TITLE: Hammer.Manager.remove() Examples
DESCRIPTION: Illustrates removing recognizers from a Hammer.Manager instance.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/api.md#_snippet_5

LANGUAGE: javascript
CODE:
```
mc.remove(myPinchRecognizer);
mc.remove('rotate');
mc.remove([myPinchRecognizer, 'rotate']);
```

----------------------------------------

TITLE: Listen for Touch Events
DESCRIPTION: Add event listeners to the document body to log touchstart, touchmove, and touchend events. This demonstrates how to capture the emulated touch events.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/touch-emulator.md#_snippet_1

LANGUAGE: javascript
CODE:
```
function log(ev) {
 console.log(ev);
}

document.body.addEventListener('touchstart', log, false);
document.body.addEventListener('touchmove', log, false);
document.body.addEventListener('touchend', log, false);
```

----------------------------------------

TITLE: Hammer.Manager with Custom Recognizers
DESCRIPTION: Illustrates initializing Hammer.Manager with a custom array of recognizer configurations.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/api.md#_snippet_2

LANGUAGE: javascript
CODE:
```
var mc = new Hammer.Manager(myElement, {
	recognizers: [
		// RecognizerClass, [options], [recognizeWith, ...], [requireFailure, ...]
		[Hammer.Rotate],
		[Hammer.Pinch, { enable: false }, ['rotate']],
		[Hammer.Swipe,{ direction: Hammer.DIRECTION_HORIZONTAL }],
	]
});
```

----------------------------------------

TITLE: Hammer.js API Documentation
DESCRIPTION: Comprehensive API reference for Hammer.js, covering core classes, methods, and options.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/api.md#_snippet_11

LANGUAGE: APIDOC
CODE:
```
Hammer.js API Reference

General API:
- Hammer
- Hammer.defaults
- Hammer.Manager
- Hammer.Recognizer
- Hammer.input event
- Event object
- Constants
- Utils

## Hammer
Creates a Manager instance with a default set of recognizers and returns the manager instance. The default set contains `tap`, `doubletap`, `pan`, `swipe`, `press`, `pinch` and `rotate` recognizer instances.

### Constructor(HTMLElement, [options])
- Parameters:
  - HTMLElement: The DOM element to attach Hammer to.
  - [options]: Optional configuration object.
- Description: Initializes Hammer with default recognizers. If an empty `recognizer` option is passed, no initial recognizers are added.

## Hammer.defaults
Global defaults that are merged with instance options.

### touchAction: 'compute'
- Description: Controls how touch actions are handled. Accepts `compute`, `auto`, `pan-y`, `pan-x`, `none`.

### domEvents: false
- Description: If true, Hammer will also fire DOM events. Disabled by default for performance.

### enable: true
- Description: Enables or disables a recognizer. Can be a boolean or a callback function.

### cssProps: {....}
- Description: Collection of CSS properties for input event handling. See JSDoc for details.

### preset: [....]
- Description: Default recognizer setup for `Hammer()` constructor. Skipped when creating a new Manager.

## Hammer.Manager
The core class that manages recognizer instances, input event listeners, and touch-action properties.

### constructor(HTMLElement, [options])
- Parameters:
  - HTMLElement: The DOM element to attach Hammer to.
  - [options]: Optional configuration object. Can include a `recognizers` array.
- Description: Initializes a Manager instance. The `recognizers` option is an array structured as `[RecognizerClass, [options], [recognizeWith, ...], [requireFailure, ...]]`.

### set(options)
- Description: Updates an option on the manager instance. Automatically updates `touchAction` if necessary.
- Parameters:
  - options: An object containing properties to update.

### get(string|Recognizer), add(Recognizer), remove(Recognizer)
- Description: Methods for managing recognizers. `get` retrieves a recognizer by name or instance. `add` registers a new recognizer. `remove` unregisters a recognizer.
- Parameters:
  - get: `string` (event name) or `Recognizer` instance.
  - add: `Recognizer` instance or an array of `Recognizer` instances.
  - remove: `Recognizer` instance or `string` (event name) or an array of recognizers.
- Returns: The added `Recognizer` instance for `add`.

### on(events, handler), off(events, [handler])
- Description: Event listeners for recognizer events. Accepts multiple events separated by a space.
- Parameters:
  - events: A string of space-separated event names.
  - handler: The callback function to execute.

### stop([force])
- Description: Stops the current input recognition session. If `force` is true, the recognizer cycle stops immediately.
- Parameters:
  - force: Boolean, whether to force stop.

### destroy()
- Description: Unbinds all events and input listeners, making the manager unusable. Does not unbind DOM event listeners.

## Hammer.Recognizer
Base class for all recognizers. Provides common options like `enable`.

### constructor([options])
- Description: Initializes a Recognizer instance with provided options.

### set(options)
- Description: Updates an option on the recognizer instance. Automatically updates `touchAction` if necessary.
- Parameters:
  - options: An object containing properties to update.
```

----------------------------------------

TITLE: Download Hammer.js Library
DESCRIPTION: Provides a link to download the minified Hammer.js JavaScript library. The version and gzipped size are dynamically displayed, indicating it's part of a build process or content management system.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/index.html#_snippet_0

LANGUAGE: javascript
CODE:
```
Hammer.min.js
v{{site.data.hammer.version}} — {{site.data.hammer.gzipped}} gzipped
(dist/hammer.min.js)
```

----------------------------------------

TITLE: Hammer Class API
DESCRIPTION: API documentation for the Hammer class, including its constructor and static members. This class is the main entry point for Hammer.js.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/Hammer.html#_snippet_20

LANGUAGE: APIDOC
CODE:
```
Hammer(element, options)
  - Simple way to create a manager with a default set of recognizers.
  - Parameters:
    - element: HTMLElement - The DOM element to attach Hammer.js to.
    - options: Object - Optional configuration object for Hammer.js.
  - Related:
    - Hammer.defaults
    - TapRecognizer
    - TouchAction
    - TouchInput
```

LANGUAGE: APIDOC
CODE:
```
Hammer.VERSION
  - Static constant representing the Hammer.js version.
  - Type: string
```

----------------------------------------

TITLE: Hammer.js Input Class API
DESCRIPTION: Documentation for the `Input` class, detailing its constructor and prototype methods for managing event listeners and handling input events.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/input.js.html#_snippet_21

LANGUAGE: APIDOC
CODE:
```
Input(manager, callback)
  - Creates a new input type manager.
  - Parameters:
    - manager: The Hammer manager instance.
    - callback: The function to call when input events occur.
  - Properties:
    - manager: Reference to the Hammer manager.
    - callback: The callback function.
    - element: The element to listen for events on.
    - target: The target element for input events.
    - domHandler: A wrapper for the handler to manage scope and enabled state.

Input.prototype.handler()
  - Virtual method, should handle the inputEvent data and trigger the callback.

Input.prototype.init()
  - Binds the events to the element, target, and window.
  - Uses `addEventListeners` to attach the `domHandler`.

Input.prototype.destroy()
  - Unbinds the events that were bound in `init`.
  - Uses `removeEventListeners` to detach the `domHandler`.
```

----------------------------------------

TITLE: Input Class API Documentation
DESCRIPTION: Documentation for the Hammer.js Input class, covering its constructor and core methods. This class manages input events and interactions.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/Input.html#_snippet_20

LANGUAGE: APIDOC
CODE:
```
Input Class:
  Description: Manages input events and interactions for Hammer.js.

  Constructor:
    new Input(manager, callback){[Input](Input.html)}
    - Creates a new input type manager.
    - Parameters:
      - manager: [Manager](Manager.html) - The manager instance.
      - callback: function - The callback function to handle input events.
    - Source: [input.js](input.js.html), [line 39](input.js.html#line39)

  Methods:
    destroy()
      - Unbinds the events associated with the input.
      - Source: [input.js](input.js.html), [line 77](input.js.html#line77)

    handler()
      - Abstract method intended to handle input event data and trigger the callback.
      - Should be implemented by subclasses.
      - Source: [input.js](input.js.html), [line 63](input.js.html#line63)

    init()
      - Binds the necessary events for the input manager.
      - Source: [input.js](input.js.html), [line 68](input.js.html#line68)
```

----------------------------------------

TITLE: Get Prefixed Property with Hammer.js
DESCRIPTION: Retrieves a browser-prefixed version of a CSS property name from a given object, such as `document.body.style`. This helps ensure compatibility across different browser vendors.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/api.md#_snippet_26

LANGUAGE: APIDOC
CODE:
```
Hammer.prefixed(obj, name)
- Get the (prefixed) property from the browser.
- Parameters:
  - obj: The object to search within (e.g., `document.body.style`).
  - name: The CSS property name (e.g., 'userSelect').
- Returns: The prefixed property name (e.g., 'webkitUserSelect') or the original name if no prefix is found.
- Example:
Hammer.prefixed(document.body.style, 'userSelect');
// returns "webkitUserSelect" on Chrome 35
```

----------------------------------------

TITLE: Get Window for Element
DESCRIPTION: Retrieves the window object associated with a given HTML element. It traverses the element's `ownerDocument` to find the corresponding `defaultView` or `parentWindow`. This is crucial for DOM operations that require a window context.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/utils.js.html#_snippet_37

LANGUAGE: javascript
CODE:
```
/**
 * get the window object of an element
 * @param {HTMLElement} element
 * @returns {DocumentView|Window}
 */
function getWindowForElement(element) {
    var doc = element.ownerDocument || element;
    return (doc.defaultView || doc.parentWindow || window);
}
```

----------------------------------------

TITLE: Compute Delta XY for Input - JavaScript
DESCRIPTION: Calculates the change in X and Y coordinates (deltaX, deltaY) for an input event. It accounts for the starting point of a gesture and accumulates deltas across subsequent events within the same gesture session.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/input.js.html#_snippet_25

LANGUAGE: javascript
CODE:
```
function computeDeltaXY(session, input) {
    var center = input.center;
    var offset = session.offsetDelta || {};
    var prevDelta = session.prevDelta || {};
    var prevInput = session.prevInput || {};

    if (input.eventType === INPUT_START || prevInput.eventType === INPUT_END) {
        prevDelta = session.prevDelta = {
            x: prevInput.deltaX || 0,
            y: prevInput.deltaY || 0
        };

        offset = session.offsetDelta = {
            x: center.x,
            y: center.y
        };
    }

    input.deltaX = prevDelta.x + (center.x - offset.x);
    input.deltaY = prevDelta.y + (center.y - offset.y);
}
```

----------------------------------------

TITLE: TouchInput Class Methods (Hammer.js)
DESCRIPTION: Documentation for the TouchInput class, which handles touch events. It includes the constructor and inherited methods from the base Input class.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/TouchInput.html#_snippet_20

LANGUAGE: APIDOC
CODE:
```
TouchInput:
  Constructor:
    new TouchInput()
    - Initializes a new TouchInput instance.
    - Source: [input/touch.js](input_touch.js.html), [line 15](input_touch.js.html#line15)
    - Description: Multi-user touch events input.
    - Extends: [Input](Input.html)

  Methods:
    destroy()
      - Inherited from: [Input](Input.html#destroy)
      - Source: [input.js](input.js.html), [line 77](input.js.html#line77)
      - Description: Unbinds the events associated with the input.
      - Parameters: None
      - Returns: void

    handler()
      - Inherited from: [Input](Input.html#handler)
      - Source: [input.js](input.js.html), [line 63](input.js.html#line63)
      - Description: Abstract method that should handle the inputEvent data and trigger the callback.
      - Parameters: None (abstract)
      - Returns: void

    init()
      - Inherited from: [Input](Input.html#init)
      - Source: [input.js](input.js.html), [line 68](input.js.html#line68)
      - Description: Binds the events required for touch input.
      - Parameters: None
      - Returns: void
```

----------------------------------------

TITLE: Hammer.js: Abstract Get Touch Action
DESCRIPTION: A placeholder method for retrieving the preferred touch-action value for the recognizer. Subclasses should implement this to specify how the browser should handle touch events when this recognizer is active (e.g., 'pan-x', 'pan-y', 'none').

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/recognizer.js.html#_snippet_29

LANGUAGE: javascript
CODE:
```
/**
 * return the preferred touch-action
 * @virtual
 * @returns {Array}
 */
getTouchAction: function() { },

```

----------------------------------------

TITLE: Get Hammer.js Recognizer by Name (JavaScript)
DESCRIPTION: Retrieves a specific recognizer instance bound to a Hammer.js manager by its name or instance. If the recognizer is managed, it fetches it from the manager; otherwise, it returns the provided recognizer. This is useful for inter-recognizer communication or configuration.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/recognizer.js.html#_snippet_33

LANGUAGE: javascript
CODE:
```
/**
 * get a recognizer by name if it is bound to a manager
 * @param {Recognizer|String} otherRecognizer
 * @param {Recognizer} recognizer
 * @returns {Recognizer}
 */
function getRecognizerByNameIfManager(otherRecognizer, recognizer) {
    var manager = recognizer.manager;
    if (manager) {
        return manager.get(otherRecognizer);
    }
    return otherRecognizer;
}
```

----------------------------------------

TITLE: Include Touch Emulator Script
DESCRIPTION: Include the TouchEmulator.js script and call the TouchEmulator() function before other touch-handling libraries. This sets up fake properties to spoof touch detection and triggers touch events.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/touch-emulator.md#_snippet_0

LANGUAGE: html
CODE:
```
<script src="touch-emulator.js"></script>
<script> TouchEmulator(); </script>
```

----------------------------------------

TITLE: Calculate Rotation Between Pointer Sets (JavaScript)
DESCRIPTION: Calculates the rotation in degrees between two sets of pointers (start and end). It sums the angles formed by pairs of pointers in each set to determine the overall rotation. Assumes two pointers per set.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/input.js.html#_snippet_33

LANGUAGE: javascript
CODE:
```
/**
 * calculate the rotation degrees between two pointersets
 * @param {Array} start array of pointers
 * @param {Array} end array of pointers
 * @return {Number} rotation
 */
function getRotation(start, end) {
    return getAngle(end[1], end[0], PROPS_CLIENT_XY) + getAngle(start[1], start[0], PROPS_CLIENT_XY);
}
```

----------------------------------------

TITLE: Release Hammer.js
DESCRIPTION: Command to initiate the release process for new versions of the Hammer.js library. This command automates tasks such as version bumping, building, and publishing.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/README.md#_snippet_0

LANGUAGE: shell
CODE:
```
make release
```

----------------------------------------

TITLE: Get Vendor Prefixed Property
DESCRIPTION: Retrieves a property name from an object, checking for vendor-prefixed versions (e.g., 'webkitTransform', 'msTransform'). It capitalizes the first letter of the property and iterates through a list of vendor prefixes to find a match. Returns the prefixed property name or undefined if not found.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/utils.js.html#_snippet_35

LANGUAGE: javascript
CODE:
```
/**
 * get the prefixed property
 * @param {Object} obj
 * @param {String} property
 * @returns {String|Undefined} prefixed
 */
function prefixed(obj, property) {
    var prefix, prop;
    var camelProp = property[0].toUpperCase() + property.slice(1);

    var i = 0;
    // VENDOR_PREFIXES is assumed to be defined elsewhere, e.g., ['webkit', 'ms', 'Moz', 'O', '']
    while (i < VENDOR_PREFIXES.length) {
        prefix = VENDOR_PREFIXES[i];
        prop = (prefix) ? prefix + camelProp : property;

        if (prop in obj) {
            return prop;
        }
        i++;
    }
    return undefined;
}
```

----------------------------------------

TITLE: Recognizer Methods
DESCRIPTION: Documentation for methods available on Hammer.js Recognizer instances, including setting options and emitting events.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/AttrRecognizer.html#_snippet_21

LANGUAGE: APIDOC
CODE:
```
set(options)
  Parameters:
    options (Object): Configuration options for the recognizer.
  Description: Sets options for the recognizer instance. Inherited from Recognizer.
  Source: recognizer.js, line 70
```

LANGUAGE: APIDOC
CODE:
```
tryEmit(input)
  Parameters:
    input (Object): The input event object.
  Description: Checks that all required failure recognizers have failed. If true, it emits a gesture event; otherwise, setup the state to FAILED. 
  Source: recognizer.js, line 202
```

----------------------------------------

TITLE: Convert Hammer.js State to String (JavaScript)
DESCRIPTION: Converts Hammer.js internal state bitmasks into descriptive string representations like 'start', 'move', 'end', or 'cancel'. This function is useful for debugging or logging gesture states. It takes an integer state value and returns the corresponding string.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/recognizer.js.html#_snippet_31

LANGUAGE: javascript
CODE:
```
/**
 * @returns {String} state
 */
function stateStr(state) {
    if (state & STATE_CANCELLED) {
        return 'cancel';
    } else if (state & STATE_ENDED) {
        return 'end';
    } else if (state & STATE_CHANGED) {
        return 'move';
    } else if (state & STATE_BEGAN) {
        return 'start';
    }
    return '';
}
```

----------------------------------------

TITLE: Google Analytics Initialization
DESCRIPTION: Initializes Google Analytics tracking for the Hammer.js website. This script configures the analytics object and sends the initial page view.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/input_pointerevent.js.html#_snippet_0

LANGUAGE: javascript
CODE:
```
var config = {"monospaceLinks":false,"cleverLinks":false,"default":{"outputSourceFiles":true},"applicationName":"Hammer.js","disqus":"","googleAnalytics":"","openGraph":{"title":"","type":"website","image":"","site_name":"","url":""},"meta":{"title":"Hammer.js API","description":"","keyword":""}};
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){ (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o), m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m) })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
ga('create', 'UA-30289566-1', 'auto');
ga('send', 'pageview');
```

----------------------------------------

TITLE: RecognizeWith Method for Simultaneous Gestures
DESCRIPTION: Enables the current gesture recognizer to be triggered simultaneously with another specified recognizer. This enhances user experience by allowing complex interactions. The example shows Pinch and Rotate recognizers working together. The dropRecognizeWith() method can be used to remove this relationship.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/recognize-with.md#_snippet_0

LANGUAGE: javascript
CODE:
```
var pinch = new Hammer.Pinch();
var rotate = new Hammer.Rotate();
pinch.recognizeWith(rotate);
```

----------------------------------------

TITLE: Hammer Constructor and Defaults
DESCRIPTION: Defines the main Hammer constructor for creating gesture managers and sets default configuration options, including touch action behavior, input targeting, and a preset array of recognizers. It also includes the library version.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/hammer.js.html#_snippet_20

LANGUAGE: JavaScript
CODE:
```
/**
 * Simple way to create a manager with a default set of recognizers.
 * @param {HTMLElement} element
 * @param {Object} [options]
 * @constructor
 */
function Hammer(element, options) {
    options = options || {};
    options.recognizers = ifUndefined(options.recognizers, Hammer.defaults.preset);
    return new Manager(element, options);
}

/**
 * @const {string}
 */
Hammer.VERSION = '{{PKG_VERSION}}';

/**
 * default settings
 * @namespace
 */
Hammer.defaults = {
    /**
     * set if DOM events are being triggered.
     * But this is slower and unused by simple implementations, so disabled by default.
     * @type {Boolean}
     * @default false
     */
    domEvents: false,

    /**
     * The value for the touchAction property/fallback.
     * When set to `compute` it will magically set the correct value based on the added recognizers.
     * @type {String}
     * @default compute
     */
    touchAction: TOUCH_ACTION_COMPUTE,

    /**
     * @type {Boolean}
     * @default true
     */
    enable: true,

    /**
     * EXPERIMENTAL FEATURE -- can be removed/changed
     * Change the parent input target element.
     * If Null, then it is being set the to main element.
     * @type {Null|EventTarget}
     * @default null
     */
    inputTarget: null,

    /**
     * force an input class
     * @type {Null|Function}
     * @default null
     */
    inputClass: null,

    /**
     * Default recognizer setup when calling `Hammer()`
     * When creating a new Manager these will be skipped.
     * @type {Array}
     */
    preset: [
        // RecognizerClass, options, [recognizeWith, ...], [requireFailure, ...]
        [RotateRecognizer, {enable: false}],
        [PinchRecognizer, {enable: false}, ['rotate']],
        [SwipeRecognizer, {direction: DIRECTION_HORIZONTAL}],
        [PanRecognizer, {direction: DIRECTION_HORIZONTAL}, ['swipe']],
        [TapRecognizer],
        [TapRecognizer, {event: 'doubletap', taps: 2}, ['tap']],
        [PressRecognizer]
    ]
```

----------------------------------------

TITLE: TouchInput Class for Hammer.js
DESCRIPTION: Implements multi-user touch event handling for Hammer.js. It extends the base Input class and manages touch events like start, move, end, and cancel, mapping them to internal input types. Includes a helper function to process touch data from event objects.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/touch.js.html#_snippet_5

LANGUAGE: javascript
CODE:
```
var TOUCH_INPUT_MAP = {
    touchstart: INPUT_START,
    touchmove: INPUT_MOVE,
    touchend: INPUT_END,
    touchcancel: INPUT_CANCEL
};

var TOUCH_TARGET_EVENTS = 'touchstart touchmove touchend touchcancel';

/**
 * Multi-user touch events input
 * @constructor
 * @extends Input
 */
function TouchInput() {
    this.evTarget = TOUCH_TARGET_EVENTS;
    this.targetIds = {};

    Input.apply(this, arguments);
}

inherit(TouchInput, Input, {
    handler: function MTEhandler(ev) {
        var type = TOUCH_INPUT_MAP[ev.type];
        var touches = getTouches.call(this, ev, type);
        if (!touches) {
            return;
        }

        this.callback(this.manager, type, {
            pointers: touches[0],
            changedPointers: touches[1],
            pointerType: INPUT_TYPE_TOUCH,
            srcEvent: ev
        });
    }
});

/**
 * @this {TouchInput}
 * @param {Object} ev
 * @param {Number} type flag
 * @returns {undefined|Array} [all, changed]
 */
function getTouches(ev, type) {
    var allTouches = toArray(ev.touches);
    var targetIds = this.targetIds;

    // when there is only one touch, the process can be simplified
    if (type & (INPUT_START | INPUT_MOVE) && allTouches.length === 1) {
        targetIds[allTouches[0].identifier] = true;
        return [allTouches, allTouches];
    }

    var i,
        targetTouches,
        changedTouches = toArray(ev.changedTouches),
        changedTargetTouches = [],
        target = this.target;

    // get target touches from touches
    targetTouches = allTouches.filter(function(touch) {
        return hasParent(touch.target, target);
    });

    // collect touches
    if (type === INPUT_START) {
        i = 0;
        while (i < targetTouches.length) {
            targetIds[targetTouches[i].identifier] = true;
            i++;
        }
    }

    // filter changed touches to only contain touches that exist in the collected target ids
    i = 0;
    while (i < changedTouches.length) {
        if (targetIds[changedTouches[i].identifier]) {
            changedTargetTouches.push(changedTouches[i]);
        }

        // cleanup removed touches
        if (type & (INPUT_END | INPUT_CANCEL)) {
            delete targetIds[changedTouches[i].identifier];
        }
        i++;
    }

    if (!changedTargetTouches.length) {
        return;
    }

    return [
        // merge targetTouches with changedTargetTouches so it contains ALL touches, including 'end' and 'cancel'
        uniqueArray(targetTouches.concat(changedTargetTouches), 'identifier', true),
        changedTargetTouches
    ];
}

```

----------------------------------------

TITLE: PointerEventInput Class Documentation
DESCRIPTION: Documentation for the PointerEventInput class, which handles pointer events. It extends the base Input class and includes methods for initialization, event handling, and cleanup.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/PointerEventInput.html#_snippet_20

LANGUAGE: APIDOC
CODE:
```
Class: PointerEventInput

PointerEventInput
-----------------

#### new PointerEventInput()

[input/pointerevent.js](input_pointerevent.js.html), [line 31](input_pointerevent.js.html#line31)

Pointer events input

### Extends

*   [Input](Input.html)

### Methods

#### [inherited](Input.html#destroy) destroy()

[input.js](input.js.html), [line 77](input.js.html#line77)

unbind the events

#### [inherited](Input.html#handler) abstracthandler()

[input.js](input.js.html), [line 63](input.js.html#line63)

should handle the inputEvent data and trigger the callback

#### [inherited](Input.html#init) init()

[input.js](input.js.html), [line 68](input.js.html#line68)

bind the events
```

----------------------------------------

TITLE: Hammer.js Core API
DESCRIPTION: Provides the main Hammer instance for gesture detection and management. Includes core methods for adding/removing recognizers, event handling, and configuration.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/input_mouse.js.html#_snippet_1

LANGUAGE: APIDOC
CODE:
```
Hammer:
  __constructor(element, [options])
    element: The DOM element to attach Hammer to.
    options: Configuration object for Hammer.

  VERSION: string
    The current version of Hammer.js.

  defaults: object
    Global default options for Hammer.
    - domEvents: boolean
    - enable: boolean
    - inputClass: Input class to use.
    - inputTarget: DOM element to listen for input events on.
    - preset: Array of recognizer configurations.
    - touchAction: CSS touch-action property value.

  defaults.cssProps: object
    Default CSS properties to manage touch behavior.
    - contentZooming: CSS property for content zooming.
    - tapHighlightColor: CSS property for tap highlight color.
    - touchCallout: CSS property for touch callout.
    - touchSelect: CSS property for touch selection.
    - userDrag: CSS property for user drag behavior.
    - userSelect: CSS property for user selection.
```

----------------------------------------

TITLE: MouseInput Class for Hammer.js
DESCRIPTION: Implements mouse event handling for Hammer.js. It maps mouse events like mousedown, mousemove, and mouseup to Hammer's input states (START, MOVE, END). It manages the mouse press state and ensures events are processed only when the mouse button is down and allowed by the input manager.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/mouse.js.html#_snippet_5

LANGUAGE: javascript
CODE:
```
var MOUSE_INPUT_MAP = {
    mousedown: INPUT_START,
    mousemove: INPUT_MOVE,
    mouseup: INPUT_END
};

var MOUSE_ELEMENT_EVENTS = 'mousedown';
var MOUSE_WINDOW_EVENTS = 'mousemove mouseup';

/**
 * Mouse events input
 * @constructor
 * @extends Input
 */
function MouseInput() {
    this.evEl = MOUSE_ELEMENT_EVENTS;
    this.evWin = MOUSE_WINDOW_EVENTS;

    this.allow = true; // used by Input.TouchMouse to disable mouse events
    this.pressed = false; // mousedown state

    Input.apply(this, arguments);
}

inherit(MouseInput, Input, {
    /**
     * handle mouse events
     * @param {Object} ev
     */
    handler: function MEhandler(ev) {
        var eventType = MOUSE_INPUT_MAP[ev.type];

        // on start we want to have the left mouse button down
        if (eventType & INPUT_START && ev.button === 0) {
            this.pressed = true;
        }

        if (eventType & INPUT_MOVE && ev.which !== 1) {
            eventType = INPUT_END;
        }

        // mouse must be down, and mouse events are allowed (see the TouchMouse input)
        if (!this.pressed || !this.allow) {
            return;
        }

        if (eventType & INPUT_END) {
            this.pressed = false;
        }

        this.callback(this.manager, eventType, {
            pointers: [ev],
            changedPointers: [ev],
            pointerType: INPUT_TYPE_MOUSE,
            srcEvent: ev
        });
    }
});
```

----------------------------------------

TITLE: Hammer.js Core API
DESCRIPTION: Provides the main Hammer instance for gesture detection and management. Includes core methods for adding/removing recognizers, event handling, and configuration.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/input_touch.js.html#_snippet_1

LANGUAGE: APIDOC
CODE:
```
Hammer:
  __constructor(element, [options])
    element: The DOM element to attach Hammer to.
    options: Configuration object for Hammer.

  VERSION: string
    The current version of Hammer.js.

  defaults: object
    Global default options for Hammer.
    - domEvents: boolean
    - enable: boolean
    - inputClass: Input class to use.
    - inputTarget: DOM element to listen for input events on.
    - preset: Array of recognizer configurations.
    - touchAction: CSS touch-action property value.

  defaults.cssProps: object
    Default CSS properties to manage touch behavior.
    - contentZooming: CSS property for content zooming.
    - tapHighlightColor: CSS property for tap highlight color.
    - touchCallout: CSS property for touch callout.
    - touchSelect: CSS property for touch selection.
    - userDrag: CSS property for user drag behavior.
    - userSelect: CSS property for user selection.
```

----------------------------------------

TITLE: Hammer.js Manager Class API
DESCRIPTION: Detailed API documentation for the Hammer.js Manager class, including its constructor and methods for managing recognizers, events, and gesture recognition sessions.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/Manager.html#_snippet_20

LANGUAGE: APIDOC
CODE:
```
Manager:
  new Manager(element, options)
    Description: Initializes a new Manager instance.
    Parameters:
      - element: HTMLElement - The DOM element to attach Hammer.js to.
      - options: Object - Configuration options for the manager (optional).

  Methods:

  add(recognizer)
    Description: Adds a recognizer to the manager. Existing recognizers with the same event name will be removed.
    Parameters:
      - recognizer: Recognizer - The recognizer instance to add.
    Returns: Recognizer | Manager - The manager instance for chaining.

  destroy()
    Description: Destroys the manager and unbinds all events. Note: it does not unbind DOM events; that is the user's responsibility.
    Returns: void

  emit(event, data)
    Description: Emits an event to the listeners.
    Parameters:
      - event: String - The name of the event to emit.
      - data: Object - The data associated with the event.
    Returns: void

  get(recognizer)
    Description: Gets a recognizer by its event name.
    Parameters:
      - recognizer: Recognizer | String - The recognizer instance or its event name.
    Returns: Recognizer | Null - The found recognizer instance or null if not found.

  off(events, handler)
    Description: Unbinds an event. If handler is left blank, all handlers for the event are removed.
    Parameters:
      - events: String - The name of the event to unbind.
      - handler: function - The handler function to unbind (optional).
    Returns: EventEmitter - The EventEmitter instance (likely the Manager itself) for chaining.

  on(events, handler)
    Description: Binds an event.
    Parameters:
      - events: String - The name of the event to bind.
      - handler: function - The handler function to execute.
    Returns: EventEmitter - The EventEmitter instance (likely the Manager itself) for chaining.

  recognize(inputData)
    Description: Runs the recognizers. Called by the inputHandler function on every movement of the pointers (touches). It walks through all the recognizers and tries to detect the gesture that is being made.
    Parameters:
      - inputData: Object - The input data object containing gesture information.
    Returns: void

  remove(recognizer)
    Description: Removes a recognizer by name or instance.
    Parameters:
      - recognizer: Recognizer | String - The recognizer instance or its event name to remove.
    Returns: Manager - The manager instance for chaining.

  set(options)
    Description: Sets options for the manager.
    Parameters:
      - options: Object - An object containing the options to set.
    Returns: Manager - The manager instance for chaining.

  stop(force)
    Description: Stops recognizing for this session. This session will be discarded when a new [input]start event is fired. When forced, the recognizer cycle is stopped immediately.
    Parameters:
      - force: Boolean - If true, the recognizer cycle is stopped immediately (optional).
    Returns: void
```

----------------------------------------

TITLE: SingleTouchInput Class for Hammer.js Touch Handling
DESCRIPTION: Implements touch event handling for Hammer.js, mapping touch events like 'touchstart' to internal input types. It manages touch state and processes touch events to provide pointer data to the manager. Dependencies include the base `Input` class and utility functions like `inherit`, `toArray`, and `uniqueArray`.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/singletouch.js.html#_snippet_5

LANGUAGE: javascript
CODE:
```
var SINGLE_TOUCH_INPUT_MAP = {
    touchstart: INPUT_START,
    touchmove: INPUT_MOVE,
    touchend: INPUT_END,
    touchcancel: INPUT_CANCEL
};

var SINGLE_TOUCH_TARGET_EVENTS = 'touchstart';
var SINGLE_TOUCH_WINDOW_EVENTS = 'touchstart touchmove touchend touchcancel';

/**
 * Touch events input
 * @constructor
 * @extends Input
 */
function SingleTouchInput() {
    this.evTarget = SINGLE_TOUCH_TARGET_EVENTS;
    this.evWin = SINGLE_TOUCH_WINDOW_EVENTS;
    this.started = false;

    Input.apply(this, arguments);
}

inherit(SingleTouchInput, Input, {
    handler: function TEhandler(ev) {
        var type = SINGLE_TOUCH_INPUT_MAP[ev.type];

        // should we handle the touch events?
        if (type === INPUT_START) {
            this.started = true;
        }

        if (!this.started) {
            return;
        }

        var touches = normalizeSingleTouches.call(this, ev, type);

        // when done, reset the started state
        if (type & (INPUT_END | INPUT_CANCEL) && touches[0].length - touches[1].length === 0) {
            this.started = false;
        }

        this.callback(this.manager, type, {
            pointers: touches[0],
            changedPointers: touches[1],
            pointerType: INPUT_TYPE_TOUCH,
            srcEvent: ev
        });
    }
});

/**
 * @this {TouchInput}
 * @param {Object} ev
 * @param {Number} type flag
 * @returns {undefined|Array} [all, changed]
 */
function normalizeSingleTouches(ev, type) {
    var all = toArray(ev.touches);
    var changed = toArray(ev.changedTouches);

    if (type & (INPUT_END | INPUT_CANCEL)) {
        all = uniqueArray(all.concat(changed), 'identifier', true);
    }

    return [all, changed];
}
```

----------------------------------------

TITLE: Google Analytics Initialization
DESCRIPTION: Initializes Google Analytics tracking for the Hammer.js website. This script configures the analytics object and sends the initial page view.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/recognizers_pinch.js.html#_snippet_0

LANGUAGE: javascript
CODE:
```
var config = {"monospaceLinks":false,"cleverLinks":false,"default":{"outputSourceFiles":true},"applicationName":"Hammer.js","disqus":"","googleAnalytics":"","openGraph":{"title":"","type":"website","image":"","site_name":"","url":""},"meta":{"title":"Hammer.js API","description":"","keyword":""}};
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){ (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o), m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m) })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
ga('create', 'UA-30289566-1', 'auto');
ga('send', 'pageview');
```

----------------------------------------

TITLE: Hammer.js Core API
DESCRIPTION: Provides the main Hammer instance for gesture detection and management. Includes core methods for adding/removing recognizers, event handling, and configuration.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/input.js.html#_snippet_1

LANGUAGE: APIDOC
CODE:
```
Hammer:
  __constructor(element, [options])
    element: The DOM element to attach Hammer to.
    options: Configuration object for Hammer.

  VERSION: string
    The current version of Hammer.js.

  defaults: object
    Global default options for Hammer.
    - domEvents: boolean
    - enable: boolean
    - inputClass: Input class to use.
    - inputTarget: DOM element to listen for input events on.
    - preset: Array of recognizer configurations.
    - touchAction: CSS touch-action property value.

  defaults.cssProps: object
    Default CSS properties to manage touch behavior.
    - contentZooming: CSS property for content zooming.
    - tapHighlightColor: CSS property for tap highlight color.
    - touchCallout: CSS property for touch callout.
    - touchSelect: CSS property for touch selection.
    - userDrag: CSS property for user drag behavior.
    - userSelect: CSS property for user selection.
```

----------------------------------------

TITLE: Google Analytics Initialization
DESCRIPTION: Initializes Google Analytics tracking for the Hammer.js website. This script configures the analytics object and sends the initial page view.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/input_touchmouse.js.html#_snippet_0

LANGUAGE: javascript
CODE:
```
var config = {"monospaceLinks":false,"cleverLinks":false,"default":{"outputSourceFiles":true},"applicationName":"Hammer.js","disqus":"","googleAnalytics":"","openGraph":{"title":"","type":"website","image":"","site_name":"","url":""},"meta":{"title":"Hammer.js API","description":"","keyword":""}};
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){ (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o), m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m) })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
ga('create', 'UA-30289566-1', 'auto');
ga('send', 'pageview');
```

----------------------------------------

TITLE: Recognizer Defaults
DESCRIPTION: Static properties providing default configuration options for various recognizer classes.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/TouchMouseInput.html#_snippet_4

LANGUAGE: APIDOC
CODE:
```
Recognizer Defaults:

- PressRecognizer.defaults
- RotateRecognizer.defaults
- SwipeRecognizer.defaults
- TapRecognizer.defaults

These static properties expose the default configuration objects for their respective recognizer classes, allowing for inspection or modification of default behaviors.
```

----------------------------------------

TITLE: Hammer.js Core API
DESCRIPTION: Provides the main Hammer instance for gesture detection and management. Includes core methods for adding/removing recognizers, event handling, and configuration.

SOURCE: https://github.com/hammerjs/hammerjs.github.io/blob/master/jsdoc/utils.js.html#_snippet_1

LANGUAGE: APIDOC
CODE:
```
Hammer:
  __constructor(element, [options])
    element: The DOM element to attach Hammer to.
    options: Configuration object for Hammer.

  VERSION: string
    The current version of Hammer.js.

  defaults: object
    Global default options for Hammer.
    - domEvents: boolean
    - enable: boolean
    - inputClass: Input class to use.
    - inputTarget: DOM element to listen for input events on.
    - preset: Array of recognizer configurations.
    - touchAction: CSS touch-action property value.

  defaults.cssProps: object
    Default CSS properties to manage touch behavior.
    - contentZooming: CSS property for content zooming.
    - tapHighlightColor: CSS property for tap highlight color.
    - touchCallout: CSS property for touch callout.
    - touchSelect: CSS property for touch selection.
    - userDrag: CSS property for user drag behavior.
    - userSelect: CSS property for user selection.
```
