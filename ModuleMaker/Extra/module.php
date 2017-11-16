<?php namespace pineapple;


/* The class name must be the name of your module, without spaces. */
/* It must also extend the "Module" class. This gives your module access to API functions */
class _MODULE_NAME extends Module
{
    public function route()
    {
        switch ($this->request->action) {
            case 'getContents':    // If you request the action "getContents" from your Javascript, this is where the PHP will see it, and use the correct function
            $this->getContents();  // $this->getContents(); refers to your private function that contains all of the code for your request.
            break;                 // Break here, and add more cases after that for different requests.
        }
    }

    private function getContents()  // This is the function that will be executed when you send the request "getContents".
    {
        $this->response = array("success" => true,    // define $this->response. We can use an array to set multiple responses.
                                "greeting" => "Hey there!",
                                "content" => "This is the HTML template for your new module! The example shows you the basics of using HTML, AngularJS and PHP to seamlessly pass information to and from Javascript and PHP and output it to HTML.");
    }
}




