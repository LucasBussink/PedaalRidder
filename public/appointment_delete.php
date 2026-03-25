<?php
// Endpoint: appointment_delete.php
session_start();
require_once '../src/authentication.php';
require_once '../src/appointments.php';

header('Content-Type: application/json');

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geen toegang']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ongeldige invoer']);
    exit();
}

$id = (int)$data['id'];
$appointments = new Appointments();
$result = $appointments->deleteAppointment($id);

if ($result !== false) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kon afspraak niet annuleren']);
}
