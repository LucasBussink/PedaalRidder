<?php
include_once '../src/customer.php';

$cust = new Customer();

$customer = $cust->Get(1);
print_r($customer);
