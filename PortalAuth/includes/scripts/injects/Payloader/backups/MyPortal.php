<?php
namespace evilportal;

class MyPortal extends Portal
{

    public function handleAuthorization()
    {
	parent::handleAuthorization();
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