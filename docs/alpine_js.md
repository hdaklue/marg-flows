========================
CODE SNIPPETS
========================
TITLE: Setting Up Alpine.js Development Environment - Shell
DESCRIPTION: This snippet provides the essential shell commands for setting up the Alpine.js development environment. It covers installing project dependencies and building the project bundles, which are necessary prerequisites for development.

SOURCE: https://github.com/alpinejs/alpine/blob/main/README.md#_snippet_0

LANGUAGE: Shell
CODE:
```
npm install
npm run build
```

----------------------------------------

TITLE: Initializing Alpine.js in HTML
DESCRIPTION: This snippet demonstrates the basic HTML structure required to integrate Alpine.js into a web page. It includes the Alpine.js CDN script with a `defer` attribute in the `<head>` and a simple `<h1>` element using `x-data` and `x-text` to display a message, confirming the successful setup.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/start-here.md#_snippet_0

LANGUAGE: HTML
CODE:
```
<html>
<head>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    <h1 x-data="{ message: 'I ❤️ Alpine' }" x-text="message"></h1>
</body>
</html>
```

----------------------------------------

TITLE: Registering Alpine.js Components from a JavaScript Bundle
DESCRIPTION: This example illustrates how to register `Alpine.data` components when using a build step. It shows importing an Alpine component definition from a separate JavaScript file (`dropdown.js`) and then registering it with `Alpine.data` before starting Alpine, promoting modularity in larger applications.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/globals/alpine-data.md#_snippet_1

LANGUAGE: js
CODE:
```
import Alpine from 'alpinejs'
import dropdown from './dropdown.js'

Alpine.data('dropdown', dropdown)

Alpine.start()
```

LANGUAGE: js
CODE:
```
export default () => ({
    open: false,

    toggle() {
        this.open = ! this.open
    }
})
```

----------------------------------------

TITLE: Installing Alpine.js CSP Build via NPM
DESCRIPTION: This command demonstrates how to install the CSP-friendly Alpine.js build as a package dependency using npm, making it available for bundling into your application.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/advanced/csp.md#_snippet_1

LANGUAGE: shell
CODE:
```
npm install @alpinejs/csp
```

----------------------------------------

TITLE: Installing Alpine.js Resize Plugin via NPM
DESCRIPTION: This command installs the Alpine.js Resize plugin using npm, adding it to your project's dependencies. This is the standard method for projects using a JavaScript module bundler.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/resize.md#_snippet_1

LANGUAGE: Shell
CODE:
```
npm install @alpinejs/resize
```

----------------------------------------

TITLE: Executing Code Before Alpine.js Component Initialization
DESCRIPTION: This example shows the `init()` lifecycle method within an `Alpine.data` component. Alpine automatically executes this method before rendering the component, making it ideal for performing setup logic, such as fetching initial data or setting up event listeners, that needs to run early in the component's lifecycle.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/globals/alpine-data.md#_snippet_3

LANGUAGE: js
CODE:
```
Alpine.data('dropdown', () => ({
    init() {
        // This code will be executed before Alpine
        // initializes the rest of the component.
    }
}))
```

----------------------------------------

TITLE: Installing Focus Plugin via CDN (Alpine.js)
DESCRIPTION: This snippet demonstrates how to install the Alpine.js Focus plugin by including its CDN build via a <script> tag. It is crucial to load the plugin script before the Alpine.js core script to ensure proper initialization and functionality.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/focus.md#_snippet_0

LANGUAGE: alpine
CODE:
```
<!-- Alpine Plugins -->
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/focus@3.x.x/dist/cdn.min.js"></script>

<!-- Alpine Core -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

----------------------------------------

TITLE: Dynamically Loading Alpine.js and Custom Scripts in JavaScript
DESCRIPTION: This JavaScript snippet defines a method `evalScripts` on a DOM element (`#root`) to dynamically load Alpine.js and additional JavaScript. It supports injecting custom code via `extraJavaScript` that runs on `alpine:init` and signals readiness after `alpine:initialized` by appending a `blockquote` element. This setup is particularly useful for testing environments like Cypress.

SOURCE: https://github.com/alpinejs/alpine/blob/main/tests/cypress/spec-csp.html#_snippet_0

LANGUAGE: JavaScript
CODE:
```
let root = document.querySelector('#root');
root.evalScripts = (extraJavaScript) => {
  if (extraJavaScript) {
    let script = document.createElement('script');
    script.src = `data:text/javascript;base64,${btoa(`document.addEventListener('alpine:init', () => { ${extraJavaScript} })`)}`;
    root.after(script);
  }
  document.addEventListener('alpine:initialized', () => {
    let readyEl = document.createElement('blockquote');
    readyEl.setAttribute('alpine-is-ready', true);
    readyEl.style.width = '1px';
    readyEl.style.height = '1px';
    document.querySelector('blockquote').after(readyEl);
  });
  let script = document.createElement('script');
  script.src = '/../../packages/csp/dist/cdn.js';
  root.after(script);
};
```

----------------------------------------

TITLE: Updating x-if and x-transition Usage in Alpine.js
DESCRIPTION: Alpine.js V3 removes support for `x-transition` when used with `x-if` due to system complexity and low usage. Transitions are now exclusively supported with `x-show` for improved maintainability. The 'before' example shows the deprecated `x-if.transition`, while the 'after' example demonstrates the correct `x-show` and `x-transition` combination.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/upgrade-guide.md#_snippet_4

LANGUAGE: Alpine.js
CODE:
```
<!-- 🚫 Before -->
<template x-if.transition="open">
    <div>...</div>
</template>

<!-- ✅ After -->
<div x-show="open" x-transition>...</div>
```

----------------------------------------

TITLE: Initializing Alpine Mask Plugin in JavaScript Bundle
DESCRIPTION: After installing via NPM, this JavaScript snippet illustrates how to import Alpine.js and the Mask plugin, then register the plugin with Alpine. This setup is necessary for the plugin to be available and functional within your bundled application.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/mask.md#_snippet_2

LANGUAGE: javascript
CODE:
```
import Alpine from 'alpinejs'
import mask from '@alpinejs/mask'

Alpine.plugin(mask)

...
```

----------------------------------------

TITLE: Initializing Alpine.js CSP Build from NPM Bundle
DESCRIPTION: After installing via npm, this JavaScript snippet illustrates how to import the CSP-friendly Alpine.js build into your project and initialize it, making Alpine.js available globally.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/advanced/csp.md#_snippet_2

