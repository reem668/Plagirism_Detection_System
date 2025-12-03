<?php
require_once __DIR__ . '/Controllers/InstructorController.php';

use Controllers\InstructorController;

$controller = new InstructorController();
$controller->dashboard();
