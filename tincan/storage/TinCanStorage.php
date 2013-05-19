<?php

/**
 * @file
 * @author  Bryan Hazelbaker <bryan.hazelbaker@gmail.com>
 * @version 1.0
 *
 * @section LICENSE
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details at
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @section DESCRIPTION
 *
 * Simple PHP interface to the TinCan Storage REST API.
 *
 * Ending ?> for PHP is omitted to avoid accidental header errors due to
 * trailing whitespace.
 *
 * @see http://apps.tincan.me/
 * @todo Allow non arrays to pass through as raw JSON in most functions.
 */

/**
 * @class TinCanStorage
 * @brief API to work with TinCan storage REST API.
 *
 * @see http://apps.tincan.me/
 */
class TinCanStorage {
  private $config = array(
    /* Name of this application. */
    'name' => NULL,
    /* User account identification. */
    'id' => NULL,
    /* User account password. */
    'key' => NULL,
    /* Use HTTPS connection. */
    'https' => NULL,
  );

  /* Base url constructed from credentials. */
  private $url = NULL;

  /* Will record all tagged requests to messages array. */
  public $debug = array(
    'request' => FALSE,
    'response' => FALSE,
    'print' => FALSE,
    'messages' => array(),
  );

  /**
   * Class constructor.
   *
   * Record credentials and construct url based on them.
   *
   * @param array $config
   *   - name: (string) The application name.
   *   - id: (string) The user identification.
   *   - key: (string) The user password.
   *   - https: (bool) (optional) Use a secured connection.
   */
  public function __construct($config) {
    $this->config = $config;
    $this->debug = array_merge($this->debug, $config['debug']);

    /* Construct base url. */
    $this->url = self::createBaseUrl($this->config);
  }

  /**
   * Class destructor.
   *
   * Print out any error messages if requested by configuration.
   */
  public function __destruct() {
    if (isset($this->debug['print']) && $this->debug['print'] == TRUE) {
      // Print out any messages recorded.
      if (!empty($this->debug['messages'])) {
        print_r($this->debug['messages']);
      }
    }
  }

  /**
   * Recommended class constructor.
   *
   * Use this so we can control how the class is constructed in the future.
   * The most common reasons for a NULL returned are bad username, key, or
   * application name. Turn on debuging for more information.
   *
   * @param array $config
   *   See TinCanStorage::__construct()
   *
   * @return object|bool
   *   A TinCanStorage object on success or NULL on failure.
   *
   * @see TinCanStorage::__construct()
   * @todo Search passed config for required keys.
   */
  public static function connect($config) {
    $TinCanStorage = new TinCanStorage($config);
    if (!$TinCanStorage->authorized()) {
      $TinCanStorage = NULL;
    }

    return $TinCanStorage;
  }

  /**
   * Construct the base url based on credentials.
   *
   * @param array $credentials
   *   An associative array:
   *   - name: (string) The application name.
   *   - id: (string) The user identification.
   *   - key: (string) The user password.
   *   - https: (bool) (optional) Use a secured connection.
   *
   * @return string|bool
   *   The base url string or NULL on failure.
   */
  public static function createBaseUrl($config) {
    $url = NULL;

    /* Construct base url. */
    $url = (isset($config['https']) && ($config['https']) ? 'https' : 'http') . '://';
    $url .= "{$config['id']}:{$config['key']}@apps.tincan.me/{$config['name']}/";

    return $url;
  }

  /**
   * Insert some data.
   *
   * @param array $payload
   *   An associative array that will be transforemed into a JSON object.
   *
   * @return bool
   *   TRUE on success. Throws exception on failure.
   */
  public function insert($payload) {
    if (!is_array($payload) || (!$payload = json_encode($payload))) {
      throw new Exception('Invalid payload for insertion.');
    }

    if ($report = $this->request('insert', $payload)) {
      $report = json_decode($report, TRUE);
    }
    if (!isset($report['success']) || $report['success'] != TRUE) {
      $message = (isset($report['error'])) ? $report['error'] : 'No error message provided by server';
      throw new Exception('Data insertion failed: ' . $message);
    }

    return TRUE;
  }

