<?php
declare(strict_types=1);

use Phalcon\Http\Response;

class IndihomeController extends ControllerBase
{
    public function indexAction()
    {
        $this->view->render('indihome', 'index');
    }

    public function numberAction(): Response
    {
        try {
            // Get JSON input
            $input = $this->request->getJsonRawBody(true);
            
            if (!$input || !isset($input['subscription_number'])) {
                $this->logger->warning('Invalid input received in numberAction', [
                    'input' => $input
                ]);
                
                return $this->errorResponse('Invalid input. Subscription number is required.', 400);
            }

            $subscriptionNumber = trim($input['subscription_number']);
            
            // Use the use case
            $useCase = new StoreIndihomeNumberUseCase($this->logger);
            $result = $useCase->execute($subscriptionNumber);

            if ($result['success']) {
                return $this->jsonResponse(
                    $result['data'], 
                    201, 
                    'Created', 
                    $result['message']
                );
            } else {
                return $this->errorResponse($result['message'], 400);
            }

        } catch (\Exception $e) {
            $this->logger->error('Exception in IndihomeController::numberAction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('An unexpected error occurred', 500);
        }
    }
}