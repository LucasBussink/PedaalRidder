<?php
session_start();
require_once '../src/authentication.php';
require_once '../src/appointments.php';

header('Content-Type: application/json');

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => 'Geen toegang']);
  exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || !isset($data['id'])) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Ongeldige invoer']);
  exit();
}

$id = (int) $data['id'];
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Ongeldige afspraak-id']);
  exit();
}

$appointments = new Appointments();
$appointment = $appointments->getById($id);

if ($appointment === null) {
  http_response_code(404);
  echo json_encode(['success' => false, 'message' => 'Afspraak niet gevonden']);
  exit();
}

$sessionEmail = (string) ($_SESSION['email'] ?? '');
$appointmentEmail = (string) ($appointment['customer_email'] ?? '');

if ($sessionEmail === '' || strcasecmp($sessionEmail, $appointmentEmail) !== 0) {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => 'Je mag alleen je eigen afspraak annuleren']);
  exit();
}

$status = strtolower((string) ($appointment['status'] ?? ''));
if ($status === 'geannuleerd') {
  echo json_encode(['success' => true, 'message' => 'Afspraak was al geannuleerd']);
  exit();
}

$begintime = $appointment['begintime'] ?? null;
if (empty($begintime)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Afspraak heeft nog geen geplande starttijd']);
  exit();
}

$startTimestamp = strtotime((string) $begintime);
if ($startTimestamp === false) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Kon starttijd niet lezen']);
  exit();
}

$secondsUntilStart = $startTimestamp - time();
$minimumSeconds = 24 * 60 * 60;

if ($secondsUntilStart <= $minimumSeconds) {
  http_response_code(400);
  echo json_encode([
    'success' => false,
    'message' => 'Annuleren kan alleen als de afspraak meer dan 24 uur in de toekomst ligt.'
  ]);
  exit();
}

$result = $appointments->cancelAppointment($id);

if ($result !== false) {
  echo json_encode(['success' => true, 'message' => 'Afspraak geannuleerd']);
} else {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Kon afspraak niet annuleren']);
}
