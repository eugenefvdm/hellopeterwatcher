<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Eugenevdm\HelloPeterClient;
use Eugenevdm\BulkSMSClient;
use Eugenevdm\StateManager;

// Initialize state manager
$stateManager = new StateManager();

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize the HelloPeter client
$client = new HelloPeterClient($_ENV['HELLO_PETER_API_KEY']);

try {
    $unrepliedReviews = $client->getUnrepliedReviews();
    // print_r($unrepliedReviews);
    // die("stop");
    $notifiedReviews = $stateManager->getNotifiedReviews();
    
    // Filter out reviews we've already notified about
    $newUnrepliedReviews = array_filter(
        $unrepliedReviews['data'] ?? [],
        fn($review) => !in_array($review['permalink'], $notifiedReviews)
    );
    
    $newReviewCount = count($newUnrepliedReviews);
    
    if ($newReviewCount > 0) {

        // die($newUnrepliedReviews);
        // Send SMS
        $sender = new BulkSMSClient($_ENV['BULKSMS_USERNAME'], $_ENV['BULKSMS_PASSWORD']);
        $message = ($newReviewCount === 1 ? "1 new unreplied review" : "{$newReviewCount} new unreplied reviews");
        $message .= " at Hello Peter. Please reply to them ASAP.";
        $recipients = explode(',', $_ENV['BULKSMS_RECIPIENTS']);
        $sender->sendSMS($message, $recipients);


        
        // Mark reviews as notified
        foreach ($newUnrepliedReviews as $review) {
            $stateManager->markReviewAsNotified($review['permalink']);
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

