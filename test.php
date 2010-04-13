#!/usr/bin/php
<?php

require 'HipChat.php';

if (!isset($argv[1])) {
  echo "Usage: $argv[0] <token>\n";
  die;
}

$token = $argv[1];
$hc = new HipChat($token, 'http://api.hipchat.com');

echo "Testing HipChat API with token=$token\n\n";

// get rooms
echo "Hitting /rooms.json\n";
try {
  $rooms = $hc->get_rooms();
  foreach ($rooms->rooms as $room) {
    echo " - $room->id = $room->name\n";
  }
} catch (HipChat_Exception $e) {
  echo "Oops! Error: ".$e->getMessage();
}

// get users
echo "\nHitting /users.json\n";
try {
  $users = $hc->get_users();
  foreach ($users->users as $user) {
    echo " - $user->id = $user->name\n";
  }
} catch (HipChat_Exception $e) {
  echo "Oops! Error: ".$e->getMessage();
}
