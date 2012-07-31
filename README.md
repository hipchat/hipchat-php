# hipchat-php

A PHP library for interacting with the [HipChat](http://hipchat.com) REST API.

## Composer Installation

HipChat-PHP can be installed with Composer (http://getcomposer.org/).  Add the following to your
composer.json file.  Composer will handle the autoloading.

```json
{
    "require": {
        "hipchat/hipchat-php": ">=1.0.0"
    }
}
```

## Usage

```php
$token = '<your api token>';
$hc = new HipChat\HipChat($token);

// list rooms
foreach ($hc->get_rooms() as $room) {
  echo " - $room->room_id = $room->name\n";
}

// send a message to the 'Development' room from 'API'
$hc->message_room('Development', 'API', 'This is just a test message!');
```

## Testing

You can test this library (and your API key) using the example.php script as follows. It should print a list of your rooms and users.

    ./example.php <your api token>

To test the library itself, run the PHPUnit tests:

    phpunit tests/
