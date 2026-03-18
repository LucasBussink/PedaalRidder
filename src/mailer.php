<?php

/**
 * Laad .env waarden (eenvoudig KEY=VALUE formaat) in $_ENV.
 */
function loadDotEnv(): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $loaded = true;
    $envPath = dirname(__DIR__) . '/.env';

    if (!is_file($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        if ($key === '') {
            continue;
        }

        $value = trim($value, "\"'");

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }
    }
}

/**
 * Lees een waarde uit environment met fallback.
 */
function envValue(string $key, string $default = ''): string
{
    loadDotEnv();

    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string) $_ENV[$key];
    }

    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return (string) $value;
    }

    return $default;
}

/**
 * Verstuurt een reparatie-aanmelding via Web3Forms.
 *
 * @return array{success: bool, message: string}
 */
function sendRepairRegistrationMail(
    string $name,
    string $email,
    string $phone,
    string $brand,
    string $model,
    string $repairType,
    string $issue,
    ?string $scheduledStart = null,
    ?string $scheduledEnd = null
): array {
    $accessKey = envValue('WEB3FORMS_ACCESS_KEY');
    $fromName = envValue('WEB3FORMS_FROM_NAME', 'De PedaalRidder Website');
    $subject = envValue('WEB3FORMS_SUBJECT', 'Nieuwe reparatie-aanmelding');

    if ($accessKey === '') {
        return [
            'success' => false,
            'message' => 'WEB3FORMS_ACCESS_KEY ontbreekt in .env'
        ];
    }

    $slotText = 'Nog geen tijdslot ingepland';
    if (!empty($scheduledStart) && !empty($scheduledEnd)) {
        try {
            $startDt = new DateTime($scheduledStart);
            $endDt = new DateTime($scheduledEnd);
            $slotText = $startDt->format('d-m-Y H:i') . ' t/m ' . $endDt->format('H:i');
        } catch (Exception $e) {
            $slotText = $scheduledStart . ' t/m ' . $scheduledEnd;
        }
    }

    $message = "Nieuwe reparatie-aanmelding ontvangen:\n\n";
    $message .= "Naam: {$name}\n";
    $message .= "E-mail: {$email}\n";
    $message .= "Telefoon: {$phone}\n";
    $message .= "Merk: {$brand}\n";
    $message .= "Model: " . ($model !== '' ? $model : '-') . "\n";
    $message .= "Type reparatie: {$repairType}\n";
    $message .= "Probleemomschrijving: " . ($issue !== '' ? $issue : '-') . "\n";
    $message .= "Tijdslot: {$slotText}\n";

    $payload = [
        'access_key' => $accessKey,
        'from_name' => $fromName,
        'subject' => $subject,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'message' => $message,
    ];

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($payload),
            'timeout' => 15,
            'ignore_errors' => true,
        ]
    ]);

    $response = @file_get_contents('https://api.web3forms.com/submit', false, $context);

    if ($response === false) {
        return [
            'success' => false,
            'message' => 'Geen response van Web3Forms ontvangen.'
        ];
    }

    $json = json_decode($response, true);
    if (!is_array($json)) {
        return [
            'success' => false,
            'message' => 'Ongeldige response van Web3Forms.'
        ];
    }

    $ok = !empty($json['success']);
    $msg = (string) ($json['message'] ?? ($ok ? 'Mail verzonden.' : 'Mail niet verzonden.'));

    return [
        'success' => $ok,
        'message' => $msg
    ];
}

/**
 * Stuur een bevestigingsmail naar de klant na het inplannen van een afspraak.
 *
 * @param string $to          E-mailadres van de klant
 * @param string $name        Naam van de klant
 * @param string $repairType  Naam van het reparatietype
 * @param string $start       Begintijd (formaat: Y-m-d H:i:s of Y-m-d H:i)
 * @param string $end         Eindtijd  (formaat: Y-m-d H:i:s of Y-m-d H:i)
 * @return bool               true als het versturen gelukt is
 */
function sendAppointmentConfirmation(string $to, string $name, string $repairType, string $start, string $end): bool
{
    $result = sendRepairRegistrationMail(
        $name,
        $to,
        '',
        '-',
        '-',
        $repairType,
        'Automatische afspraakbevestiging',
        $start,
        $end
    );

    return $result['success'];
}
