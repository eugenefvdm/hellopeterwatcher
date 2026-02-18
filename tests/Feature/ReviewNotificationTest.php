<?php

use Eugenevdm\HelloPeterClient;

// Toggle this to test against live API (requires valid API key in .env)
const USE_LIVE_DATA = true;

// Status filter for live testing: null fetches all reviews, 'unreplied,unreplied_comment' for real scenario
const LIVE_TEST_STATUS = null;

function getMockReviews(): array
{
    return [
        'data' => [
            [
                'permalink' => 'https://hellopeter.com/review/123',
                'rating' => 5,
                'user' => 'John Smith',
            ],
            [
                'permalink' => 'https://hellopeter.com/review/456',
                'rating' => 3,
                'user' => 'Jane Doe',
            ],
        ],
    ];
}

function getReviews(): array
{
    if (USE_LIVE_DATA) {
        $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->safeLoad();
        $apiKey = $_ENV['HELLO_PETER_API_KEY'] ?? '';
        if ($apiKey === '' || str_contains($apiKey, 'your_')) {
            return getMockReviews();
        }
        $client = new HelloPeterClient($apiKey);
        return $client->getReviews(LIVE_TEST_STATUS);
    }

    return getMockReviews();
}

function formatReviewMessage(array $review): string
{
    $stars = str_repeat('⭐️', $review['rating'] ?? 0);
    $reviewerName = $review['user'] ?? 'Unknown';
    return "You received a {$stars} review by {$reviewerName} at Hellopeter. Please reply ASAP.";
}

it('formats review message with correct stars and reviewer name', function () {
    $reviews = getReviews();

    expect($reviews)->toHaveKey('data');
    expect($reviews['data'])->toBeArray()->not->toBeEmpty();

    $review = $reviews['data'][0];
    $message = formatReviewMessage($review);

    echo "\n" . $message . "\n";

    // Verify message contains expected components
    expect($message)->toContain('You received a');
    expect($message)->toContain('review by');
    expect($message)->toContain('at Hellopeter. Please reply ASAP.');

    // For mock data, verify exact values
    if (!USE_LIVE_DATA) {
        expect($message)->toBe('You received a ⭐️⭐️⭐️⭐️⭐️ review by John Smith at Hellopeter. Please reply ASAP.');
    }
});

it('generates correct number of star emojis based on rating', function () {
    $testCases = [
        ['rating' => 1, 'expected_stars' => '⭐️'],
        ['rating' => 3, 'expected_stars' => '⭐️⭐️⭐️'],
        ['rating' => 5, 'expected_stars' => '⭐️⭐️⭐️⭐️⭐️'],
    ];

    foreach ($testCases as $case) {
        $review = [
            'rating' => $case['rating'],
            'user' => 'Test User',
            'permalink' => 'https://hellopeter.com/review/test',
        ];

        $message = formatReviewMessage($review);
        expect($message)->toContain($case['expected_stars']);
    }
});
