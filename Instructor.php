<?php
// Start by loading the controller
require_once __DIR__ . "/controllers/InstructorController.php";

// Create controller instance
$controller = new InstructorController();

// Load dashboard
$controller->dashboard();