LANGUAGE: javascript
CODE:
```
import Alpine from '@alpinejs/csp'

window.Alpine = Alpine

Alpine.start()
```

----------------------------------------

TITLE: Importing and Initializing Alpine.js as a Module
DESCRIPTION: This JavaScript snippet demonstrates how to import Alpine.js into a module bundle and initialize it. It imports the `Alpine` object, optionally exposes it globally for debugging, and then calls `Alpine.start()` to activate Alpine.js on the page. Ensure `Alpine.start()` is called only once per page.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/essentials/installation.md#_snippet_3

LANGUAGE: JavaScript
CODE:
```
import Alpine from 'alpinejs'

window.Alpine = Alpine

Alpine.start()
```

----------------------------------------

TITLE: Installing Focus Plugin via NPM (Shell)
DESCRIPTION: This command installs the Alpine.js Focus plugin using npm, making it available for bundling into your JavaScript project. It is a prerequisite for initializing the plugin in your application's JavaScript code.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/focus.md#_snippet_1

LANGUAGE: shell
CODE:
```
npm install @alpinejs/focus
```

----------------------------------------

TITLE: Installing Alpine.js Anchor Plugin via NPM
DESCRIPTION: This command installs the Alpine.js Anchor plugin package using npm, making it available for use in a bundled JavaScript application.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/anchor.md#_snippet_1

LANGUAGE: Shell
CODE:
```
npm install @alpinejs/anchor
```

----------------------------------------

TITLE: Auto-evaluating init() Method with Alpine.data() (JavaScript)
DESCRIPTION: Shows how the `init()` method within a component registered via `Alpine.data()` is automatically evaluated when each instance of that component is initialized, ensuring consistent setup logic for reusable components.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/directives/init.md#_snippet_5

LANGUAGE: JavaScript
CODE:
```
Alpine.data('dropdown', () => ({
    init() {
        console.log('I will get evaluated when initializing each "dropdown" component.')
    },
}))
```

----------------------------------------

TITLE: Installing Alpine.js Collapse Plugin via NPM
DESCRIPTION: This command installs the Alpine.js Collapse plugin using npm, making it available for bundling into a JavaScript application. It's a prerequisite for using the plugin in a module-based project.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/collapse.md#_snippet_1

LANGUAGE: Shell
CODE:
```
npm install @alpinejs/collapse
```

----------------------------------------

TITLE: Installing Alpine.js Morph Plugin via NPM
DESCRIPTION: This command installs the Alpine.js Morph plugin using npm, making it available for bundling into your project.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/morph.md#_snippet_1

LANGUAGE: shell
CODE:
```
npm install @alpinejs/morph
```

----------------------------------------

TITLE: Installing Alpine Sort Plugin via NPM
DESCRIPTION: This command shows how to install the Alpine Sort plugin into your project using the Node Package Manager (npm). This is the preferred method when using a module bundler.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/sort.md#_snippet_1

LANGUAGE: shell
CODE:
```
npm install @alpinejs/sort
```

----------------------------------------

TITLE: Migrating to `Alpine.data()` for Data Providers in Alpine.js
DESCRIPTION: This snippet illustrates the shift from using global functions as Alpine.js data providers to the preferred `Alpine.data()` method. The 'Before' example shows a global `dropdown()` function, while the 'After' example uses `Alpine.data()` defined within an `alpine:init` event listener, which is the recommended approach for defining reusable data components.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/upgrade-guide.md#_snippet_12

LANGUAGE: Alpine.js
CODE:
```
<!-- 🚫 Before -->
<div x-data="dropdown()">
    ...
</div>
```

LANGUAGE: JavaScript
CODE:
```
function dropdown() {
        return {
            ...
        }
    }
```

LANGUAGE: Alpine.js
CODE:
```
<!-- ✅ After -->
<div x-data="dropdown">
    ...
</div>
```

LANGUAGE: JavaScript
CODE:
```
document.addEventListener('alpine:init', () => {
        Alpine.data('dropdown', () => ({
            ...
        }))
    })
```

----------------------------------------

TITLE: Creating a Simple Counter with Alpine.js
DESCRIPTION: This Alpine.js snippet creates a basic interactive counter component. It uses `x-data` to declare a `count` variable initialized to 0, `x-on:click` on a button to increment the count, and `x-text` on a `<span>` to display the current value of `count`, demonstrating state management and event handling.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/start-here.md#_snippet_1

LANGUAGE: HTML
CODE:
```
<div x-data="{ count: 0 }">
    <button x-on:click="count++">Increment</button>

    <span x-text="count"></span>
</div>
```

----------------------------------------

TITLE: Installing Alpine.js via NPM
DESCRIPTION: This command installs Alpine.js as a package dependency using npm, the Node.js package manager. This method is preferred for projects using module bundlers like Webpack or Rollup, providing a more robust and manageable approach to dependency management.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/essentials/installation.md#_snippet_2

LANGUAGE: Shell
CODE:
```
npm install alpinejs
```

----------------------------------------

TITLE: Interactive Demo of $refs and x-ref within x-data (Alpine.js)
DESCRIPTION: This example showcases the `x-ref` and `$refs` functionality within a typical Alpine.js setup, including the necessary `x-data` directive on a parent element. It illustrates a button triggering the removal of a referenced `div` element, highlighting the practical application of direct DOM access and the dependency on `x-data`.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/directives/ref.md#_snippet_1

LANGUAGE: html
CODE:
```
<div x-data>
        <button @click="$refs.text.remove()">Remove Text</button>

        <div class="pt-4" x-ref="text">Hello 👋</div>
    </div>
```

----------------------------------------

TITLE: Live Demo Example of x-text in Alpine.js
DESCRIPTION: This snippet provides a complete HTML structure for a live demonstration of the `x-text` directive. It mirrors the basic example, showcasing how `x-text` dynamically updates the `<strong>` tag's content with the `username` data property within an Alpine.js component, typically used for interactive previews.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/directives/text.md#_snippet_1

LANGUAGE: HTML
CODE:
```
<div class="demo">
    <div x-data="{ username: 'calebporzio' }">
        Username: <strong x-text="username"></strong>
    </div>
</div>
```

----------------------------------------

TITLE: Including Alpine.js via CDN Script Tag (General)
DESCRIPTION: This snippet demonstrates the simplest way to include Alpine.js in an HTML page by adding a deferred script tag to the <head>. It uses @3.x.x to pull the latest version of Alpine 3, suitable for development or when automatic updates are desired. The `defer` attribute ensures the script executes after the HTML is parsed.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/essentials/installation.md#_snippet_0

