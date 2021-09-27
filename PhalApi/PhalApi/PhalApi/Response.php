<?php
/**
 * PhalApi_Response 响应类
 *
 * - 拥有各种结果返回状态 ，以及对返回结果 的格式化
 * - 其中：200成功，400非法请求，500服务器错误
 *
 * @package     PhalApi\Response
 * @license     http://www.phalapi.net/license GPL 协议
 * @link        http://www.phalapi.net/
 * @author      dogstar <chanzonghuang@gmail.com> 2014-10-02
 */

abstract class PhalApi_Response {

	/**
	 * @var int $ret 返回状态码，其中：200成功，400非法请求，500服务器错误
	 */
    protected $ret = 200;
    
    /**
     * @var array 待返回给客户端的数据
     */
    protected $data = array();
    
    /**
     * @var string $msg 错误返回信息
     */
    protected $msg = '';
    
    /**
     * @var array $headers 响应报文头部
     */
    protected $headers = array();

    /**
     * @var array $debug 调试信息
     */
    protected $debug = array();

    /**
     * 私钥格式化带验证
     * @param $key
     * @return resource
     */
    private static function _readPrikey()
    {
        $key = "MIICXAIBAAKBgQCihLWIdO9Kh/WhEp90QuX9bcGy6/elqspgcfDKYBP1LfiCLFsP
z0aAGWZsPFQq4UatbIwQwYrw6kFtyv0nBxr+/N/Yhuvy1OLA60mXiP6zW8aGxpnd
oCbIgjHsG2pTNz95wygiv8aMXkICgXWtjd9Qlm0ZyUw72G8ecg0K1mZhtwIDAQAB
AoGAJqsv7F9yXkf6TFB7izStt36pg3J80rjP/WGu+uAgb4p4IjT+l8ToT+t7QM6b
8jX21KXKr+P1NLxwQ/j0AhjBNb+8s8lzDGx6UKXcoGOdngYAi4L4Kvdo6DCilDJr
7tvU6Mni5903hwcemj80+f2Yi0rXbOh5l541GZ4DJJx92ikCQQCy9mpYyhPfr7TW
nE/juNrr4RLGGJ0jeELauGPKE3HYKr9GzTmV/G0v7m217i1tBhZRrcDk77QvrEh/
WkKYdXd/AkEA6HonqHeNyHV6Fr2tKjoBpncyGGWjr6JfLhcsFiMe8HuYlGDbnfu6
8aer+qah3UAl8jDE0++mc+kuRSpWcdPxyQJASRfJwa/vRAoQkyLOolSq3XJU56G/
9G+25nwvDaa5da+n5fQGFBNASTZZitfXp9K3pO6RfS/F6T61cYZc8sXvYwJBAK0k
XWkBMZ28sONC/TdX4GbEm5DEEjb67Xx8UZ9jJOXih27q/GYbV84nHNUfSapo3loU
rGNUN1pYrtdgguVf/tECQDs46IrHZZPm5hf3wb2vsIuAQ72kYc7oN49cy5oWfyb0
hIoXFbEiLRTroQ8kRlDET5mZMLUoN8N3MbqHsQIsH20=";
//        $pem = "-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split($key, 64, "\n") . "-----END RSA PRIVATE KEY-----\n";
        $pem = "-----BEGIN RSA PRIVATE KEY-----\n" . $key . "\n-----END RSA PRIVATE KEY-----\n";
        return openssl_pkey_get_private($pem);
    }

    /**
     * 公钥格式化带验证
     * @param $key
     * @return resource
     */
    private static function _readPubkey()
    {
        //服务端公钥
        $key = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCihLWIdO9Kh/WhEp90QuX9bcGy
6/elqspgcfDKYBP1LfiCLFsPz0aAGWZsPFQq4UatbIwQwYrw6kFtyv0nBxr+/N/Y
huvy1OLA60mXiP6zW8aGxpndoCbIgjHsG2pTNz95wygiv8aMXkICgXWtjd9Qlm0Z
yUw72G8ecg0K1mZhtwIDAQAB";
        //客户端公钥
//        $key = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCS3RIUMb1nuLxQ9O4n4aYMeQDU
//dquf3mBEFmbSysdw911kVpx57t3mE/YUaKk1BHAkewlB3em3KJjcXpHeaG7l+OGx
//1ClROwIokdI7VW+/d0d9aVkAaplfi33kAdj/JuYyJ8GLLN7EyfaB/QdDWv3iSKLj
//vI7yAJ3maGDDtVq4UQIDAQAB";
//        $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(self::ecpss_pubkey, 64, "\n") . "-----END PUBLIC KEY-----\n";
        $pem = "-----BEGIN PUBLIC KEY-----\n" . $key . "\n-----END PUBLIC KEY-----\n";
        return openssl_pkey_get_public($pem);
    }

