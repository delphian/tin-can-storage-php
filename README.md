PHP TinCan Storage API
======

This is a simple PHP class that will facilitate communication with the TinCan
Storage REST API.

See http://apps.tincan.me/ for more information and documentation.

See the example.php for an example of using the TinCanStorage class.

Setup the configuration array
------

<code><pre>
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
</pre></code>

Instantiate the class
------

`$Storage = TinCanStorage::connect($config);`

The connect method will return FALSE if the class could not be instantiated. This is normally due to bad username, password, or application name.

Create the data structure you want saved to the database.
------

`$data = array('table' => 'names', 'name' => 'crunch');`

Write the data to the database.
------

`$Storage->insert($data);`

Most methods will throw an exception on failure, otherwise will return TRUE on success.

Read data from the database.
------

`$structure = $Storage->select($data);`

The return value will be an object.

Delete the data from the database.
------

`$Storage->delete($data);`
