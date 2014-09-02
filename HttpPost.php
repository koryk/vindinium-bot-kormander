<?php

class HttpPost
{

    public static function post($url, $params = array(), $timeout = 30)
    {

	// use key 'http' even if you send the request to https://...
	$options = array(
	    'http' => array(
		'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		'method'  => 'POST',
		'content' => http_build_query($params),
	    ),
	);
	$context  = stream_context_create($options);
            $startTime = microtime(true);
	$result = file_get_contents($url, false, $context);
	    echo "FILE_GET_CONTENTS TOOK " . (microtime(true)-$startTime)  . "SECONDS\n";
	return $result;
	print_r($result);
        if (!function_exists('curl_init')) {
            echo "Curl library is not installed\n";
            exit();
        }

        $ch = \curl_init($url);
        \curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        \curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        \curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        \curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_HEADER, 1);

        if (is_array($params)) {
            $fieldsString = '';
            foreach ($params as $key => $value) {
                $fieldsString .= $key . '=' . urlencode($value) . '&';
            }
            $fieldsString = rtrim($fieldsString, '&');
            \curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsString);
        }

        $startTime = microtime(true);
        $response = \curl_exec($ch);
	echo "CURL EXEC TOOK " . (microtime(true)-$startTime)  . "SECONDS\n";

        $header_size = \curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	print_r(\curl_getinfo($ch));
        \curl_close($ch);

        $result = array();
        $result['headers_origins'] = substr($response, 0, $header_size);
        $result['content'] = substr($response, $header_size);

        $result['headers'] = array();
        $result['headers']['status_code'] = $http_status;
        $boomHeaders = explode("\n", $result['headers_origins']);
        foreach ($boomHeaders as $value) {
            $boomHeadersMore = explode(': ', $value);
            if (isset($boomHeadersMore[1])) {
                $result['headers'][$boomHeadersMore[0]] = $boomHeadersMore[1];
            }
        }
        unset($result['headers_origins']);
	print_r($result);
die;
        return $result;
    }
}
