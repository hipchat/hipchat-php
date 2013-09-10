#!/usr/bin/php
<?php

require 'src/HipChat/HipChat.php';

if (!isset($argv[1])) {
  echo "Usage: $argv[0] <token> [target]\n";
  die;
}

$token = $argv[1];
$target = isset($argv[2]) ? $argv[2] : 'https://api.hipchat.com';
$hc = new HipChat\HipChat($token, $target);

echo "Testing HipChat API.\nTarget: $target\nToken: $token\n\n";

// get rooms
echo "Rooms:\n";
try {
  $rooms = $hc->get_rooms();
  foreach ($rooms as $room) {
    echo "Room $room->room_id\n";
    echo " - Name: $room->name\n";
    $room_data = $hc->get_room($room->room_id);
    echo " - Participants: ".count($room_data->participants)."\n";
  }
} catch (HipChat\HipChat_Exception $e) {
  echo "Oops! Error: ".$e->getMessage();
}

// get users
echo "\nUsers:\n";
try {
  $users = $hc->get_users();
  foreach ($users as $user) {
    echo "User $user->user_id\n";
    echo " - Name: $user->name\n";
    echo " - Email: $user->email\n";
    $user_data = $hc->get_user($user->user_id);
    echo " - Status: ".$user_data->status."\n";
  }
} catch (HipChat\HipChat_Exception $e) {
  echo "Oops! Error: ".$e->getMessage();
}
