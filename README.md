hipchat-php
===========

A PHP library for interacting with the [HipChat][hc] REST API.

Testing
-------
You can test this library (and your API key) using the test.php script as follows. It should print a list of your rooms and users.

    ./test.php <your api token>

Usage
-----
    require 'HipChat.php';
    $token = '<your api token>';
    $hc = new HipChat($token);
    $rooms = $hc->get_rooms();
    foreach ($rooms->rooms as $room) {
      echo " - $room->id = $room->name\n";
    }

[hc]: http://www.hipchat.com