LANGUAGE: HTML
CODE:
```
<html>
    <head>
        ...

        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </head>
    ...
</html>
```

----------------------------------------

TITLE: Installing Alpine.js Resize Plugin via CDN
DESCRIPTION: This snippet demonstrates how to include the Alpine.js Resize plugin using a CDN. The plugin script must be loaded before the Alpine.js core script to ensure proper initialization. This method is suitable for quick integration without a build step.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/resize.md#_snippet_0

LANGUAGE: Alpine.js
CODE:
```
<!-- Alpine Plugins -->
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/resize@3.x.x/dist/cdn.min.js"></script>

<!-- Alpine Core -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

----------------------------------------

TITLE: Installing Alpine Persist Plugin via NPM
DESCRIPTION: This command installs the Alpine Persist plugin package using npm, making it available for use in a bundled JavaScript application. It's the first step for integrating the plugin into a modern JavaScript project.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/persist.md#_snippet_1

LANGUAGE: Shell
CODE:
```
npm install @alpinejs/persist
```

----------------------------------------

TITLE: Installing Intersect Plugin via NPM (Shell)
DESCRIPTION: This command installs the Alpine.js Intersect plugin using npm. It adds the `@alpinejs/intersect` package to your project's dependencies, allowing it to be imported and used in your JavaScript bundle.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/intersect.md#_snippet_1

LANGUAGE: Shell
CODE:
```
npm install @alpinejs/intersect
```

----------------------------------------

TITLE: Basic Counter Component with Alpine.js CSP Build
DESCRIPTION: This complete HTML example demonstrates a working counter component using the CSP-friendly Alpine.js build. It includes a Content-Security-Policy meta tag with a nonce, and registers the component's data and methods using `Alpine.data`.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/advanced/csp.md#_snippet_3

LANGUAGE: html
CODE:
```
<html>
    <head>
        <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'nonce-a23gbfz9e'">

        <script defer nonce="a23gbfz9e" src="https://cdn.jsdelivr.net/npm/@alpinejs/csp@3.x.x/dist/cdn.min.js"></script>
    </head>

    <body>
        <div x-data="counter">
            <button x-on:click="increment"></button>

            <span x-text="count"></span>
        </div>

        <script nonce="a23gbfz9e">
            document.addEventListener('alpine:init', () => {
                Alpine.data('counter', () => {
                    return {
                        count: 1,

                        increment() {
                            this.count++;
                        },
                    }
                })
            })
        </script>
    </body>
</html>
```

----------------------------------------

TITLE: Using @ Shorthand for Event Listening (Alpine.js)
DESCRIPTION: This example illustrates the shorthand syntax `@` as an alternative to `x-on` for event binding in Alpine.js. It achieves the same functionality as `x-on:click` but with a more concise syntax.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/essentials/events.md#_snippet_1

LANGUAGE: HTML
CODE:
```
<button @click="...">...</button>
```

----------------------------------------

TITLE: Initializing Alpine Store with init() - Alpine.js
DESCRIPTION: This snippet demonstrates the use of the `init()` method within an Alpine store definition. The `init()` method is executed immediately after the store is registered, allowing for initial state setup, such as setting the `darkMode.on` property based on the user's system color scheme preference.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/globals/alpine-store.md#_snippet_5

LANGUAGE: alpine
CODE:
```
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('darkMode', {
            init() {
                this.on = window.matchMedia('(prefers-color-scheme: dark)').matches
            },

            on: false,

            toggle() {
                this.on = ! this.on
            }
        })
    })
</script>
```

----------------------------------------

TITLE: Alpine.js Transition Demo with TailwindCSS (Verbatim)
DESCRIPTION: This HTML snippet provides a complete, runnable demonstration of Alpine.js transitions, applying TailwindCSS utility classes for visual effects. It uses x-show to toggle an element's visibility and x-transition directives to control its entry and exit animations, including transform for scaling effects. This verbatim example is suitable for direct integration into a web page to observe the transition behavior.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/directives/transition.md#_snippet_10

LANGUAGE: html
CODE:
```
<div class="demo">
    <div x-data="{ open: false }">
    <button @click="open = ! open">Toggle</button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-90"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-90"
    >Hello 👋</div>
</div>
</div>
```

----------------------------------------

TITLE: Programmatic Access to x-model Properties in Alpine.js
DESCRIPTION: This example illustrates programmatic access to `x-model` bound properties using the `_x_model` utility. It shows how to get the current value of the `username` property using `_x_model.get()` and how to set a new value using `_x_model.set()`, allowing for dynamic manipulation of `x-model` data outside of direct input interaction.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/directives/model.md#_snippet_20

LANGUAGE: Alpine.js
CODE:
```
<div x-data="{ username: 'calebporzio' }">
    <div x-ref="div" x-model="username"></div>

    <button @click="$refs.div._x_model.set('phantomatrix')">
        Change username to: 'phantomatrix'
    </button>

    <span x-text="$refs.div._x_model.get()"></span>
</div>
```

----------------------------------------

TITLE: Initializing Alpine.js Resize Plugin with NPM
DESCRIPTION: This JavaScript snippet shows how to import and register the Alpine.js Resize plugin with Alpine's core library after installing via NPM. The `Alpine.plugin()` method integrates the resize functionality into your Alpine.js application.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/resize.md#_snippet_2

LANGUAGE: JavaScript
CODE:
```
import Alpine from 'alpinejs'
import resize from '@alpinejs/resize'

Alpine.plugin(resize)

...
```

----------------------------------------

TITLE: Building a Dynamic Search Input with Alpine.js
DESCRIPTION: This comprehensive snippet demonstrates building a dynamic search filter using Alpine.js. It defines data properties (search, items) and a computed property (filteredItems) within x-data. x-model binds the input to the search property, and x-for iterates over the filteredItems to dynamically render a list, showcasing reactive filtering.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/start-here.md#_snippet_8

LANGUAGE: Alpine.js
CODE:
```
<div
    x-data="{
        search: '',

        items: ['foo', 'bar', 'baz'],

        get filteredItems() {
            return this.items.filter(
                i => i.startsWith(this.search)
            )
        }
    }"
>
    <input x-model="search" placeholder="Search...">

    <ul>
        <template x-for="item in filteredItems" :key="item">
            <li x-text="item"></li>
        </template>
    </ul>
