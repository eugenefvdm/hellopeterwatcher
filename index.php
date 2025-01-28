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

    $newReviewCount = count($newUnrepliedReviews);

    if ($newReviewCount > 0) {
        $message = ($newReviewCount === 1
            ? "1 new unreplied review"
            : "{$newReviewCount} new unreplied reviews");
        $message .= " at Hello Peter. Please reply ASAP.";

        // Send SMS
        if ($_ENV['ENABLE_BULKSMS'] === 'true') {
            $sender = new BulkSMSClient($_ENV['BULKSMS_USERNAME'], $_ENV['BULKSMS_PASSWORD']);
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

        // Mark reviews as notified
        foreach ($newUnrepliedReviews as $review) {
            $stateManager->markReviewAsNotified($review['permalink']);
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
