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

  private $target = 'https://api.hipchat.com';

  public function testBadToken() {
    $hc = new HipChat\HipChat('hipchat-php-test-bad-token', $this->target);
    $this->setExpectedException('HipChat\HipChat_Exception');
    $hc->get_rooms();
  }

  public function testBadTargetHost() {
    $bad_target = 'http://does-not-exist.hipchat.com';
    $hc = new HipChat\HipChat('hipchat-php-test-token', $bad_target);
    $this->setExpectedException('HipChat\HipChat_Exception');
    $hc->get_rooms();
  }

  public function testBadApiMethod() {
    $hc = new HipChat\HipChat('hipchat-php-test-token', $this->target);
    $this->setExpectedException('HipChat\HipChat_Exception');
    $hc->make_request('bad/method');
  }



  /**
   * Tests for a mention at the first position in the message.
   *
   * In PHP, curl uses the syntax "@test.php" to send *the file*
   * test.php via curl.
   *
   * This test should actually just work (i.e. not throwing an exception)
   *
   * @link http://www.php.net/manual/en/function.curl-setopt.php see reference for CURLOPT_POSTFIELDS
   */
  public function testMentionAtFirstPosition ()
  {
    $hc = new HipChat\HipChat('hipchat-php-test-token', $this->target);
    $hc->message_room(123, '@sender', '@test test');
  }

}