</div>
```

----------------------------------------

TITLE: Installing Alpine Persist Plugin via CDN
DESCRIPTION: This snippet demonstrates how to include the Alpine Persist plugin and Alpine.js core library using CDN links. The Persist plugin script must be loaded before the Alpine core script to ensure proper initialization and functionality.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/persist.md#_snippet_0

LANGUAGE: HTML
CODE:
```
<!-- Alpine Plugins -->
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>

<!-- Alpine Core -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

----------------------------------------

TITLE: Full Example of Nested Alpine.js Dialogs with x-trap
DESCRIPTION: This comprehensive example demonstrates nested dialogs using `x-trap` for focus management. It includes input fields to show focus trapping in action and uses `@keyup.escape.window` to allow closing dialogs with the Escape key. The outer dialog becomes visually dimmed when the inner dialog is open.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/focus.md#_snippet_5

LANGUAGE: Alpine.js
CODE:
```
<div x-data="{ open: false }" class="demo">
    <div :class="open && 'opacity-50'">
        <button x-on:click="open = true">Open Dialog</button>
    </div>

    <div x-show="open" x-trap="open" class="p-4 mt-4 space-y-4 bg-yellow-100 border" @keyup.escape.window="open = false">
        <div>
            <input type="text" placeholder="Some input...">
        </div>

        <div>
            <input type="text" placeholder="Some other input...">
        </div>

        <div x-data="{ open: false }">
            <div :class="open && 'opacity-50'">
                <button x-on:click="open = true">Open Nested Dialog</button>
            </div>

            <div x-show="open" x-trap="open" class="p-4 mt-4 space-y-4 bg-yellow-200 border border-gray-500" @keyup.escape.window="open = false">
                <strong>
                    <div>Focus is now "trapped" inside this nested dialog. You cannot focus anything inside the outer dialog while this is open. If you close this dialog, focus will be returned to the last known active element.</div>
                </strong>

                <div>
                    <input type="text" placeholder="Some input...">
                </div>

                <div>
                    <input type="text" placeholder="Some other input...">
                </div>

                <div>
                    <button @click="open = false">Close Nested Dialog</button>
                </div>
            </div>
        </div>

        <div>
            <button @click="open = false">Close Dialog</button>
        </div>
    </div>
</div>
```

----------------------------------------

TITLE: Auto-evaluating init() Method in x-data (Alpine.js)
DESCRIPTION: Explains that if an `x-data` object contains an `init()` method, Alpine.js will automatically invoke this method during the component's initialization, providing a structured way to run setup code.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/directives/init.md#_snippet_4

LANGUAGE: Alpine.js
CODE:
```
<div x-data="{
    init() {
        console.log('I am called automatically')
    }
}">
    ...
</div>
```

----------------------------------------

TITLE: Initializing Alpine Sort Plugin in JavaScript Bundle
DESCRIPTION: After installing via NPM, this JavaScript snippet illustrates how to import the Alpine Sort plugin and register it with Alpine.js. This step is necessary to make the `x-sort` directives available in your application.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/sort.md#_snippet_2

LANGUAGE: js
CODE:
```
import Alpine from 'alpinejs'
import sort from '@alpinejs/sort'

Alpine.plugin(sort)

...
```

----------------------------------------

TITLE: Installing Alpine Mask Plugin via NPM
DESCRIPTION: This command shows how to install the Alpine Mask plugin using npm, the Node.js package manager. This method is suitable for projects that use a build system and bundle their JavaScript dependencies.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/mask.md#_snippet_1

LANGUAGE: shell
CODE:
```
npm install @alpinejs/mask
```

----------------------------------------

TITLE: Live Example of Custom SortableJS Configuration in Alpine.js HTML
DESCRIPTION: This Alpine.js HTML snippet provides a runnable example of `x-sort` with custom SortableJS configuration. The `x-sort:config="{ animation: 0 }"` directive disables sorting animation, while `x-data` initializes Alpine.js and `x-sort:item` defines sortable elements, styled with `cursor-pointer`.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/sort.md#_snippet_20

LANGUAGE: HTML
CODE:
```
<div x-data>
    <ul x-sort x-sort:config="{ animation: 0 }">
        <li x-sort:item class="cursor-pointer">foo</li>
        <li x-sort:item class="cursor-pointer">bar</li>
        <li x-sort:item class="cursor-pointer">baz</li>
    </ul>
</div>
```

----------------------------------------

TITLE: Defining Computed Properties with Getters in Alpine.js (JavaScript)
DESCRIPTION: This snippet demonstrates how to define reactive data properties within an Alpine.js x-data object. It shows a simple `items` array and a `filteredItems` getter. The `get` keyword allows `filteredItems` to be accessed like a regular property, but its value is dynamically computed based on `this.items` and `this.search`, ensuring reactivity.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/start-here.md#_snippet_10

LANGUAGE: JavaScript
CODE:
```
{
    ...
    items: ['foo', 'bar', 'baz'],

    get filteredItems() {
        return this.items.filter(
            i => i.startsWith(this.search)
        )
    }
}
```

----------------------------------------

TITLE: Installing Alpine.js Anchor Plugin via CDN
DESCRIPTION: This snippet demonstrates how to include the Alpine.js Anchor plugin using a CDN. The plugin script must be loaded before the Alpine.js core script to ensure proper initialization and functionality.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/anchor.md#_snippet_0

LANGUAGE: Alpine.js
CODE:
```
<!-- Alpine Plugins -->
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/anchor@3.x.x/dist/cdn.min.js"></script>

<!-- Alpine Core -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

----------------------------------------

TITLE: Staggered Enter/Leave Delay Transition with Alpine.js x-transition:delay
DESCRIPTION: This snippet illustrates a staggered delay where the enter transition starts after 250ms and the leave transition starts immediately, using `x-transition:enter.delay.250ms` and `x-transition:leave.delay.0ms`.

SOURCE: https://github.com/alpinejs/alpine/blob/main/tests/cypress/manual-transition-test.html#_snippet_12

LANGUAGE: HTML
CODE:
```
x-transition:enter.delay.250ms x-transition:leave.delay.0ms
```

----------------------------------------

TITLE: Including Alpine.js via CDN Script Tag (Specific Version)
DESCRIPTION: This snippet shows how to include a specific, hardcoded version of Alpine.js (e.g., 3.14.9) using a deferred script tag. Hardcoding the version is recommended for production environments to ensure stability and prevent unexpected breaking changes from new releases. The `defer` attribute ensures the script executes after the HTML is parsed.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/essentials/installation.md#_snippet_1

LANGUAGE: HTML
CODE:
```
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
```

----------------------------------------

TITLE: Installing Alpine.js Collapse Plugin via CDN
DESCRIPTION: This snippet demonstrates how to include the Alpine.js Collapse plugin and Alpine.js core library using CDN links. The Collapse plugin script must be loaded before the Alpine.js core script to ensure proper initialization and functionality.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/collapse.md#_snippet_0

LANGUAGE: Alpine.js
CODE:
```
<!-- Alpine Plugins -->
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>

