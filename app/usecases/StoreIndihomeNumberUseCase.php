<?php
declare(strict_types=1);

class StoreIndihomeNumberUseCase
{
    private $logger;

    public function __construct($logger = null)
    {        
        $this->logger = $logger;
    }

    public function execute(string $subscriptionNumber): array
    {
        try {
            $validator = new IndihomeValidator();
            $validation = $validator->validate(['subscription_number' => $subscriptionNumber]);

            if (count($validation)) {
                $errors = [];
                foreach ($validation as $message) {
                    $errors[] = $message->getMessage();
                }
                
                if ($this->logger) {
                    $this->logger->warning('Validation failed for subscription number', [
                        'subscription_number' => $subscriptionNumber,
                        'errors' => $errors
                    ]);
                }

                return [
                    'success' => false,
                    'message' => implode(', ', $errors),
                    'data' => null
                ];
            }

            $existingNumber = IndihomeNumbersModel::findFirst([
                'conditions' => 'subscription_number = ?0 AND deleted_at IS NULL',
                'bind' => [$subscriptionNumber]
            ]);

            if ($existingNumber) {
                if ($this->logger) {
                    $this->logger->warning('Duplicate subscription number attempted', [
                        'subscription_number' => $subscriptionNumber
                    ]);
                }

                return [
                    'success' => false,
                    'message' => 'Subscription number already exists',
                    'data' => null
                ];
            }

            $indihomeNumber = new IndihomeNumbersModel();
            $indihomeNumber->subscription_number = $subscriptionNumber;

            if ($indihomeNumber->save()) {
                if ($this->logger) {
                    $this->logger->info('Subscription number stored successfully', [
                        'id' => $indihomeNumber->id,
                        'subscription_number' => $subscriptionNumber
                    ]);
                }

                return [
                    'success' => true,
                    'message' => 'Subscription number stored successfully',
                    'data' => [
                        'id' => $indihomeNumber->id,
                        'subscription_number' => $indihomeNumber->subscription_number,
                        'created_at' => $indihomeNumber->created_at
                    ]
                ];
            } else {
                $errors = [];
                foreach ($indihomeNumber->getMessages() as $message) {
                    $errors[] = $message->getMessage();
                }

                if ($this->logger) {
                    $this->logger->error('Failed to save subscription number', [
                        'subscription_number' => $subscriptionNumber,
                        'errors' => $errors
                    ]);
                }

                return [
                    'success' => false,
                    'message' => 'Failed to store subscription number: ' . implode(', ', $errors),
                    'data' => null
                ];
            }

        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Exception in StoreIndihomeNumberUseCase', [
                    'subscription_number' => $subscriptionNumber,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            return [
                'success' => false,
                'message' => 'An error occurred while processing your request',
                'data' => null
            ];
        }
    }
}