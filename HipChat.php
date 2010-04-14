<?php

/**
 * Library for interacting with the HipChat REST API.
 *
 * @see http://api.hipchat.com/docs/api
 */
class HipChat {

  const DEFAULT_TARGET = 'http://api.hipchat.com';

  /**
   * Response formats
   */
  const FORMAT_JSON = 'json';
  const FORMAT_XML = 'xml';

  /**
   * HTTP response codes from API
   *
   * @see http://api.hipchat.com/docs/api/response_codes
   */
  const STATUS_BAD_RESPONSE = -1; // Not an HTTP response code
  const STATUS_OK = 200;
  const STATUS_BAD_REQUEST = 400;
  const STATUS_UNAUTHORIZED = 401;
  const STATUS_FORBIDDEN = 403;
  const STATUS_NOT_FOUND = 404;
  const STATUS_NOT_ACCEPTABLE = 406;
  const STATUS_INTERNAL_SERVER_ERROR = 500;
  const STATUS_SERVICE_UNAVAILABLE = 503;

  /**
   * API versions
   */
  const VERSION_1 = 'v1';

  private $api_target;
  private $api_token;

  /**
   * Creates a new API interaction object.
   *
   * @param $api_token    Your API token.
   * @param $api_target   API protocol and host. Change if you're using an API
   *                      proxy such as apigee.com.
   * @param $api-version  Version of API to use.
   */
  function __construct($api_token, $api_target = self::DEFAULT_TARGET,
                       $api_version = self::VERSION_1) {
    $this->api_target = $api_target;
    $this->api_token = $api_token;
    $this->api_version = $api_version;
  }


  /////////////////////////////////////////////////////////////////////////////
  // Public functions
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Get information about a room
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/show
   */
  public function get_room($room_id, $format = self::FORMAT_JSON) {
    $args = array('room_id' => $room_id);
    return $this->make_request("rooms/show", $args, $format);
  }

  /**
   * Get list of rooms
   *
   * @see http://api.hipchat.com/docs/api/method/rooms
   */
  public function get_rooms($format = self::FORMAT_JSON) {
    return $this->make_request('rooms/list', array(), $format);
  }

  /**
   * Get information about a user
   *
   * @see http://api.hipchat.com/docs/api/method/users/show
   */
  public function get_user($user_id, $format = self::FORMAT_JSON) {
    $args = array('user_id' => $user_id);
    return $this->make_request("users/show", $args, $format);
  }

  /**
   * Get list of users
   *
   * @see http://api.hipchat.com/docs/api/method/users
   */
  public function get_users($format = self::FORMAT_JSON) {
    return $this->make_request('users/list', array(), $format);
  }

  /**
   * Send a message to a room
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/message
   */
  public function message_room($room_id, $from, $message,
                               $format = self::FORMAT_JSON) {
    $args = array(
      'room_id' => $room_id,
      'from' => $from,
      'message' => utf8_encode($message)
    );
    return $this->make_request("rooms/message", $args, $format);
  }


  /////////////////////////////////////////////////////////////////////////////
  // Private functions
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Make an API request using curl
   *
   * @param $method             Which API method to hit. e.g.: 'rooms/show'
   * @param $format             Desired response format.
   * @param $args               Data to send via POST.
   * @param $expected_response  Expected HTTP response code (usually 200)
   */
  private function make_request($method, $args = array(),
                                $format = self::FORMAT_JSON,
                                $expected_response = self::STATUS_OK) {
    $url = "$this->api_target/$this->api_version/$method";
    $headers = array("Authorization: HipChat $this->api_token");
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if (!empty($args)) {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
    }
    $response = curl_exec($ch);

    // make sure we got a real response
    if (strlen($response) == 0) {
      $errno = curl_errno($ch);
      $error = curl_error($ch);
      throw new HipChat_Exception(self::STATUS_BAD_RESPONSE,
        "CURL error: $errno - $error", $url);
    }

    // make sure we got a 200
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($code != $expected_response) {
      throw new HipChat_Exception(self::STATUS_BAD_RESPONSE,
        "HTTP status code: $code, response=$response", $url);
    }

    curl_close($ch);

    // make sure response is valid
    if ($format == self::FORMAT_JSON) {
      $response = json_decode($response);
      if (!$response) {
        throw new HipChat_Exception(self::STATUS_BAD_RESPONSE,
          "Invalid JSON received: $response", $url);
      }
    } else {
      try {
        $response = @new SimpleXMLElement($response);
      } catch (Exception $e) {
        throw new HipChat_Exception(self::STATUS_BAD_RESPONSE,
          "Invalid XML received: $response", $url);
      }
    }

    return $response;
  }

}


class HipChat_Exception extends Exception {
	public function __construct($code, $info, $url) {
    $message = "HipChat API error: code=$code, info=$info, url=$url";
		parent::__construct($message, (int)$code);
	}
}
