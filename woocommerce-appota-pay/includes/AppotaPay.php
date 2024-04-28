<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
class AppotaPay
{

    const API_URL_SANDBOX = 'https://payment.dev.appotapay.com';
    const API_URL_LIVE = 'https://payment.appotapay.com';
    private $partnerCode;
    private $apiKey;
    private $secretKey;

    /**
     * @param array config(partner_code, api_key, secret_key)
     */
    public function __construct(array $config)
    {
        $this->partnerCode = $config['partner_code'];
        $this->apiKey = $config['api_key'];
        $this->secretKey = $config['secret_key'];
    }

    /**
     * @param array $orderDetails(order_id, order_info, amount)
     * @param array $paymentDetails(bank_code, method)
     * @return false|mixed
     *
     * if format error, sign error, status_code != 200 ==> return false
     * else return array data ( map with status_http = 200 )
     */
    public function makeBankPayment(array $orderDetails, array $paymentDetails, $isSandBoxMode = false)
    {
        $params =[
            'orderId' => $orderDetails['order_id'],
            'orderInfo' => $orderDetails['order_info'],
            'amount' => $orderDetails['amount'],
//            'bankCode' => $paymentDetails['bank_code'],
//            'paymentMethod' => $paymentDetails['method'],
            'notifyUrl' =>    $paymentDetails['notiUrl'],
            'redirectUrl' =>  $paymentDetails['redirectUrl'],
            'clientIp' => $paymentDetails['client_ip']
        ];
        write_log('all param payment = ');
        write_log($params);
        ksort($params);
        $signData = self::generateSignData($params);
        $params['signature'] = hash_hmac('sha256', $signData, $this->secretKey);

        write_log("in function pay with bank, partnercode = $this->partnerCode, api Key = $this->apiKey , secret= $this->secretKey ");

        $headers = [
            'X-APPOTAPAY-AUTH: Bearer '.$this->generateJWT($this->partnerCode, $this->apiKey, $this->secretKey),
            'Content-Type: application/json'
        ];
        if($isSandBoxMode){
            write_log('use sandbox mode');
            $apiUrl = self::API_URL_SANDBOX . '/api/v1/orders/payment/wc';
        } else {
            write_log('use live mode');
            $apiUrl = self::API_URL_LIVE . '/api/v1/orders/payment/wc';
        }

        $result = $this->makeRequest($apiUrl, json_encode($params), $headers);
        if(empty($result)){
            return false;
        }

        try {
            $result = json_decode($result,true);
        } catch (\Exception $e ){
            return false;
        }

        if(!$this->verifySignReponseBankPayment($result)){
            return false;
        }

        return $result;
    }

    /**
     * @param array $params
     * @return string
     */
    public static function generateSignData(array $params)
    {
        ksort($params);
        array_walk($params, function(&$item, $key) {
            $item = $key.'='.$item;
        });
        return implode('&', $params);
    }

    /**
     * @param string $partnerCode
     * @param string $apiKey
     * @param string $secretKey
     */
    public function generateJWT(string $partnerCode, string $apiKey, string $secretKey)
    {
        $now = time();
        $exp = $now + 600;
        $header = array(
            'typ' => 'JWT',
            'alg' => 'HS256',
            'cty' => "appotapay-api;v=1"
        );
        $payload = array(
            'iss' => $partnerCode,
            'jti' => $apiKey . '-' . $now,
            'api_key' => $apiKey,
            'exp' => $exp
        );
        write_log('payload = ');
        write_log($payload);
        return JWT::encode($payload, $secretKey, 'HS256', null, $header);
    }

    /*
     * function make request
     * url : string | url request
     * params : array | params request
     * method : string(POST,GET) | method request
     */
    private function makeRequest($url, $params, $headers)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60); // Time out 60s
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // connect time out 5s

        $result = curl_exec($ch);;
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //must have 1 log reslut for debug
        write_log('reslut=');
        write_log($result);

        // to do delete all log below
        write_log('status=');
        write_log($status);
        write_log('url=');
        write_log($url);

        if (curl_error($ch)) {
            return false;
        }

        if ($status !== 200) {
            curl_close($ch);
            return false;
        }
        // close curl
        curl_close($ch);

        return $result;
    }

