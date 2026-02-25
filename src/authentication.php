<?php
require 'database.php';

class Authenticate extends Database {
  public function login($gebruikersnaam, $wachtwoord) {
    $query = "SELECT * FROM users WHERE name = ?";
    $params = [$gebruikersnaam];
    $result = parent::voerQueryUit($query, $params);

    if (count($result) > 0) {
      if (password_verify($wachtwoord, $result[0]['password_hash'])) {
        return true;
      }
    }
    return false;
  }

  public function logout() {
    session_start();
    session_destroy();
  }
}