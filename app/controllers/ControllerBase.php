<?php
declare(strict_types=1);

use Phalcon\Mvc\Controller;
use Phalcon\Logger\Logger;
use Phalcon\Http\Response;

class ControllerBase extends Controller
{
    protected $logger;

    public function onConstruct()
    {
        $this->logger = $this->di->getShared('logger');
    }
     protected function jsonResponse(array $data, int $status, string $statusMessage, string $message): Response
    {
        return $this
            ->response
            ->setStatusCode($status, $statusMessage)
            ->setContentType('application/json', 'utf-8')
            ->setJsonContent([
                'error' => false,
                'message' => $message,
                'data' => $data
            ]);
    }

    protected function errorResponse(string $message = 'Something Went Wrong', int $status = 500): Response
    {
        return $this
            ->response
            ->setStatusCode($status)
            ->setContentType('application/json','utf-8')
            ->setJsonContent([
                'error' => true,
                'message' => $message,
                'data' => null
            ]);
    }
}
