<?php

/**
 * Some simple unit tests to help test this library. These tests are not
 * intended to be used to verify the API response data.
 *
 * TODO: Test valid requests. What API token to use?
 */

require_once dirname(__FILE__).'/../src/HipChat/HipChat.php';
require_once 'PHPUnit/Framework.php';

class HipChatPHPTest extends PHPUnit_Framework_TestCase {

  private $target = 'http://api.hipchat.com';

  public function testBadToken() {
    $hc = new HipChat('hipchat-php-test-bad-token', $this->target);
    $this->setExpectedException('HipChat_Exception');
    $hc->get_rooms();
  }

  public function testBadTargetHost() {
    $bad_target = 'http://does-not-exist.hipchat.com';
    $hc = new HipChat('hipchat-php-test-token', $bad_target);
    $this->setExpectedException('HipChat_Exception');
    $hc->get_rooms();
  }

  public function testBadApiMethod() {
    $hc = new HipChat('hipchat-php-test-token', $this->target);
    $this->setExpectedException('HipChat_Exception');
    $hc->make_request('bad/method');
  }

  public function testBadColor()
  {
    $bad_color = 'fancy pink';
    $hc = new HipChat('hipchat-php-test-token', $this->target);
    $this->setExpectedException('InvalidArgumentException');
    $hc->message_room(1337, 'Hipchat', "Hi everybody.", false, $bad_color);
  }

}
