<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Eugenevdm\HelloPeterClient;
use Eugenevdm\BulkSMSClient;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize the HelloPeter client
$client = new HelloPeterClient($_ENV['HELLO_PETER_API_KEY']);

try {
    // Example: Get reviews with some parameters
    $unrepliedReviewCount = $client->getUnrepliedReviews();

    // Output the review count
    $reviewCount = count($unrepliedReviewCount['data'] ?? []);
    echo "Unreplied review count: " . $reviewCount . "\n";
    foreach ($unrepliedReviewCount['data'] ?? [] as $review) {
        echo "----------------------------------------\n";
        echo "Review ID: " . ($review['id'] ?? 'N/A') . "\n";
        echo "Rating: " . ($review['rating'] ?? 'N/A') . "\n";
        echo "Content: " . ($review['content'] ?? 'N/A') . "\n";
        echo "----------------------------------------\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 

if (isset($reviewCount) && $reviewCount > 0) {
    echo "Now sending SMS\n";
    $sender = new BulkSMSClient($_ENV['BULKSMS_USERNAME'], $_ENV['BULKSMS_PASSWORD']);
    $message = ($reviewCount === 1 ? "1 new unreplied review" : "{$reviewCount} new unreplied reviews");
    $message .= " at Hello Peter. Please reply to them ASAP.";
    $recipients = explode(',', $_ENV['BULKSMS_RECIPIENTS']);
    $sender->sendSMS($message, $recipients);
}

