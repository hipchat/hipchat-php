<?php

namespace HipChat;

/**
 * Library for interacting with the HipChat REST API.
 *
 * @see http://api.hipchat.com/docs/api
 */
class HipChat {

  const DEFAULT_TARGET = 'https://api.hipchat.com';

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

  const COLOR_YELLOW = 'yellow';
  const COLOR_RED = 'red';
  const COLOR_GREEN = 'green';
  const COLOR_PURPLE = 'purple';
  const COLOR_RANDOM = 'random';

  /**
   * API versions
   */
  const VERSION_1 = 'v1';

  private $api_target;
  private $auth_token;
  private $verify_ssl = true;

  /**
   * Creates a new API interaction object.
   *
   * @param $auth_token   Your API token.
   * @param $api_target   API protocol and host. Change if you're using an API
   *                      proxy such as apigee.com.
   * @param $api-version  Version of API to use.
   */
  function __construct($auth_token, $api_target = self::DEFAULT_TARGET,
                       $api_version = self::VERSION_1) {
    $this->api_target = $api_target;
    $this->auth_token = $auth_token;
    $this->api_version = $api_version;
  }


  /////////////////////////////////////////////////////////////////////////////
  // Room functions
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Get information about a room
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/show
   */
  public function get_room($room_id) {
    $response = $this->make_request("rooms/show", array(
      'room_id' => $room_id
    ));
    return $response->room;
  }

  /**
   * Get list of rooms
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/list
   */
  public function get_rooms() {
    $response = $this->make_request('rooms/list');
    return $response->rooms;
  }

  /**
   * Send a message to a room
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/message
   */
  public function message_room($room_id, $from, $message, $notify = false, $color = self::COLOR_YELLOW) {

    if (!in_array($color, array(self::COLOR_YELLOW, self::COLOR_PURPLE, self::COLOR_GREEN, self::COLOR_RED, self::COLOR_RANDOM))) 
    {
      throw new \InvalidArgumentException(sprintf('Unkown color "%s"', $color));
    }

    $args = array(
      'room_id' => $room_id,
      'from' => $from,
      'message' => utf8_encode($message),
      'notify' => (int)$notify,
      'color' => $color
    );
    $response = $this->make_request("rooms/message", $args, 'POST');
    return ($response->status == 'sent');
  }

  /**
   * Get chat history for a room
   *
   * @see https://www.hipchat.com/docs/api/method/rooms/history
   */
   public function get_rooms_history($room_id, $date = 'recent') {
     $response = $this->make_request('rooms/history', array(
      'room_id' => $room_id,
      'date' => $date
     ));
     return $response->messages;
   }

  /////////////////////////////////////////////////////////////////////////////
  // User functions
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Get information about a user
   *
   * @see http://api.hipchat.com/docs/api/method/users/show
   */
  public function get_user($user_id) {
    $response = $this->make_request("users/show", array(
      'user_id' => $user_id
    ));
    return $response->user;
  }

  /**
   * Get list of users
   *
   * @see http://api.hipchat.com/docs/api/method/users/list
   */
  public function get_users() {
    $response = $this->make_request('users/list');
    return $response->users;
  }


  /////////////////////////////////////////////////////////////////////////////
  // Helper functions
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Performs a curl request
   *
   * @param $url        URL to hit.
   * @param $post_data  Data to send via POST. Leave null for GET request.
   */
  public function curl_request($url, $post_data = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
    if (is_array($post_data)) {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
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
    if ($code != self::STATUS_OK) {
      throw new HipChat_Exception($code,
        "HTTP status code: $code, response=$response", $url);
    }

    curl_close($ch);

    return $response;
  }

  /**
   * Make an API request using curl
   *
   * @param $api_method   Which API method to hit, like 'rooms/show'.
   * @param $args         Data to send.
   * @param $http_method  HTTP method (GET or POST).
   */
  public function make_request($api_method, $args = array(),
                               $http_method = 'GET') {
    $args['format'] = 'json';
    $args['auth_token'] = $this->auth_token;
    $url = "$this->api_target/$this->api_version/$api_method";
    $post_data = null;

    // add args to url for GET
    if ($http_method == 'GET') {
      $url .= '?'.http_build_query($args);
    } else {
      $post_data = $args;
    }

    $response = $this->curl_request($url, $post_data);

    // make sure response is valid json
    $response = json_decode($response);
    if (!$response) {
      throw new HipChat_Exception(self::STATUS_BAD_RESPONSE,
        "Invalid JSON received: $response", $url);
    }

    return $response;
  }
    
  /**
   * Enable/disable verify_ssl.
   * This is useful when curl spits back ssl verification errors, most likely due to 
   * outdated SSL CA bundle file on server.  If you are able to, update that CA bundle.
   * If not, call this method with false for $bool param before interacting with the API.
   * 
   * @param bool $bool
   * @return bool
   * @link http://davidwalsh.name/php-ssl-curl-error
   */
  public function set_verify_ssl($bool = true)
  {
    $this->verify_ssl = (bool) $bool;
    return $this->verify_ssl;
  }  

}


class HipChat_Exception extends \Exception {
	public function __construct($code, $info, $url) {
    $message = "HipChat API error: code=$code, info=$info, url=$url";
		parent::__construct($message, (int)$code);
	}
}