  /**
   * Find some data.
   *
   * @param array $payload
   *   (optional) An associative array that will be transforemed into a JSON
   *   object. If no parameter is specified then the entire database will be
   *   returned.
   *
   * @return mixed
   *   JSON decoded object on success. Throws exception on failure.
   */
  public function select($payload = '[]') {
    if ($payload != '[]' && (!is_array($payload) || (!$payload = json_encode($payload)))) {
      throw new Exception('Invalid payload for selection.');
    }

    if ($report = $this->request('find', $payload)) {
      $report = json_decode($report, TRUE);
    }
    if (!isset($report['success']) || $report['success'] != TRUE) {
      $message = (isset($report['error'])) ? $report['error'] : 'No error message provided by server';
      throw new Exception('Data selection failed: ' . $message);
    }
    if (!isset($report['data'])) {
      throw new Exception('Selection succeeded but no data was returned');
    }

    return $report['data'];
  }

  /**
   * Remove some data.
   *
   * Removing data that does not exist will not generate an error.
   *
   * @param array $payload
   *   An associative array that will be transforemed into a JSON object.
   *
   * @return bool
   *   TRUE on success. Throws exception on failure.
   */
  public function delete($payload) {
    if (!is_array($payload) || (!$payload = json_encode($payload))) {
      throw new Exception('Invalid payload for deletion.');
    }

    if ($report = $this->request('remove', $payload)) {
      $report = json_decode($report, TRUE);
    }
    if (!isset($report['success']) || $report['success'] != TRUE) {
      $message = (isset($report['error'])) ? $report['error'] : 'No error message provided by server';
      throw new Exception('Data deletion failed: ' . $message);
    }

    return TRUE;
  }

  /**
   * Test if current credentials are authorized. This is not a static function
   * So we don't encourage repeated polling from an outside source.
   *
   * @return bool
   *   TRUE on success. FALSE on not authorized..
   *
   * @todo make this static instead? Then we don't have to actually instantiate
   * if authorization fails.
   */
  protected function authorized() {
    $authorized = FALSE;

    if ($report = $this->request('authorized', NULL)) {
      $report = json_decode($report, TRUE);
    }
    if (isset($report['success']) && $report['success'] == TRUE) {
      $authorized = TRUE;
    }

    return $authorized;
  }

  /**
   * Use curl to communicate with tincan storage REST API.
   *
   * @param string $type
   *   The type of request to send. Acceptable values are:
   *     'insert', 'find', 'update', 'remove'
   * @param string $payload
   *   A JSON encoded string that will be sent. No JSON validation will be done
   *   on purpose to avoid PHP centric issues.
   *
   * @return string|bool
   *   A JSON encode response from the server or FALSE on communication failure.
   *
   * @see http://apps.tincan.me/
   * @todo Change the hard coded request types to something a little more nice.
   */
  protected function request($type, $payload) {
    // Don't bother with an invalid type.
    if ($type != 'insert' && $type != 'find' && $type != 'update' && $type != 'remove'
        && $type != 'authorized') {
      throw new Exception('Invalid request type.');
    }

    $ch = curl_init();
    $url = $this->url . $type;
    $options = array(
      CURLOPT_URL => $url,
      CURLOPT_POSTFIELDS => $payload,
      CURLOPT_POST => TRUE, 
      CURLOPT_HEADER => FALSE,
      CURLOPT_RETURNTRANSFER => TRUE,
    ); 

    if ($this->debug['request'] == TRUE) {
      $this->debug['messages'][] = $payload;
    }
    curl_setopt_array($ch, $options); 
    $response = curl_exec($ch);
    if ($this->debug['response'] == TRUE) {
      $this->debug['messages'][] = $response;
    }

    return $response;
  }


}
