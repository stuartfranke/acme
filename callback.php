<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'initialisation.php';

if (isset($_GET['code']) && isset($_GET['state'])) {
    if ((bool)$helper->validateState($_GET['state']) === false) {
        $helper->addMessage(
            \Acme\Tracker\Objects\Helper::ERROR_MESSAGE_TYPE,
            'Cross site request forgery attempt'
        );
        $helper->redirect();
        exit;
    }

    if ((bool)$helper->requestAccessToken($_GET['code']) === false) {
        $helper->addMessage(
            \Acme\Tracker\Objects\Helper::ERROR_MESSAGE_TYPE,
            'Login failed, please try again'
        );
        $helper->redirect();
        exit;
    }
}

$helper->redirect();
