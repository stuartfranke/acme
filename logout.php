<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'initialisation.php';
session_destroy();
$helper->redirect();