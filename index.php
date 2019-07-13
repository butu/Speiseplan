<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
require __DIR__ . '/vendor/autoload.php';


$controller = new \Bube\Speiseplan\Controller\MealPlanController();
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'api':
        echo $controller->apiAction();
        break;
    case 'blockImage':
        $src = $_GET['src'];
        $controller->blockImageAction($src);
        break;
    case 'changeImage':
        $src = $_GET['src'];
        $controller->changeImageAction($src);
        break;
    default:
        echo $controller->listAction();
}



