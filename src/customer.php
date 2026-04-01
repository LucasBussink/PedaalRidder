<?php

require_once __DIR__ . '/database.php';

class Customer extends Database{
  //simple function to get the name and email from a customer id
  public function Get($id)
  {
    $query = "SELECT 

        c.name AS naam,
        c.email AS adres,
        c.phone AS telefoon
    FROM customers AS c
    WHERE c.id = ?;";

    $params = [$id];

      return parent::voerQueryUit($query, $params);
    }

  public function findByContact($name, $email, $phone)
  {
    $query = "SELECT id, name, email, phone
              FROM customers
              WHERE name = ? AND email = ? AND phone = ?
              LIMIT 1";

    $result = parent::voerQueryUit($query, [$name, $email, $phone]);
    return !empty($result) ? $result[0] : null;
  }

  public function createCustomer($name, $email, $phone)
  {
    $query = "INSERT INTO customers (name, email, phone)
              VALUES (?, ?, ?)";

    parent::voerQueryUit($query, [$name, $email, $phone]);
    return parent::getLastInsertId();
  }

  public function findOrCreateCustomer($name, $email, $phone)
  {
    $customer = $this->findByContact($name, $email, $phone);

    if ($customer !== null) {
      return (int) $customer['id'];
    }

    return $this->createCustomer($name, $email, $phone);
  }

  public function getAllCustomers()
  {
    $query = "SELECT id, name, email, phone, no_show_count FROM customers";
    return parent::voerQueryUit($query);
    
  }
}