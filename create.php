<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'initialisation.php';

if (isset($_POST['submit']) && $_POST['submit'] === 'create') {
    $issues = new \Acme\Tracker\Objects\Issues();
    $issues->createIssue($_POST);
}

$helper->redirect();
