<?php
declare(strict_types=1);

class GetIndihomeNumberUseCase {
    private $logger;
    private $di;

    public function __construct($logger = null, $di = null)
    {
        $this->logger = $logger;
    }

    public function execute(): array
    {
        try {
            $numbers = IndihomeNumbersModel::find([
                'conditions' => 'deleted_at IS NULL',
                'order' => 'created_at DESC'
            ]);

            if (count($numbers) === 0) {
                if ($this->logger) {
                    $this->logger->info('No IndiHome numbers found');
                }

                return [
                    'success' => true,
                    'message' => 'No subscription numbers found',
                    'data' => []
                ];
            }

            return [
                'success' => true,
                'message' => 'Subscription numbers retrieved successfully',
                'data' => $numbers->toArray()
            ];
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error retrieving subscription numbers', [
                    'exception' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'code' => $e->getCode(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            return [
                'success' => false,
                'message' => 'An error occurred while retrieving subscription numbers',
                'data' => null
            ];
        }
    }
}