# WiFi Pineapple Module Repository

This is the module repository for the WiFi Pineapple NANO and TETRA. All the community developed modules are stored here, and developers should create pull requests for any changes to their 
modules, or module additions.

## Module Structure
A WiFi Pineapple Module is created with HTML, AngularJS and PHP. All HTML is done using the Bootstrap CSS framework, and AngularJS combined with our [PHP 
API](https://wifipineapple.github.io/wifipineapple-wiki//#!api.md) allows for asynchronus updating and easy to implement features for your module.

A basic module will request information through AngularJS to PHP, and then the PHP will provide a response to AngularJS, where it will then be displayed on the HTML page for the user to see.

```
+-------------------+         +--------------+         +-----------+         +------+
| AngularJS Request |   -->   | PHP Response |   -->   | AngularJS |   -->   | HTML |
+-------------------+         +--------------+         +-----------+         +------+
```

The structure of a module is as follows:
```
.
├── api
│   └── module.php
├── js
│   └── module.js
├── module.html
└── module.info
```

More information on creating modules can be found [here](https://wifipineapple.github.io/wifipineapple-wiki//#!creating_modules.md) while more information on the API can be found 
[here](https://wifipineapple.github.io/wifipineapple-wiki//#!api.md).

### module.info
The `module.info` file is a simple JSON array consisting of `author`, `description`, `devices`, `title`, and `version`. The `version` field will need to be updated with any pull request.

### module.html
The WiFi Pineapple modules make use of Bootstrap to provide a good mobile viewing experience and a clean look. Module developers are encouraged to make use of Bootstrap components, such as 
responsive tables and the grid system. To learn more about Bootstrap, visit the [Bootstrap Website](https://getbootstrap.com/). We also include a hook for atleast one AngularJS controller. You 
can 
learn more about AngularJS at the [AngularJS Website](https://angularjs.org/).

```
<div class="row">
    <div ng-controller="ExampleController" class="col-md-12">
        {{ hello }}
    </div>
</div>
```

### module.js
The `js/module.js` file will house the Javascript for your module, and will be the place for controller definitions, in this brief example it will be called `ExampleController`. We will also 
set a 
variable called `$scope.hello` with content we will receieve from our PHP. 

```
registerController("ExampleController", ['$api', '$scope', function($api, $scope) {
    $api.request({
        module: 'ExampleModule',
        action: 'getHello'
    }, function(response) {
        $scope.hello = response.text;
    });
}])
```

This snippet makes use of our API to send a request to our PHP with the `getHello` action, and will set it a response into the `$scope.hello` variable.

### module.php
The `api/module.php` file must be in the `pineapple` namespace, and contain a routing switch statement, for example:

```
<?php namespace pineapple;

class ExampleModule extends Module
{
    public function route()
    {
        switch ($this->request->action) {
            case 'getHello':
                $this->hello();
                break;
            }
    }
}
```

We will then need to call our function `hello()`, which should be `private` and should set a response:
```
private function hello()
{
    $this->response = array('text' => "Hello World");
}
```

**Note:** You should never use the closing `?>` PHP tag in your `module.php` file.

