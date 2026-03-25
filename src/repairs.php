<?php

require_once __DIR__ . '/database.php';

class Repairs extends Database
{
  //simple function to get the name and email from a customer id
  public function GetAllRepairTypes()
  {
    $query = "SELECT id, name FROM basic_repairs ORDER BY name";

    return parent::voerQueryUit($query);
  }

  public function addBasicRepair($name, $description, $duration_minutes)
  {
    $query = "INSERT INTO basic_repairs (name, description, minutes) VALUES (?, ?, ?)";
    $params = [$name, $description, $duration_minutes];
    $result = parent::voerQueryUit($query, $params);

    return $result;
  }

  public function GetAllTypeInfo()
  {
    $query = "SELECT id, name, description, minutes FROM basic_repairs ORDER BY id";

    return parent::voerQueryUit($query);
  }

  public function DeleteRepair($id)
  {
    $query = "DELETE FROM basic_repairs WHERE id = ?";
    $params = [$id];
    $result = parent::voerQueryUit($query, $params);

    return $result;
  }

  public function getBasicRepairById($id)
  {
    $query = "SELECT id, name, description, minutes FROM basic_repairs WHERE id = ? LIMIT 1";
    $params = [(int) $id];
    $result = parent::voerQueryUit($query, $params);

    return $result[0] ?? null;
  }

  public function updateBasicRepair($id, $name, $description, $minutes)
  {
    $query = "UPDATE basic_repairs SET name = ?, description = ?, minutes = ? WHERE id = ?";
    $params = [
      trim((string) $name),
      trim((string) $description),
      (int) $minutes,
      (int) $id,
    ];

    return parent::voerQueryUit($query, $params);
  }
}