<!-- Alpine Core -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

----------------------------------------

TITLE: Installing Alpine.js Morph Plugin via CDN
DESCRIPTION: This snippet demonstrates how to include the Alpine.js Morph plugin using a CDN. The plugin script must be loaded before the core Alpine.js script to ensure proper initialization and functionality.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/morph.md#_snippet_0

LANGUAGE: alpine
CODE:
```
<!-- Alpine Plugins -->
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/morph@3.x.x/dist/cdn.min.js"></script>

<!-- Alpine Core -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

----------------------------------------

TITLE: Listening for Key Combinations (Alpine.js)
DESCRIPTION: This example demonstrates combining multiple key modifiers to listen for specific key combinations, such as `shift` and `enter`. The action will only execute when both keys are pressed simultaneously and then released.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/essentials/events.md#_snippet_3

LANGUAGE: HTML
CODE:
```
<input @keyup.shift.enter="...">
```

----------------------------------------

TITLE: Live Example of Alpine.js Sortable Items with Drag Handles
DESCRIPTION: This HTML snippet provides a complete, runnable example of Alpine.js x-sort with x-sort:handle directives. It shows how to make specific parts of list items draggable, enhancing user interaction by limiting the draggable area.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/sort.md#_snippet_8

LANGUAGE: html
CODE:
```
<div x-data>
    <ul x-sort>
        <li x-sort:item>
            <span x-sort:handle class="cursor-pointer"> - </span>foo
        </li>
        <li x-sort:item>
            <span x-sort:handle class="cursor-pointer"> - </span>bar
        </li>
        <li x-sort:item>
            <span x-sort:handle class="cursor-pointer"> - </span>baz
        </li>
    </ul>
</div>
```

----------------------------------------

TITLE: Manually Starting Alpine.js V3 After NPM Import
DESCRIPTION: This snippet highlights the new requirement in Alpine.js V3 to explicitly call Alpine.start() after importing it as an NPM module. This change does not affect users who include Alpine via CDN or build files.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/upgrade-guide.md#_snippet_2

LANGUAGE: JavaScript
CODE:
```
// 🚫 Before
import 'alpinejs'

// ✅ After
import Alpine from 'alpinejs'

window.Alpine = Alpine

Alpine.start()
```

----------------------------------------

TITLE: Initializing Focus Plugin via NPM (JavaScript)
DESCRIPTION: This JavaScript snippet shows how to import and initialize the Alpine.js Focus plugin after it has been installed via npm. The Alpine.plugin() method registers the focus plugin with Alpine.js, enabling its directives and functionalities.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/focus.md#_snippet_2

LANGUAGE: js
CODE:
```
import Alpine from 'alpinejs'
import focus from '@alpinejs/focus'

Alpine.plugin(focus)

...
```

----------------------------------------

TITLE: Installing Alpine.js CSP Build via CDN
DESCRIPTION: This snippet shows how to include the CSP-friendly Alpine.js build directly into an HTML page using a script tag from a Content Delivery Network (CDN). It defers script execution to ensure the DOM is ready.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/advanced/csp.md#_snippet_0

LANGUAGE: html
CODE:
```
<!-- Alpine's CSP-friendly Core -->
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/csp@3.x.x/dist/cdn.min.js"></script>
```

----------------------------------------

TITLE: Initializing Intersect Plugin (JavaScript)
DESCRIPTION: This JavaScript snippet shows how to initialize the Alpine.js Intersect plugin after installing it via NPM. It imports both Alpine.js and the Intersect plugin, then registers the plugin with Alpine using `Alpine.plugin()`.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/intersect.md#_snippet_2

LANGUAGE: JavaScript
CODE:
```
import Alpine from 'alpinejs'
import intersect from '@alpinejs/intersect'

Alpine.plugin(intersect)

...
```

----------------------------------------

TITLE: Full Example of Alpine.js Dialog with x-trap.noscroll
DESCRIPTION: This complete example demonstrates the `.noscroll` modifier in action, preventing the page from scrolling when the dialog is open. It includes a button to open the dialog and content within the dialog to illustrate the effect of the modifier.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/focus.md#_snippet_8

LANGUAGE: Alpine.js
CODE:
```
<div class="demo">
    <div x-data="{ open: false }">
        <button @click="open = true">Open Dialog</button>

        <div x-show="open" x-trap.noscroll="open" class="p-4 mt-4 border">
            <div class="mb-4 text-bold">Dialog Contents</div>

            <p class="mb-4 text-sm text-gray-600">Notice how you can no longer scroll on this page while this dialog is open.</p>

            <button class="mt-4" @click="open = false">Close Dialog</button>
        </div>
    </div>
</div>
```

----------------------------------------

TITLE: Live Example of Alpine.js Sortable List with Ghost Elements
DESCRIPTION: This HTML snippet provides a complete, runnable example of Alpine.js x-sort with the .ghost modifier. It illustrates how a 'ghost' element appears in the original item's position during a drag operation, improving visual feedback.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/sort.md#_snippet_10

LANGUAGE: html
CODE:
```
<div x-data>
    <ul x-sort.ghost>
        <li x-sort:item class="cursor-pointer">foo</li>
        <li x-sort:item class="cursor-pointer">bar</li>
        <li x-sort:item class="cursor-pointer">baz</li>
    </ul>
</div>
```

----------------------------------------

TITLE: Applying Transitions to x-show Elements - Alpine.js
DESCRIPTION: This example shows how to combine `x-show` with `x-transition` to add smooth animations when an element is shown or hidden. The `x-transition` directive automatically applies CSS transitions, enhancing the user experience during visibility changes.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/directives/show.md#_snippet_1

LANGUAGE: Alpine
CODE:
```
<div x-data="{ open: false }">
    <button x-on:click="open = ! open">Toggle Dropdown</button>

    <div x-show="open" x-transition>
        Dropdown Contents...
    </div>
