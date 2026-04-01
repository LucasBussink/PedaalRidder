<?php
// Endpoint: appointment_edit.php
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
if (!$data || !isset($data['id'], $data['begintime'], $data['endtime'], $data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ongeldige invoer']);
    exit();
}

$id = (int)$data['id'];
$begintime = $data['begintime']; // Verwacht formaat: 'YYYY-MM-DD HH:MM:SS'
$endtime = $data['endtime'];
$status = $data['status'];


$appointments = new Appointments();
$result = $appointments->updateAppointmentTimeAndStatus($id, $begintime, $endtime, $status);

// No show teller ophogen als status 'niet opgehaald' is
if ($result !== false && $status === 'niet opgehaald') {
    require_once '../src/customer.php';
    // Haal de afspraak op om customer_id te vinden
    $appointment = $appointments->getById($id);
    if ($appointment && isset($appointment['customer_id'])) {
        $customerId = $appointment['customer_id'];
        $customer = new Customer();
        // Probeer de kolom 'no_show_count' op te hogen
        $query = "UPDATE customers SET no_show_count = IFNULL(no_show_count,0) + 1 WHERE id = ?";
        $customer->voerQueryUit($query, [$customerId]);
    }
}

if ($result !== false) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kon afspraak niet bijwerken']);
}
