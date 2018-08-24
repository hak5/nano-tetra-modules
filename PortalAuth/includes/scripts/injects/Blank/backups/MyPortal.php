<?php namespace evilportal;

class MyPortal extends Portal
{

    public function handleAuthorization()
    {
	// Call parent to handle basic authorization first
	parent::handleAuthorization();

	// Check for other form data here
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