//    public function checkBankPayment(array $orderDetails, array $paymentDetails)
//    {
//        $params =[
//            'orderId' => $orderDetails['order_id'],
//            'orderInfo' => $orderDetails['order_info'],
//            'amount' => $orderDetails['amount'],
//            'bankCode' => $paymentDetails['bank_code'],
//            'paymentMethod' => $paymentDetails['method'],
//            'notifyUrl' =>   route('handleResponseNotiPay'),
//            'redirectUrl' =>  route('handleReponsePay'),
//            'clientIp' => $paymentDetails['client_ip']
//        ];
//        ksort($params);
//        $signData = $this->generateSignData($params);
//        $params['signature'] = hash_hmac('sha256', $signData, $this->secretKey);
//
//        $headers = [
//            'X-APPOTAPAY-AUTH: Bearer '.$this->generateJWT($this->partnerCode, $this->apiKey, $this->secretKey),
//            'Content-Type: application/json'
//        ];
//        dump('param=',$params,'header=',$headers);
//        $apiUrl = self::API_URL . '/api/v1/orders/payment/bank';
//        $result = $this->makeRequest($apiUrl, json_encode($params), $headers);
//        $result = json_decode($result);
//        dump($result);
//        if (isset($result->errorCode) && $result->errorCode === 0) {
//            return $result->paymentUrl;
//        }
//
//        return null;
//    }


    /**
     * @param string $algo
     * @param string $signVerify
     * @param array $dataVerify
     * @param string $secretKey
     * @return bool
     */
    public static function verifySign(string $signVerify, array $dataVerify, string $secretKey,string $algo = 'sha256'){
        $signAgain = null;
        $dataSignAgain = self::generateSignData($dataVerify);
        switch ($algo) {
            case 'sha256':
                $signAgain =  hash_hmac('sha256', $dataSignAgain, $secretKey);
                break;
            default:
                $signAgain = null;
                break;
        }

        if(is_null($signAgain) or is_null($signVerify)) {
            return false;
        }

        if(hash_equals($signVerify,$signAgain)){
            return true;
        }
        return false;
    }

    /**
     * @param array $reponse
     * @return bool
     */
    public function verifySignReponseBankPayment(array $reponse){
        $errorCode = isset($reponse['errorCode']) ?$reponse['errorCode'] : null;
        $message = isset($reponse['message']) ? $reponse['message'] : null;
        $orderId = isset($reponse['orderId']) ? $reponse['orderId'] : null;
        $amount = isset($reponse['amount']) ? $reponse['amount'] : null;
        $paymentUrl = isset($reponse['paymentUrl']) ? $reponse['paymentUrl'] : null;
        $signature = isset($reponse['signature']) ? $reponse['signature'] : null;

        if(is_null($errorCode) or is_null($message) or is_null($orderId) or is_null($amount) or is_null($paymentUrl) or is_null($signature)) {
            return false;
        }
        $dataVerify = array('errorCode'=>$errorCode, 'message'=>$message, 'orderId'=>$orderId, 'amount'=>$amount, 'paymentUrl'=>$paymentUrl );

        return self::verifySign($signature,$dataVerify,$this->secretKey);
    }


    /**
     * @return array|false
     * verify and get param method GET when redirect URL after payment
     * if format param error, sign error  ==> return false
     * else return array data
     */
    public static function verifyAndGetDataRedirectUrl(string $secretKey){
        $partnerCode = isset($_GET['partnerCode']) ? $_GET['partnerCode'] : null;
        $apiKey = isset($_GET['apiKey']) ? $_GET['apiKey'] : null;
        $amount = isset($_GET['amount']) ? $_GET['amount'] : null;
        $currency = isset($_GET['currency']) ? $_GET['currency'] : null;
        $orderId = isset($_GET['orderId']) ? $_GET['orderId'] : null;
        $bankCode = isset($_GET['bankCode']) ? $_GET['bankCode'] : null;
        $paymentMethod = isset($_GET['paymentMethod']) ? $_GET['paymentMethod'] : null;
        $paymentType = isset($_GET['paymentType']) ? $_GET['paymentType'] : null;
        $appotapayTransId = isset($_GET['appotapayTransId']) ? $_GET['appotapayTransId'] : null;
        $errorCode = isset($_GET['errorCode']) ? $_GET['errorCode'] : null;
        $message = isset($_GET['message']) ? $_GET['message'] : null;
        $transactionTs = isset($_GET['transactionTs']) ? $_GET['transactionTs'] : null;
        $extraData = isset($_GET['extraData']) ? $_GET['extraData'] : null;
        $signature = isset($_GET['signature']) ? $_GET['signature'] : null;

        if( is_null($partnerCode) or is_null($apiKey) or is_null($amount) or is_null($currency) or is_null($orderId) or is_null($bankCode) or is_null($paymentMethod) or is_null($paymentType)
            or is_null($appotapayTransId) or is_null($errorCode) or is_null($message) or is_null($transactionTs) or is_null($extraData)  or is_null($signature) ){
            return false;
        }

        $dataRedirect = array('partnerCode'=>$partnerCode, 'apiKey'=>$apiKey,'amount'=>$amount,'currency'=>$currency,'orderId'=>$orderId,'bankCode'=>$bankCode,
            'paymentMethod'=>$paymentMethod, 'paymentType'=>$paymentType,'appotapayTransId'=>$appotapayTransId,'errorCode'=>$errorCode,'message'=>$message,
            'transactionTs'=>$transactionTs, 'extraData'=>$extraData,'signature'=>$signature);
        $dataVerify = $dataRedirect;
        unset($dataVerify['signature']);
        if(!self::verifySign($signature,$dataVerify,$secretKey)){
            return false;
        }

        return $dataRedirect;
    }


    /**
     * @param string $secretKey
     * @return array|false
     * verify and get param method POST IPN after payment
     * if format param error, sign error  ==> return false
     * else return array data
     */
    public static function verifyAndGetDataIPN(string $secretKey){
        $dataPOST = json_decode(file_get_contents('php://input'), true);
        $errorCode = isset($dataPOST['errorCode']) ? $dataPOST['errorCode'] : null;
        $message = isset($dataPOST['message']) ? $dataPOST['message'] : null;
        $partnerCode = isset($dataPOST['partnerCode']) ? $dataPOST['partnerCode'] : null;
        $apiKey = isset($dataPOST['apiKey']) ? $dataPOST['apiKey'] : null;
        $amount = isset($dataPOST['amount']) ? $dataPOST['amount'] : null;
        $currency = isset($dataPOST['currency']) ? $dataPOST['currency'] : null;
        $orderId = isset($dataPOST['orderId']) ? $dataPOST['orderId'] : null;
        $bankCode = isset($dataPOST['bankCode']) ? $dataPOST['bankCode'] : null;
        $paymentMethod = isset($dataPOST['paymentMethod']) ? $dataPOST['paymentMethod'] : null;
        $paymentType = isset($dataPOST['paymentType']) ? $dataPOST['paymentType'] : null;
        $appotapayTransId = isset($dataPOST['appotapayTransId']) ? $dataPOST['appotapayTransId'] : null;
        $transactionTs = isset($dataPOST['transactionTs']) ? $dataPOST['transactionTs'] : null;
        $extraData = isset($dataPOST['extraData']) ? $dataPOST['extraData'] : null;
        $signature = isset($dataPOST['signature']) ? $dataPOST['signature'] : null;

        if( is_null($errorCode) or is_null($message) or is_null($partnerCode) or is_null($apiKey) or is_null($amount) or is_null($currency) or is_null($orderId) or is_null($bankCode)
            or is_null($paymentMethod) or is_null($paymentType) or is_null($appotapayTransId) or is_null($transactionTs) or is_null($extraData)  or is_null($signature) ){
            return false;
        }

        $dataIPN = array('errorCode'=>$errorCode, 'message'=>$message,'partnerCode'=>$partnerCode,'apiKey'=>$apiKey,'amount'=>$amount,'currency'=>$currency,
            'orderId'=>$orderId, 'bankCode'=>$bankCode,'paymentMethod'=>$paymentMethod,'paymentType'=>$paymentType,'appotapayTransId'=>$appotapayTransId,
            'transactionTs'=>$transactionTs, 'extraData'=>$extraData, 'signature'=>$signature);
        $dataVerify = $dataIPN;
        unset($dataVerify['signature']);

        if(!self::verifySign($signature,$dataVerify,$secretKey)){
            return false;
        }

        return $dataIPN;
    }





}