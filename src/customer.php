<?php

include_once 'database.php';

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
  }