</div>
```

----------------------------------------

TITLE: Live Example of x-sort Hover Bug in Alpine.js HTML
DESCRIPTION: This Alpine.js HTML snippet provides a runnable example demonstrating the CSS hover bug. It uses `x-data` for Alpine.js initialization, `x-sort` for the sortable list, and `x-sort:item` for individual sortable list items, styled with Tailwind CSS classes like `hover:border` and `cursor-pointer`.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/sort.md#_snippet_16

LANGUAGE: HTML
CODE:
```
<div x-data>
    <ul x-sort class="flex flex-col items-start">
        <li x-sort:item class="border-black cursor-pointer hover:border">foo</li>
        <li x-sort:item class="border-black cursor-pointer hover:border">bar</li>
        <li x-sort:item class="border-black cursor-pointer hover:border">baz</li>
    </ul>
</div>
```

----------------------------------------

TITLE: Implementing Basic Sortable List with Alpine.js
DESCRIPTION: This example demonstrates the fundamental usage of the Alpine Sort plugin. By applying `x-sort` to a parent element and `x-sort:item` to its children, the children become draggable and reorderable within the list.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/sort.md#_snippet_3

LANGUAGE: alpine
CODE:
```
<ul x-sort>
    <li x-sort:item>foo</li>
    <li x-sort:item>bar</li>
    <li x-sort:item>baz</li>
</ul>
```

LANGUAGE: alpine
CODE:
```
<div x-data>
    <ul x-sort>
        <li x-sort:item class="cursor-pointer">foo</li>
        <li x-sort:item class="cursor-pointer">bar</li>
        <li x-sort:item class="cursor-pointer">baz</li>
    </ul>
</div>
```

----------------------------------------

TITLE: Advanced x-trap.noreturn Example with Outside Click and Escape Key (Alpine.js)
DESCRIPTION: This more comprehensive example showcases `x-trap.noreturn` in a search dropdown context. It includes additional Alpine.js directives like `@click.outside` and `@keyup.escape` to manage the dropdown's open state, ensuring that focus is not returned to the input field when the dropdown closes, regardless of the closing method.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/focus.md#_snippet_10

LANGUAGE: html
CODE:
```
<div class="demo">
    <div
        x-data="{ open: false }"
        x-trap.noreturn="open"
        @click.outside="open = false"
        @keyup.escape.prevent.stop="open = false"
    >
        <input type="search" placeholder="search for something"
            @focus="open = true"
            @keyup.escape.prevent="$el.blur()"
        />

        <div x-show="open">
            <div class="mb-4 text-bold">Search results</div>

            <p class="mb-4 text-sm text-gray-600">Notice when closing this dropdown, focus is not returned to the input.</p>

            <button class="mt-4" @click="open = false">Close Dialog</button>
        </div>
    </div>
</div>
```

----------------------------------------

TITLE: Building a Dropdown Component with Alpine.js
DESCRIPTION: This snippet demonstrates how to create a simple toggleable dropdown component using Alpine.js. It utilizes x-data to manage the `open` state, @click to toggle the state, and x-show to conditionally display the dropdown contents. The @click.outside modifier ensures the dropdown closes when a click occurs outside its boundaries.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/start-here.md#_snippet_5

LANGUAGE: Alpine.js
CODE:
```
<div x-data="{ open: false }">
    <button @click="open = ! open">Toggle</button>

    <div x-show="open" @click.outside="open = false">Contents...</div>
</div>
```

----------------------------------------

TITLE: Alpine.js Template for Reactive x-log Directive
DESCRIPTION: This Alpine.js template demonstrates a basic setup for a reactive `x-log` directive. It initializes a `message` variable and includes a button that modifies this variable, showcasing how changes trigger reactivity.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/advanced/extending.md#_snippet_8

LANGUAGE: alpine
CODE:
```
<div x-data="{ message: 'Hello World!' }">
    <div x-log="message"></div>

    <button @click="message = 'yolo'">Change</button>
</div>
```

----------------------------------------

TITLE: Installing Intersect Plugin via CDN (Alpine.js)
DESCRIPTION: This snippet demonstrates how to include the Alpine.js Intersect plugin using a CDN. The plugin script must be loaded before the Alpine.js core script to ensure proper initialization and functionality.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/intersect.md#_snippet_0

LANGUAGE: Alpine.js
CODE:
```
<!-- Alpine Plugins -->
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>

<!-- Alpine Core -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

----------------------------------------

TITLE: Installing Alpine Sort Plugin via CDN
DESCRIPTION: This snippet demonstrates how to include the Alpine Sort plugin and Alpine Core JavaScript files via CDN. It is crucial to include the plugin's script tag before Alpine's core script for proper initialization.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/sort.md#_snippet_0

LANGUAGE: alpine
CODE:
```
<!-- Alpine Plugins -->
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/sort@3.x.x/dist/cdn.min.js"></script>

<!-- Alpine Core -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

----------------------------------------

TITLE: Using Global Lifecycle Events for Alpine.js Initialization
DESCRIPTION: Alpine.js V3 replaces the `Alpine.deferLoadingAlpine()` function with global DOM events (`alpine:init` and `alpine:initialized`) for managing Alpine's loading lifecycle. This provides a more standard and flexible way to execute code before and after Alpine initializes. The 'before' example uses the deprecated `deferLoadingAlpine`, while 'after' shows the new event listeners.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/upgrade-guide.md#_snippet_9

LANGUAGE: JavaScript
CODE:
```
<!-- 🚫 Before -->
<script>
    window.deferLoadingAlpine = startAlpine => {
        // Will be executed before initializing Alpine.

        startAlpine()

        // Will be executed after initializing Alpine.
    }
</script>
```

LANGUAGE: JavaScript
CODE:
```
<!-- ✅ After -->
<script>
    document.addEventListener('alpine:init', () => {
        // Will be executed before initializing Alpine.
    })

    document.addEventListener('alpine:initialized', () => {
        // Will be executed after initializing Alpine.
    })
