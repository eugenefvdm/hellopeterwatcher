<?php

use Eugenevdm\BulkSMSClient;

beforeEach(function () {
    $this->client = new BulkSMSClient('test_user', 'test_pass');
});

function invokePrivateMethod(object $object, string $methodName, ...$args)
{
    $m = new ReflectionMethod($object, $methodName);
    $m->setAccessible(true);
    return $m->invoke($object, ...$args);
}

it('converts star emoji sequence to SMS-safe "N star " text', function () {
    $message = "You received a ⭐️⭐️⭐️⭐️⭐️ review by David at Hellopeter. Please reply ASAP.";
    $safe = $this->client->toSmsSafeMessage($message);

    expect($safe)->toBe("You received a 5 star review by David at Hellopeter. Please reply ASAP.");
});

it('converts single star emoji to 1 star', function () {
    $message = "You received a ⭐️ review by Jane at Hellopeter.";
    $safe = $this->client->toSmsSafeMessage($message);

    expect($safe)->toContain("1 star");
});

it('leaves messages without star emoji unchanged', function () {
    $message = "You received a review by Bob at Hellopeter. Please reply ASAP.";
    $safe = $this->client->toSmsSafeMessage($message);

    expect($safe)->toBe($message);
});

it('shortens standard review message for Unicode SMS to at most 70 chars and keeps stars and key text', function () {
    $message = "You received a ⭐️⭐️⭐️⭐️⭐️ review by David at Hellopeter. Please reply ASAP.";
    $short = invokePrivateMethod($this->client, 'shortenForUnicodeSms', $message, 70);

    expect(mb_strlen($short, 'UTF-8'))->toBeLessThanOrEqual(70);
    expect($short)->toContain('⭐️');
    expect($short)->toContain('Hellopeter');
    expect($short)->toContain('Reply ASAP');
});

it('truncates long reviewer name when shortening for Unicode SMS', function () {
    $longName = str_repeat('A', 80);
    $message = "You received a ⭐️⭐️⭐️⭐️⭐️ review by {$longName} at Hellopeter. Please reply ASAP.";
    $short = invokePrivateMethod($this->client, 'shortenForUnicodeSms', $message, 70);

    expect(mb_strlen($short, 'UTF-8'))->toBeLessThanOrEqual(70);
    expect($short)->toContain('...');
    expect($short)->toContain('Hellopeter');
});

it('generically truncates non-matching message to 70 chars for Unicode SMS', function () {
    $long = str_repeat('x', 100);
    $short = invokePrivateMethod($this->client, 'shortenForUnicodeSms', $long, 70);

    expect(mb_strlen($short, 'UTF-8'))->toBe(70);
    expect($short)->toEndWith('...');
});

it('encodes UTF-8 to UCS-2BE hex for 16-bit API', function () {
    $hex = invokePrivateMethod($this->client, 'utf8ToUcs2Hex', 'Hi');
    expect($hex)->toBe('00480069');
});

it('sends a real SMS when ENABLE_SMS_TEST is true and credentials are set', function () {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
    $dotenv->load();

    if (($_ENV['ENABLE_SMS_TEST'] ?? '') !== 'true') {
        $this->markTestSkipped('Set ENABLE_SMS_TEST=true and BulkSMS credentials in .env to run this test.');
    }

    $username = $_ENV['BULKSMS_USERNAME'] ?? '';
    $password = $_ENV['BULKSMS_PASSWORD'] ?? '';
    $recipients = isset($_ENV['BULKSMS_RECIPIENTS']) ? array_map('trim', explode(',', $_ENV['BULKSMS_RECIPIENTS'])) : [];

    if ($username === '' || $password === '' || empty($recipients) || str_contains($username, 'your_') || str_contains($password, 'your_')) {
        $this->markTestSkipped('Set valid BULKSMS_USERNAME, BULKSMS_PASSWORD and BULKSMS_RECIPIENTS in .env to run this test.');
    }

    $encoding = $_ENV['BULKSMS_ENCODING'] ?? '7bit';
    $message = "You received a ⭐️⭐️⭐️⭐️⭐️ review by TestUser at Hellopeter. Please reply ASAP. (SMS encoding test)";
    $client = new BulkSMSClient($username, $password, $encoding);
    $results = $client->sendSMS($message, $recipients);

    foreach ($results as $recipient => $result) {
        expect($result)->toHaveKey('success');
        expect($result['success'])->toBe(1, "SMS to {$recipient} failed: " . ($result['details'] ?? $result['api_message'] ?? 'unknown'));
    }
});
