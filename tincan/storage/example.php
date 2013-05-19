<?php

/**
 * @file
 * @author  Bryan Hazelbaker <bryan.hazelbaker@gmail.com>
 * @version 1.0
 *
 * @section DESCRIPTION
 *
 * Example script demonstrating the use of the TinCanStorage class.
 *
 * Ending ?> for PHP is omitted to avoid accidental header errors due to
 * trailing whitespace.
 *
 * @see TinCanStorage.php
 */


require_once('TinCanStorage.php');

// Setup credentials.
$config = array(
  // Name of the application.
  'name' => getenv('tincanappname'),
  // User name.
  'id' => getenv('tincanappid'),
  // User password.
  'key' => getenv('tincanappkey'),
  // Use secured connection or not.
  'https' => FALSE,
  'debug' => array(
    // Log all curl requests to the server.
    'request' => TRUE,
    // Log all curl responses to the server.
    'response' => TRUE,
    // Print out all messages logged when class is deconstructed.
    'print' => FALSE,
  ),
);

// Instantiate the class.
if ($Storage = TinCanStorage::connect($config)) {
  $data = array(
    'table' => 'testTable',
    'id' => 1,
    'name' => 'TinCan',
  );
  // Inert our data into the remote database.
  $Storage->insert($data);
  // Retrieve previously inserted data and display.
  print_r($Storage->select(array('table' => 'testTable')));
  // Remove previously inersted data from remote database.
  $Storage->delete(array('table' => 'testTable'));
}
