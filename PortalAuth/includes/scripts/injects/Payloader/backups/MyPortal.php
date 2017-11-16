<?php
namespace evilportal;

class MyPortal extends Portal
{

    public function handleAuthorization()
    {
	parent::handleAuthorization();
    }

    public function showSuccess()
    {
        // Calls default success message
        parent::showSuccess();
    }

    public function showError()
    {
        // Calls default error message
        parent::showError();
    }
}