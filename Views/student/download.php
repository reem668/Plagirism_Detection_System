<?php
session_start();
require_once __DIR__ . '/../../Controllers/SubmissionController.php';
use Controllers\SubmissionController;

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') exit;

$id = $_GET['id'] ?? null;
if (!$id) exit;

$ctrl = new SubmissionController();
$ctrl->downloadReport($id);
