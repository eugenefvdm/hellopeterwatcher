<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Eugenevdm\HelloPeterClient;
use Eugenevdm\BulkSMSClient;
use Eugenevdm\SlackClient;
use Eugenevdm\StateManager;
use Eugenevdm\TelegramClient;

// Initialize state manager
$stateManager = new StateManager();

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize the HelloPeter client
$client = new HelloPeterClient($_ENV['HELLO_PETER_API_KEY']);

try {
    $unrepliedReviews = $client->getUnrepliedReviews();

    $notifiedReviews = $stateManager->getNotifiedReviews();

    // Filter out reviews we've already notified about
    $newUnrepliedReviews = array_filter(
        $unrepliedReviews['data'] ?? [],
        fn($review) => !in_array($review['permalink'], $notifiedReviews)
    );

    if (count($newUnrepliedReviews) > 0) {
        foreach ($newUnrepliedReviews as $review) {
            $stars = str_repeat('⭐️', $review['rating'] ?? 0);
            $reviewerName = $review['user'] ?? 'Unknown';
            $message = "You received a {$stars} review by {$reviewerName} at Hellopeter. Please reply ASAP.";

            // Send SMS
            if ($_ENV['ENABLE_BULKSMS'] === 'true') {
                $sender = new BulkSMSClient($_ENV['BULKSMS_USERNAME'], $_ENV['BULKSMS_PASSWORD'], $_ENV['BULKSMS_ENCODING'] ?? '7bit');
                $recipients = explode(',', $_ENV['BULKSMS_RECIPIENTS']);
                $sender->sendSMS($message, $recipients);
            }

            // Send Slack notification
            if ($_ENV['ENABLE_SLACK'] === 'true') {
                $slack = new SlackClient($_ENV['SLACK_WEBHOOK_URL']);
                $slack->sendMessage($message);
            }

            // Send Telegram notification
            if ($_ENV['ENABLE_TELEGRAM'] === 'true') {
                $telegram = new TelegramClient($_ENV['TELEGRAM_BOT_TOKEN'], $_ENV['TELEGRAM_CHAT_ID']);
                $telegram->sendMessage($message);
            }

            // Mark review as notified
            $stateManager->markReviewAsNotified($review['permalink']);
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
