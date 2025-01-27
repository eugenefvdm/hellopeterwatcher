# BulkSMS Sender

A PHP class for sending SMS messages through the BulkSMS API.

## Installation

1. Clone this repository
2. Copy `.env.example` to `.env` and fill in your BulkSMS credentials:

```php
// Example usage:
$config = [
    'username' => getenv('BULKSMS_USERNAME'),
    'password' => getenv('BULKSMS_PASSWORD'),
];

$sender = new BulkSMS($config['username'], $config['password']);

// For single recipient
// $result = $sender->sendSMS("Your message here", "27823096710");

// For multiple recipients
// $recipients = ['27823096710', '27827883723'];
// $result = $sender->sendSMS("Your message here", $recipients);

// If you're using this in the existing context where $sms is provided:
if (isset($sms)) {
    $recipients = ['27823096710', '27827883723']; // You can modify this array as needed
    $sender->sendSMS($sms, $recipients);
}
```