</script>
```

----------------------------------------

TITLE: Alpine.js Application Root Definition in JavaScript
DESCRIPTION: This function defines the main Alpine.js application object, which encapsulates the reactive `data` array, the `selected` item, and all the methods for manipulating the data. It serves as the central state management for the UI, demonstrating various data operations with performance measurement.

SOURCE: https://github.com/alpinejs/alpine/blob/main/benchmarks/loop.html#_snippet_1

LANGUAGE: JavaScript
CODE:
```
function app() { return { data: [], selected: undefined, add() { let start = performance.now() this.data = this.data.concat(buildData(1000)) setTimeout(() => { console.log(performance.now() - start); }, 0) }, clear() { let start = performance.now() this.data = []; this.selected = undefined; setTimeout(() => { console.log(performance.now() - start); }, 0) }, update() { let start = performance.now() for (let i = 0; i < this.data.length; i += 10) { this.data[i].label += ' !!!'; } setTimeout(() => { console.log(performance.now() - start); }, 0) }, remove(id) { let start = performance.now() const idx = this.data.findIndex(d => d.id === id); this.data.splice(idx, 1); setTimeout(() => { console.log(performance.now() - start); }, 0) }, run() { let start = performance.now() this.data = buildData(100); this.selected = undefined; setTimeout(() => { console.log(performance.now() - start); }, 0) }, runLots() { let start = performance.now() this.data = buildData(10000); this.selected = undefined; setTimeout(() => { console.log(performance.now() - start); }, 0) }, select(id) { let start = performance.now() this.selected = id setTimeout(() => { console.log(performance.now() - start); }, 0) }, swapRows() { let start = performance.now() const d = this.data; if (d.length > 998) { const tmp = d[998]; d[998] = d[1]; d[1] = tmp; } setTimeout(() => { console.log(performance.now() - start); }, 0) } } }
```

----------------------------------------

TITLE: Measuring Alpine.js Initialization Performance in JavaScript
DESCRIPTION: This JavaScript snippet measures the time taken for Alpine.js to initialize. It records the performance timestamp when the script starts and calculates the elapsed time after the 'alpine:initialized' event fires, logging the result to the console. This is useful for benchmarking Alpine.js load times.

SOURCE: https://github.com/alpinejs/alpine/blob/main/benchmarks/init.html#_snippet_0

LANGUAGE: javascript
CODE:
```
window.start = performance.now(); document.addEventListener('alpine:initialized', () => { setTimeout(() => { console.log(performance.now() - window.start); }); });
```

----------------------------------------

TITLE: Using Shorthand @ for Click Events in Alpine.js
DESCRIPTION: This example illustrates the shorthand syntax for `x-on`, using `@` instead of `x-on:`. It achieves the same functionality as the full `x-on` directive, displaying an alert when the button is clicked, offering a more concise syntax.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/directives/on.md#_snippet_1

LANGUAGE: alpine
CODE:
```
<button @click="alert('Hello World!')">Say Hi</button>
```

----------------------------------------

TITLE: Looping Elements with x-for Directive in Alpine.js (Alpine.js)
DESCRIPTION: This Alpine.js template snippet demonstrates the `x-for` directive used for iterating over a collection of data. The `x-for` directive must be placed on a `<template>` element and follows the `[item] in [items]` syntax. It dynamically renders `<li>` elements for each `item` in `filteredItems`, with `x-text` binding the item's value to the list item's content.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/start-here.md#_snippet_12

LANGUAGE: Alpine.js
CODE:
```
<ul>
    <template x-for="item in filteredItems">
        <li x-text="item"></li>
    </template>
</ul>
```

----------------------------------------

TITLE: Handling Click Events with x-on in Alpine.js
DESCRIPTION: This snippet demonstrates the `x-on` directive, used for listening to DOM events. Specifically, `x-on:click` is used to execute a JavaScript expression (`count++`) when the button is clicked, directly modifying the `count` property declared in `x-data`.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/start-here.md#_snippet_3

LANGUAGE: HTML
CODE:
```
<button x-on:click="count++">Increment</button>
```

----------------------------------------

TITLE: Initializing Alpine Persist Plugin in JavaScript
DESCRIPTION: This JavaScript snippet shows how to import Alpine.js and the Persist plugin, then register the plugin with Alpine. This setup is typical for applications using a module bundler like Webpack or Rollup, enabling the `$persist` magic method.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/persist.md#_snippet_2

LANGUAGE: JavaScript
CODE:
```
import Alpine from 'alpinejs'
import persist from '@alpinejs/persist'

Alpine.plugin(persist)

...
```

----------------------------------------

TITLE: Migrating x-init Callback Returns to $nextTick in Alpine.js
DESCRIPTION: In Alpine.js V3, `x-init` no longer automatically executes a returned function after all other directives in the tree have initialized. To achieve post-initialization execution, developers must now explicitly use `$nextTick()`. The 'before' example shows an implicit callback return, while 'after' demonstrates the required `$nextTick` wrapper for deferred execution.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/upgrade-guide.md#_snippet_6

LANGUAGE: Alpine.js
CODE:
```
<!-- 🚫 Before -->
<div x-data x-init="() => { ... }">...</div>

<!-- ✅ After -->
<div x-data x-init="$nextTick(() => { ... })">...</div>
```

----------------------------------------

TITLE: Live Example of Fixed x-sort Hover Effect in Alpine.js HTML
DESCRIPTION: This Alpine.js HTML snippet provides a runnable example of the fixed CSS hover behavior. It uses `x-data` for Alpine.js, `x-sort` for the sortable list, and `x-sort:item` with the `[body:not(.sorting)_&]:hover:border` class to ensure hover effects are correctly applied only to the currently dragged element, resolving the bug.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/plugins/sort.md#_snippet_18

LANGUAGE: HTML
CODE:
```
<div x-data>
    <ul x-sort class="flex flex-col items-start">
        <li x-sort:item class="[body:not(.sorting)_&]:hover:border border-black cursor-pointer">foo</li>
        <li x-sort:item class="[body:not(.sorting)_&]:hover:border border-black cursor-pointer">bar</li>
        <li x-sort:item class="[body:not(.sorting)_&]:hover:border border-black cursor-pointer">baz</li>
    </ul>
</div>
```

----------------------------------------

TITLE: Calling Alpine.js Methods Without Parentheses
DESCRIPTION: This example highlights an optional syntax for calling methods in Alpine.js. If a method does not require arguments, its invocation can omit the parentheses, leading to more concise event handler declarations.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/directives/data.md#_snippet_3

LANGUAGE: alpine
CODE:
```
<!-- Before -->
<button @click="toggle()">...</button>

