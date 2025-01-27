<?php

namespace Eugenevdm;

class StateManager
{
    private $stateFile;
    
    public function __construct(string $stateFile = 'storage/notified_reviews.json')
    {
        $this->stateFile = $stateFile;
        $this->ensureStateFileExists();
    }
    
    private function ensureStateFileExists(): void
    {
        if (!file_exists(dirname($this->stateFile))) {
            mkdir(dirname($this->stateFile), 0755, true);
        }
        if (!file_exists($this->stateFile)) {
            file_put_contents($this->stateFile, json_encode(['notifications' => []]));
        }
    }
    
    public function getNotifiedReviews(): array
    {
        $content = file_get_contents($this->stateFile);
        $data = json_decode($content, true);
        // Return just the review IDs for backward compatibility
        return array_keys($data['notifications'] ?? []);
    }
    
    public function markReviewAsNotified(string $reviewId): void
    {
        $data = json_decode(file_get_contents($this->stateFile), true);
        if (!isset($data['notifications'][$reviewId])) {
            $data['notifications'][$reviewId] = [
                'notified_at' => date('Y-m-d H:i:s'),
                'timestamp' => time()
            ];
            file_put_contents($this->stateFile, json_encode($data, JSON_PRETTY_PRINT));
        }
    }
} 