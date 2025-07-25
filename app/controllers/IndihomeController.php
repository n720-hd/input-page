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
            $recaptchaResponse = $input['recaptcha_response'] ?? '';

            // Validate reCAPTCHA if provided
            if (!empty($recaptchaResponse)) {
                if (!$this->validateRecaptcha($recaptchaResponse)) {
                    $this->logger->warning('reCAPTCHA validation failed', [
                        'subscription_number' => $subscriptionNumber,
                        'client_ip' => $this->getClientIp()
                    ]);
                    
                    return $this->errorResponse('reCAPTCHA verification failed. Please try again.', 400);
                }
            } else {
                $this->logger->info('Request without reCAPTCHA token', [
                    'subscription_number' => $subscriptionNumber,
                    'client_ip' => $this->getClientIp()
                ]);
            }
            
            // Use the use case
            $useCase = new StoreIndihomeNumberUseCase($this->logger);
            $result = $useCase->execute($subscriptionNumber);

            if ($result['success']) {
                 $this->response->setHeader('Accept-CH',
                    'Sec-CH-UA-Platform-Version, Sec-CH-UA-Model,
                    Sec-CH-UA-Arch');
                   
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

    public function getNumberAction(): Response{
        try {
            $useCase = new GetIndihomeNumberUseCase($this->logger, $this->di);

            $result = $useCase->execute();

            if ($result['success']) {
                 $this->response->setHeader('Accept-CH',
                    'Sec-CH-UA-Platform-Version, Sec-CH-UA-Model,
                    Sec-CH-UA-Arch');
                   
                return $this->jsonResponse(
                    $result['data'], 
                    200, 
                    'OK', 
                    $result['message']
                );
            } else {
                return $this->errorResponse($result['message'], 404);
            }
        } catch (\Throwable $th) {
            $this->logger->error('Exception in IndihomeController::getNumberAction: ' . $th->getMessage(), [
                'error' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString()
            ]);

            return $this->errorResponse('An unexpected error occurred: ' . $th->getMessage(), 500);
        }
    }
}