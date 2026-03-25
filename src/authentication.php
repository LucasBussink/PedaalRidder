<?php
require_once 'database.php';

class Authenticate extends Database {
  public function login($email, $wachtwoord) {
    $query = "SELECT * FROM users WHERE email = ? LIMIT 1";
    $params = [$email];
    $result = parent::voerQueryUit($query, $params);

    if (count($result) > 0) {
      if (password_verify($wachtwoord, $result[0]['password_hash'])) {
        return true;
      }
    }
    return false;
  }

  public function checkIsAdmin($email) {
    if (empty($email)) {
      return false;
    }

    $query = "SELECT * FROM users WHERE email = ? LIMIT 1";
    $params = [$email];
    $result = parent::voerQueryUit($query, $params);

    if (count($result) === 0) {
      return false;
    }

    $user = $result[0];

    if (array_key_exists('admin', $user)) {
      return (bool) $user['admin'];
    }

    if (array_key_exists('role', $user)) {
      return strtolower((string) $user['role']) === 'admin';
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