    /**
     * RSA 私钥、公钥加密
     * @param $data
     * @param $key
     * @param $type
     * @return string
     */
    private static function sslEn($data, $type = 'pi')
    {
        $encrypted = "";
        if ($type == 'pi') {
            //私钥加密
            foreach (str_split($data, 117) as $chunk) {
                openssl_private_encrypt($chunk, $encryptData, self::_readPrikey());
                $encrypted .= $encryptData;
            }
        } elseif ($type == 'pu') {
            //公钥加密
            foreach (str_split($data, 117) as $chunk) {
                openssl_public_encrypt($chunk, $encryptData, self::_readPubkey());
                $encrypted .= $encryptData;
            }
        }
        $encrypt_data = base64_encode($encrypted);//BASE64转换
        return $encrypt_data;
    }

    /**
     * RSA 私钥、公钥解密
     * @param $data
     * @param $key
     * @param $type
     * @return string
     */
    private static function sslDe($data, $type = 'pu')
    {
        $hex_encrypt_data = trim($data);
        $encrypt_data = base64_decode($hex_encrypt_data);//BASE64转换
        $decrypted = "";
        if ($type == 'pu') {
            //公钥解密
            $arrThrunk = str_split($encrypt_data, 256);
            foreach ($arrThrunk as $trunk) {
                $temp = '';
                if (openssl_public_decrypt($trunk, $temp, self::_readPubkey())) {
                    $decrypted .= $temp;
                } else {
                    return '';
                }
            }
        } elseif ($type == 'pi') {
            //私钥解密  ---- 分段解密
            $arrThrunk = str_split($encrypt_data, 256);
            foreach ($arrThrunk as $trunk) {
                $temp = '';
                if (openssl_private_decrypt($trunk, $temp, self::_readPrikey())) {
                    $decrypted .= $temp;
                } else {
                    return '';
                }
            }
        }
        return $decrypted;
    }

    /** ------------------ setter ------------------ **/

    /**
     * 设置返回状态码
     * @param int $ret 返回状态码，其中：200成功，400非法请求，500服务器错误
     * @return PhalApi_Response
     */
    public function setRet($ret) {
    	$this->ret = $ret;
    	return $this;
    }
    
    /**
     * 设置返回数据
     * @param array/string $data 待返回给客户端的数据，建议使用数组，方便扩展升级
     * @return PhalApi_Response
     */
    public function setData($data) {
    	$this->data = $data;
    	return $this;
    }
    
    /**
     * 设置错误信息
     * @param string $msg 错误信息
     * @return PhalApi_Response
     */
    public function setMsg($msg) {
    	$this->msg = $msg;
    	return $this;
    }

    /**
     * 设置调试信息
     * @param   string  $key        键值标识
     * @param   mixed   $value      调试数据
     * @return  PhalApi_Response
     */
    public function setDebug($key, $value) {
        if (DI()->debug) {
            $this->debug[$key] = $value;
        }
        return $this;
    }

    /**
     * 添加报文头部
     * @param string $key 名称
     * @param string $content 内容
     */
    public function addHeaders($key, $content) {
    	$this->headers[$key] = $content;
    }

    /** ------------------ 结果输出 ------------------ **/

    /**
     * 结果输出
     */
    public function output() {
    	$this->handleHeaders($this->headers);

        $rs = $this->getResult();
//        var_dump($rs);
        if(empty($rs['data']['jmreq'])){
            $rs = $this->formatResult($rs);
            //私钥加密
            $rs = self::sslEn($rs);
        }else{
            $rs = $this->formatResult($rs);
        }

    	echo $rs;
    }
    
