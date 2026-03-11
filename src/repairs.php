<?php

require_once __DIR__ . '/database.php';

class Repairs extends Database{
  //simple function to get the name and email from a customer id
  public function GetAllRepairTypes()
  {
    $query = "SELECT id, name FROM basic_repairs ORDER BY name";

      return parent::voerQueryUit($query);
    }


  }