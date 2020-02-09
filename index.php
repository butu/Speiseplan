<?php

use Bube\Speiseplan\Controller\MealPlanController;

error_reporting(E_ALL);
ini_set("display_errors", 1);
require __DIR__ . '/vendor/autoload.php';


$controller = new MealPlanController();
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

switch ($action) {
    case 'api':
        echo $controller->apiAction();
        break;
    case 'blockImage':
        $src = $_GET['src'];
        $controller->blockImageAction($src);
        break;
    case 'changeImage':
        $src = $_REQUEST['src'];
        $mealName = $_REQUEST['mealName'];
        $controller->changeImageAction($mealName, $src);
        break;
    default:
        echo $controller->listAction();
}



