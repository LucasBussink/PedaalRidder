<?php
session_start();
require_once '../src/database.php';

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
	header('Location: new_password.php');
	exit();
}

$db = new Database();
$email = (string) ($_SESSION['reset_email'] ?? '');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_password'])) {
	$password = $_POST['password'] ?? '';
	$confirmPassword = $_POST['confirm_password'] ?? '';

	if ($password === '' || $confirmPassword === '') {
		$message = 'Vul beide wachtwoordvelden in.';
	} elseif (strlen($password) < 8) {
		$message = 'Wachtwoord moet minimaal 8 tekens bevatten.';
	} elseif ($password !== $confirmPassword) {
		$message = 'De wachtwoorden komen niet overeen.';
	} else {
		$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

		$rows = $db->voerQueryUit(
			"UPDATE users SET password_hash = ? WHERE email = ?",
			[$hashedPassword, $email]
		);

		if ($rows > 0) {
			unset($_SESSION['verified'], $_SESSION['reset_email'], $_SESSION['reset_code'], $_SESSION['reset_expires_at']);
			$_SESSION['password_reset_success'] = true;

			header('Location: login.php');
			exit();
		}

		$message = 'Wachtwoord wijzigen is mislukt. Controleer je resetflow en probeer opnieuw.';
	}
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Nieuw wachtwoord instellen</title>
	<link rel="stylesheet" href="assets/css/setNewPassword.css">
</head>

<body>
	<main>
		<h1>Nieuw wachtwoord instellen</h1>
		<p class="description">Kies een nieuw wachtwoord van minimaal 8 tekens.</p>

		<?php if ($message !== ''): ?>
			<p class="message"><?php echo htmlspecialchars($message); ?></p>
		<?php endif; ?>

		<form method="post">
			<label for="password">Nieuw wachtwoord:</label>
			<input type="password" id="password" name="password" minlength="8" required>

			<label for="confirm_password">Herhaal nieuw wachtwoord:</label>
			<input type="password" id="confirm_password" name="confirm_password" minlength="8" required>

			<button type="submit" name="set_password">Opslaan</button>
		</form>
	</main>
</body>

</html>
