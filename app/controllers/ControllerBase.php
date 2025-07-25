<?php
declare(strict_types=1);

use Phalcon\Mvc\Controller;
use Phalcon\Logger\Logger;
use Phalcon\Http\Response;
use Phalcon\Logger\Adapter\Stream as StreamAdapter;
use Phalcon\Logger\Formatter\Line as LineFormatter;
use WhichBrowser\Parser;

class ControllerBase extends Controller
{   
    /** @var Logger */

    protected $logger;

    protected $logger_id;


    public function onConstruct()
    {
        $this->logger = $this->di->getShared('logger');
    }

    public function initialize()
    {
        $this->logger_id = uniqid();
        
        // Log request with simple device info
        if ($this->logger) {
            $deviceInfo = $this->getDeviceInfo();
            $this->logger->info($this->logger_id . " request: " . $this->request->getMethod() . " " . $this->request->getURI() . " | " . $deviceInfo);
        }
    }

    public function afterExecuteRoute()
    {
        $this->logResponse();
    }

    protected function logRequest()
    {
        if ($this->logger) {
            $userAgent = $this->request->getUserAgent();
            $deviceInfo = $this->parseUserAgent($userAgent);
            
            $requestData = [
                'logger_id' => $this->logger_id,
                'method' => $this->request->getMethod(),
                'uri' => $this->request->getURI(),
                'user_agent' => $userAgent,
                'device_info' => $deviceInfo,
                'client_ip' => $this->getClientIp(),
                'forwarded_ip' => $this->getForwardedIp(),
                'headers' => $this->request->getHeaders(),
                'post_data' => $this->request->getPost(),
                'get_data' => $this->request->getQuery(),
                'raw_body' => $this->request->getRawBody(),
                'timestamp' => date('Y-m-d H:i:s.u')
            ];

            $logMessage = 'REQUEST ' . json_encode($requestData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            $this->logger->info($logMessage);
        }
    }

    protected function logResponse()
    {
        if ($this->logger && $this->response) {
            $responseData = [
                'logger_id' => $this->logger_id,
                'status_code' => $this->response->getStatusCode(),
                'headers' => $this->response->getHeaders()->toArray(),
                'content' => $this->response->getContent(),
                'timestamp' => date('Y-m-d H:i:s.u')
            ];

            $logMessage = 'RESPONSE ' . json_encode($responseData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            $this->logger->info($logMessage);
        }
    }

    protected function getDeviceInfo()
    {
        $userAgent = $this->request->getUserAgent();
        
        if (empty($userAgent)) {
            return json_encode([
                'browser' => 'Unknown',
                'browser_version' => 'Unknown',
                'os' => 'Unknown',
                'os_version' => 'Unknown',
                'device_type' => 'Unknown',
                'device_model' => 'Unknown',
                'device_manufacturer' => 'Unknown'
            ]);
        }
        
        $result = new Parser($userAgent);
        
        $browser = $result->browser->name ?? 'Unknown';
        $browserVersion = $result->browser->version->value ?? 'Unknown';
        $os = $result->os->name ?? 'Unknown';
        $osVersion = $result->os->version->value ?? 'Unknown';
        $deviceType = $result->device->type ?? 'Unknown';
        
        if ($browser === 'Unknown' || empty($browser)) {
            if (preg_match('/PostmanRuntime\/([0-9\.]+)/i', $userAgent, $matches)) {
                $browser = 'Postman';
                $browserVersion = $matches[1];
                $deviceType = 'API Client';
                $os = 'Desktop Application';
            } elseif (preg_match('/curl\/([0-9\.]+)/i', $userAgent, $matches)) {
                $browser = 'cURL';
                $browserVersion = $matches[1];
                $deviceType = 'Command Line';
            } elseif (preg_match('/Insomnia\/([0-9\.]+)/i', $userAgent, $matches)) {
                $browser = 'Insomnia';
                $browserVersion = $matches[1];
                $deviceType = 'API Client';
            } elseif (preg_match('/HTTPie\/([0-9\.]+)/i', $userAgent, $matches)) {
                $browser = 'HTTPie';
                $browserVersion = $matches[1];
                $deviceType = 'Command Line';
            }
        }
        
        // Enhanced OS version detection for macOS
        if ($os === 'OS X' && $osVersion === 'Unknown') {
            // Try to extract macOS version from User-Agent
            if (preg_match('/Mac OS X ([0-9_]+)/i', $userAgent, $matches)) {
                $osVersion = str_replace('_', '.', $matches[1]);
                $os = 'macOS';
            }
        }
        
        // Get Client Hints for modern browsers (if available)
        $clientHints = $this->getClientHints();
        
        // Override with Client Hints if available
        if (!empty($clientHints['platform'])) {
            $os = $clientHints['platform'];
        }
        if (!empty($clientHints['platform_version'])) {
            $osVersion = $clientHints['platform_version'];
        }
        if (!empty($clientHints['model'])) {
            $deviceModel = $clientHints['model'];
        }
        
        $deviceInfo = [
            'browser' => $browser,
            'browser_version' => $browserVersion,  
            'os' => $os,
            'os_version' => $osVersion,
            'device_type' => $deviceType,
            'device_model' => $result->device->model ?? 'Unknown',
            'device_manufacturer' => $result->device->manufacturer ?? 'Unknown',
            'client_hints' => $clientHints
        ];
        
        return json_encode($deviceInfo);
    }

    protected function getClientHints()
    {
        $hints = [];
        
        // Get Client Hints headers if available (case-insensitive)
        $headers = $this->request->getHeaders();
        
        // Check for different header name variations
        foreach ($headers as $name => $value) {
            $lowerName = strtolower($name);
            
            if ($lowerName === 'sec-ch-ua-platform') {
                $hints['platform'] = trim($value, '"');
            } elseif ($lowerName === 'sec-ch-ua-platform-version') {
                $hints['platform_version'] = trim($value, '"');
            } elseif ($lowerName === 'sec-ch-ua-model') {
                $hints['model'] = trim($value, '"');
            } elseif ($lowerName === 'sec-ch-ua-mobile') {
                $hints['mobile'] = $value === '?1';
            } elseif ($lowerName === 'sec-ch-ua-full-version-list') {
                $hints['browsers'] = $value;
            } elseif ($lowerName === 'sec-ch-ua-arch') {
                $hints['architecture'] = trim($value, '"');
            } elseif ($lowerName === 'sec-ch-ua-bitness') {
                $hints['bitness'] = trim($value, '"');
            }
        }
        
        // Add note about enabling high entropy hints if platform version is missing
        if (!isset($hints['platform_version']) && isset($hints['platform'])) {
            $hints['note'] = 'Enable high-entropy Client Hints for accurate OS version';
        }
        
        return $hints;
    }

    protected function validateRecaptcha($recaptchaResponse)
    {
        if (empty($recaptchaResponse)) {
            return false;
        }

        $config = $this->di->getShared('config');
        $secretKey = $config->recaptcha->secret_key ?? '6Ld0to4rAAAAANhBUX4flrWG95prD4C1YFAcPMoW';
        
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
        $clientIp = $this->getClientIp();
        
        $postData = [
            'secret' => $secretKey,
            'response' => $recaptchaResponse,
            'remoteip' => $clientIp
        ];
        
        // Use cURL to verify with Google
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $verifyUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError || $httpCode !== 200) {
            if ($this->logger) {
                $this->logger->error('reCAPTCHA cURL error', [
                    'curl_error' => $curlError,
                    'http_code' => $httpCode,
                    'client_ip' => $clientIp
                ]);
            }
            return false;
        }
        
        $result = json_decode($response, true);
        
        if ($this->logger) {
            $this->logger->info('reCAPTCHA validation result', [
                'success' => $result['success'] ?? false,
                'score' => $result['score'] ?? null,
                'action' => $result['action'] ?? null,
                'client_ip' => $clientIp
            ]);
        }
        
        return isset($result['success']) && $result['success'] === true;
    }

    protected function useLoggerAdapterMicrosecond(StreamAdapter $adapter)
    {
        $formatter = new LineFormatter();
        $formatter->setDateFormat('Y-m-d\TH:i:s.u');
        $adapter->setFormatter($formatter);
    }

    protected function prepErrorGeneral()
    {
        if (!isset($this->trx_id)) $this->trx_id = -1;

        $res = new stdClass();
        $res->result = false;
        $res->trx_id = $this->trx_id;
        $res->error_code = 'E009';
        $res->error_info = 'Internal Server Error';
        return $res;
    }

    protected function prepErrorMissingParams($trx_id, $missing_params)
    {
        $res = new stdClass();
        $res->result = false;
        $res->trx_id = $trx_id;
        $res->error_code = 'E001';
        $replacee = array('/<num>/', '/<params>/');
        $replacement = array(count($missing_params), join(", ", $missing_params));

        $res->error_info = preg_replace($replacee, $replacement, "Missing <num> parameter(s): <params>");
        return $res;
    }

    protected function prepJsonFromVarErrorMissingParams($trx_id, $missing_params, $var_name)
    {
        $res = new stdClass();
        $res->result = false;
        $res->trx_id = $trx_id;
        $res->error_code = 'E001';
        $replacee = array('/<num>/', '/<params>/');
        $replacement = array(count($missing_params), join(", ", $missing_params));

        $res->error_info = preg_replace($replacee, $replacement, "Missing <num> parameter(s) in $var_name: <params>");
        return $res;
    }

    protected function prepErrorInvalidParam($trx_id, $param)
    {
        $res = new stdClass();
        $res->result = false;
        $res->trx_id = $trx_id;
        $res->error_code = 'E002';
        $replacee = array('/<param>/');
        $replacement = array($param);

        $res->error_info = preg_replace($replacee, $replacement, "Invalid parameter: <param>");
        return $res;
    }

    protected function requireParams($params)
    {
        $missing_params = array();
        if (!isset($this->trx_id)) $this->trx_id = -1;

        foreach ($params as $param) {
            $this->$param = $this->request->getPost($param);
            if ($this->$param == null) $missing_params[] = $param;
        }
        if (count($missing_params) > 0) {
            $res = $this->prepErrorMissingParams($this->trx_id, $missing_params);
            $this->sendResponse($res);
        }
    }

    protected function requireJsonParams($json_data, $params)
    {
        $data = (array)json_decode($json_data, true);
        $missing_params = array();
        if (!isset($this->trx_id)) $this->trx_id = -1;

        foreach ($params as $param) {
            $this->$param = $data[$param];
            if ($this->$param == null) $missing_params[] = $param;
        }
        if (count($missing_params) > 0) {
            $res = $this->prepErrorMissingParams($this->trx_id, $missing_params);
            $this->sendResponse($res);
        }
    }

    protected function requireJsonFromVarParams($json_data, $params, $var_name = '')
    {
        $missing_params = array();
        $tmp_var = array();

        $data = (array)json_decode($json_data, true);

        foreach ($params as $param) {
            if (isset($data[$param])) $tmp_var[$param] = $data[$param];
            else $missing_params[] = $param;
        }
        if (count($missing_params) > 0) {
            $res = $this->prepJsonFromVarErrorMissingParams($this->trx_id, $missing_params, $var_name);
            $this->sendResponse($res);
        }
        $this->$var_name = $tmp_var;
    }

    protected function validateParamsRegex($parameter, $regex)
    {
        if (isset($this->$parameter)) {
            if (!preg_match($regex, $this->$parameter)) {
                if (!isset($this->trx_id)) $this->trx_id = -1;
                $res = $this->prepErrorInvalidParam($this->trx_id, $parameter);
                $this->sendResponse($res);
            }
        }
    }

    protected function getForwardedIp()
    {
        $ips_raw = "";
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips_raw = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        $ips_array = explode(",", $ips_raw);
        return $ips_array[0];
    }

    protected function getClientIp($is_forwarded = false)
    {
        if ($is_forwarded) return $this->getForwardedIp();
        return $this->request->getClientAddress();
    }

    protected function is_in_ip_range($ip, $valid_ips)
    {
        $valid = false;
        foreach ($valid_ips as $valid_ip) {
            if ($this->ip_in_range($ip, $valid_ip)) {
                $valid = true;
                break;
            }
        }
        return $valid;
    }

    private function ip_in_range($ip, $range)
    {
        if (strpos($range, '/') == false) {
            $range .= '/32';
        }
        list($range, $netmask) = explode('/', $range, 2);
        $range_decimal = ip2long($range);
        $ip_decimal = ip2long($ip);
        $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
        $netmask_decimal = ~$wildcard_decimal;
        return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }


      protected function curl_get($url, array $fields = array(), array $headers = array(), $num_retries = 0, $timeout = 45)
    {
        return CurlHelper::Get($this->logger, $this->logger_id, $url, $fields, $headers, $num_retries, $timeout);
    }

    protected function curl_post($url, array $fields = array(), array $headers = array(), $is_json = false, $num_retries = 0, $timeout = 45)
    {
        return CurlHelper::Post($this->logger, $this->logger_id, $url, $fields, $headers, $is_json, $num_retries, $timeout);
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