<!-- After -->
<button @click="toggle">...</button>
```

----------------------------------------

TITLE: Running Alpine.js Project Tests - Shell
DESCRIPTION: This snippet details the shell commands for executing tests within the Alpine.js project. It includes commands to run all tests (Cypress and Jest), run only Cypress tests with its UI, and run Jest unit tests with optional command-line configurations for targeted testing.

SOURCE: https://github.com/alpinejs/alpine/blob/main/README.md#_snippet_1

LANGUAGE: Shell
CODE:
```
npm run test
npm run cypress
npm run jest
npm run jest -- --watch
```

----------------------------------------

TITLE: Using a Custom Directive with Expression (HTML)
DESCRIPTION: This HTML example illustrates how a custom directive, x-log, can receive a JavaScript expression as its value. The directive is intended to evaluate the message property from the x-data scope.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/advanced/extending.md#_snippet_6

LANGUAGE: HTML
CODE:
```
<div x-data="{ message: 'Hello World!' }">
    <div x-log="message"></div>
</div>
```

----------------------------------------

TITLE: Utility Functions for Data Generation in JavaScript
DESCRIPTION: This snippet defines helper variables and functions for generating structured data. `idCounter` ensures unique IDs, `adjectives`, `colours`, and `nouns` provide random label components, `_random` generates random indices, and `buildData` constructs an array of objects with unique IDs and descriptive labels.

SOURCE: https://github.com/alpinejs/alpine/blob/main/benchmarks/loop.html#_snippet_0

LANGUAGE: JavaScript
CODE:
```
let idCounter = 1; const adjectives = ["pretty", "large", "big", "small", "tall", "short", "long", "handsome", "plain", "quaint", "clean", "elegant", "easy", "angry", "crazy", "helpful", "mushy", "odd", "unsightly", "adorable", "important", "inexpensive", "cheap", "expensive", "fancy"], colours = ["red", "yellow", "blue", "green", "pink", "brown", "purple", "brown", "white", "black", "orange"], nouns = ["table", "chair", "house", "bbq", "desk", "car", "pony", "cookie", "sandwich", "burger", "pizza", "mouse", "keyboard"]; function _random (max) { return Math.round(Math.random() * 1000) % max; }; function buildData(count) { let data = new Array(count); for (let i = 0; i < count; i++) { data[i] = { id: idCounter++, label: `${adjectives[_random(adjectives.length)]} ${colours[_random(colours.length)]} ${nouns[_random(nouns.length)]}` } } return data; }
```

----------------------------------------

TITLE: Defining a Synchronous JavaScript Function
DESCRIPTION: This snippet defines a basic synchronous JavaScript function `getLabel` that returns a static string. It serves as an example of a standard function used with Alpine.js before introducing asynchronous behavior.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/advanced/async.md#_snippet_0

LANGUAGE: js
CODE:
```
function getLabel() {
    return 'Hello World!'
}
```

----------------------------------------

TITLE: Registering Alpine Store from a Bundle - JavaScript
DESCRIPTION: This snippet illustrates how to define an Alpine store when importing Alpine.js into a build system. The 'darkMode' store, with its `on` property and `toggle` method, is defined before `Alpine.start()` is manually called, ensuring the store is available upon application initialization.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/globals/alpine-store.md#_snippet_1

LANGUAGE: js
CODE:
```
import Alpine from 'alpinejs'

Alpine.store('darkMode', {
    on: false,

    toggle() {
        this.on = ! this.on
    }
})

Alpine.start()
```

----------------------------------------

TITLE: Consuming Alpine.js Plugin as ES Module (JavaScript)
DESCRIPTION: This JavaScript snippet demonstrates how to consume an Alpine.js plugin when it's distributed as an ES module. It imports Alpine.js and the plugin, then uses `Alpine.plugin()` to register the plugin's functionalities before starting Alpine.js.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/advanced/extending.md#_snippet_24

LANGUAGE: JavaScript
CODE:
```
import Alpine from 'alpinejs'

import foo from 'foo'
Alpine.plugin(foo)

window.Alpine = Alpine
window.Alpine.start()
```

----------------------------------------

TITLE: Listening for Events on Window Object (Alpine.js)
DESCRIPTION: This example demonstrates using the `.window` modifier to listen for events on the global `window` object. This enables communication between components that are not directly related in the DOM tree, as events bubble up to the window.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/essentials/events.md#_snippet_7

LANGUAGE: HTML
CODE:
```
<div x-data>
    <button @click="$dispatch('foo')"></button>
</div>

<div x-data @foo.window="console.log('foo was dispatched')">...</div>
```

----------------------------------------

TITLE: Declaring Component Data with x-data in Alpine.js
DESCRIPTION: This snippet illustrates the `x-data` directive, which is fundamental for declaring reactive data within an Alpine.js component. It initializes a `count` property to 0, making it available to other directives within the element and ensuring that any changes to `count` will automatically update dependent elements.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/start-here.md#_snippet_2

LANGUAGE: HTML
CODE:
```
<div x-data="{ count: 0 }">
```

----------------------------------------

TITLE: Declarative Counter Component with Alpine.js Syntax - HTML
DESCRIPTION: This example shows the same counter component implemented using Alpine.js's declarative syntax. It utilizes `x-data` to define the reactive state, `@click` for event handling, and `x-text` to bind the reactive data to the DOM, demonstrating Alpine's simplified approach to reactivity compared to the manual JavaScript implementation.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/advanced/reactivity.md#_snippet_5

LANGUAGE: HTML
CODE:
```
<div x-data="{ count: 1 }" class="demo">
    <button @click="count++">Increment</button>

    <div>Count: <span x-text="count"></span></div>
</div>
```

----------------------------------------

TITLE: Fetching Data on Initialization with x-init (Alpine.js)
DESCRIPTION: Illustrates how `x-init` can be used to asynchronously fetch JSON data from an API endpoint (`/posts`) and populate an `x-data` property (`posts`) before the Alpine.js component is fully processed, enabling data pre-loading.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/directives/init.md#_snippet_1

LANGUAGE: Alpine.js
CODE:
```
<div
    x-data="{ posts: [] }"
    x-init="posts = await (await fetch('/posts')).json()"
>...</div>
```

----------------------------------------

TITLE: Preserving Initial Classes with Object Syntax (Alpine.js)
DESCRIPTION: This example demonstrates a unique advantage of object syntax for class binding: it allows preserving initial classes defined in the `class` attribute while still toggling the same class via `x-bind:class`.

SOURCE: https://github.com/alpinejs/alpine/blob/main/packages/docs/src/en/directives/bind.md#_snippet_6

LANGUAGE: alpine
CODE:
```
<div class="hidden" :class="{ 'hidden': ! show }">
```
