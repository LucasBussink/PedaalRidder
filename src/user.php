<?php

require_once 'database.php';

class User extends Database {
public function addUser($username, $password, $email)
  {
    // add user
    $userQuery = "INSERT INTO users (name, password_hash, email) VALUES (?, ?, ?)";
      $userParams = [
      $username,
      password_hash($password, PASSWORD_DEFAULT),
      $email
    ];
    $userResult = parent::voerQueryUit($userQuery, $userParams);
    
    return $userResult;
  }
}