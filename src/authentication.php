<?php
require_once 'database.php';

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


  public function checkEmail($email) {
    $query = "SELECT * FROM users WHERE email = ?";
    $params = [$email];
    $result = parent::voerQueryUit($query, $params);

    return count($result) > 0;
  }
}