    /** ------------------ getter ------------------ **/
    
    /**
     * 根据状态码调整Http响应状态码
     */
    public function adjustHttpStatus() {
        $httpStatus = array ( 
            100 => 'HTTP/1.1 100 Continue', 
            101 => 'HTTP/1.1 101 Switching Protocols', 
            200 => 'HTTP/1.1 200 OK', 
            201 => 'HTTP/1.1 201 Created', 
            202 => 'HTTP/1.1 202 Accepted', 
            203 => 'HTTP/1.1 203 Non-Authoritative Information', 
            204 => 'HTTP/1.1 204 No Content', 
            205 => 'HTTP/1.1 205 Reset Content', 
            206 => 'HTTP/1.1 206 Partial Content', 
            300 => 'HTTP/1.1 300 Multiple Choices', 
            301 => 'HTTP/1.1 301 Moved Permanently', 
            302 => 'HTTP/1.1 302 Found', 
            303 => 'HTTP/1.1 303 See Other', 
            304 => 'HTTP/1.1 304 Not Modified', 
            305 => 'HTTP/1.1 305 Use Proxy', 
            307 => 'HTTP/1.1 307 Temporary Redirect', 
            400 => 'HTTP/1.1 400 Bad Request', 
            401 => 'HTTP/1.1 401 Unauthorized', 
            402 => 'HTTP/1.1 402 Payment Required', 
            403 => 'HTTP/1.1 403 Forbidden', 
            404 => 'HTTP/1.1 404 Not Found', 
            405 => 'HTTP/1.1 405 Method Not Allowed', 
            406 => 'HTTP/1.1 406 Not Acceptable', 
            407 => 'HTTP/1.1 407 Proxy Authentication Required', 
            408 => 'HTTP/1.1 408 Request Time-out', 
            409 => 'HTTP/1.1 409 Conflict', 
            410 => 'HTTP/1.1 410 Gone', 
            411 => 'HTTP/1.1 411 Length Required', 
            412 => 'HTTP/1.1 412 Precondition Failed', 
            413 => 'HTTP/1.1 413 Request Entity Too Large', 
            414 => 'HTTP/1.1 414 Request-URI Too Large', 
            415 => 'HTTP/1.1 415 Unsupported Media Type', 
            416 => 'HTTP/1.1 416 Requested range not satisfiable', 
            417 => 'HTTP/1.1 417 Expectation Failed', 
            500 => 'HTTP/1.1 500 Internal Server Error', 
            501 => 'HTTP/1.1 501 Not Implemented', 
            502 => 'HTTP/1.1 502 Bad Gateway', 
            503 => 'HTTP/1.1 503 Service Unavailable', 
            504 => 'HTTP/1.1 504 Gateway Time-out',
            505 => 'HTTP/1.1 505 HTTP Version not supported',  
        );

        $str = isset($httpStatus[$this->ret]) ? $httpStatus[$this->ret] : "HTTP/1.1 {$this->ret} PhalApi Unknown Status";
        @header($str);

        return $this;
    }

    public function getResult() {
        $rs = array(
            'ret'   => $this->ret,
            'data'  => $this->data,
            'msg'   => $this->msg,
        );

        if (!empty($this->debug)) {
            $rs['debug'] = $this->debug;
        }

        return $rs;
    }

	/**
	 * 获取头部
	 * 
	 * @param string $key 头部的名称
	 * @return string/array 对应的内容，不存在时返回NULL，$key为NULL时返回全部
	 */
    public function getHeaders($key = NULL) {
        if ($key === NULL) {
            return $this->headers;
        }

        return isset($this->headers[$key]) ? $this->headers[$key] : NULL;
    }

    /** ------------------ 内部方法 ------------------ **/

    protected function handleHeaders($headers) {
    	foreach ($headers as $key => $content) {
    		@header($key . ': ' . $content);
    	}
    }

    /**
     * 格式化需要输出返回的结果
     *
     * @param array $result 待返回的结果数据
     *
     * @see PhalApi_Response::getResult()
     */
    abstract protected function formatResult($result);
}
