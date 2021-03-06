<?php

    namespace Rexster;
    
    class Client {
    
        protected $_base_url = null;
        protected $_graph_name = null;
        protected $_handle = null;
        protected $_last_request = null;
        protected $_response_code = null;
        protected $_response_message = null;
        
        protected $_offset_start = null;
        protected $_offset_end = null;
        protected $_return_keys = null;
        
        const HEADER_JSON = 'application/json';
        const HEADER_VND_REXSTER_JSON = 'application/vnd.rexster-v1+json';
        const HEADER_VND_REXSTER_TYPED_JSON = 'application/vnd.rexster-typed-v1+json';
        const HEADER_FORM_URLENCODED = 'application/x-www-form-urlencoded';
    
        public function __construct($base_url, $graph_name){
    
            if (!function_exists('curl_init')) {
                throw new \RuntimeException('CURL extension not enabled or installed');
            }

            $this->setGraphBaseUrl($base_url)->setGraphName($graph_name);

        }
        
        /**
         * Set the graph name
         * 
         * @param string $graph_name
         * @throws \InvalidArgumentException
         * @return \Rexster\Client
         */
        public function setGraphName($graph_name){
            
            $graph_name = trim($graph_name);
            if (empty($graph_name)){
                throw new \InvalidArgumentException('Invalid graph name');
            }
            
            $this->_graph_name = $graph_name;
            
            return $this;
            
        }
    
        /**
         * Get the graph name
         * 
         * @return string
         */
        public function getGraphName(){
    
            return $this->_graph_name;
    
        }
        
        /**
         * Set the base url of the graph
         * 
         * @param string $base_url
         * @throws \InvalidArgumentException
         * @return \Rexster\Client
         */
        public function setGraphBaseUrl($base_url){
            
            $base_url = trim($base_url);
            if (empty($base_url)){
                throw new \InvalidArgumentException('Invalid base url');
            }
            
            $this->_base_url = rtrim($base_url, '/') . '/graphs';

            return $this;
            
        }
    
        /**
         * Get the base url of the graph
         * 
         * @return string
         */
        public function getGraphBaseUrl(){
    
            return $this->_base_url;
    
        }
    
        /**
         * Performs a GET request to a custom endpoint with payload $data
         * 
         * @param string $url
         * @param array $data
         * @throws \InvalidArgumentException
         * @throws \RuntimeException
         * @return Ambigous <boolean, \Rexster\Object>
         */
        public function getCustom($url, array $data = array()){
    
            if (empty($url)){
                throw new \InvalidArgumentException('Invalid URL');
            }
    
            $response = $this->makeRequest('GET', "/{$url}", $data);
            if (!$response){
                throw new \RuntimeException($this->getLastErrorMessage());
            }

            return Factory::getGeneric($this, $response);
    
        }
    
        /**
         * Performs a POST request to a custom endpoint with payload $data
         * 
         * @param string $url
         * @param array $data
         * @throws \InvalidArgumentException
         * @throws \RuntimeException
         * @return Ambigous <boolean, \Rexster\Object>
         */
        public function postCustom($url, array $data = array()){
    
            if (empty($url)){
                throw new \InvalidArgumentException('Invalid URL');
            }
    
            $response = $this->makeRequest('POST', "/{$url}", $data);
            if (!$response){
                throw new \RuntimeException($this->getLastErrorMessage());
            }

            return Factory::getGeneric($this, $response);
    
        }
    
        /**
         * Performs a PUT request to a custom endpoint with payload $data
         * 
         * @param string $url
         * @param array $data
         * @throws \InvalidArgumentException
         * @throws \RuntimeException
         * @return Ambigous <boolean, \Rexster\Object>
         */
        public function putCustom($url, array $data = array()){
    
            if (empty($url)){
                throw new \InvalidArgumentException('Invalid URL');
            }
    
            $response = $this->makeRequest('PUT', "/{$url}", $data);
            if (!$response){
                throw new \RuntimeException($this->getLastErrorMessage());
            }

            return Factory::getGeneric($this, $response);
    
        }
    
        /**
         * Makes a reques to the Rexster API
         * 
         * @param string $method
         * @param string $path
         * @param array $data
         * @param string $content_type
         * @param string $accept
         * @throws \InvalidArgumentException
         * @throws \RuntimeException
         * @return boolean|mixed
         */
        public function makeRequest($method, $path = null, array $data = array(), $content_type = self::HEADER_JSON, $accept = self::HEADER_JSON){
   
            if (empty($method)){
                throw new \InvalidArgumentException('Invalid request method');
            }
    
            $method = strtoupper($method);
    
            $url = $this->getGraphBaseUrl() . '/' . $this->getGraphName();
            if ($path){
                $url .= '/' . trim($path, '/');
            }

            $options = array(
                CURLOPT_URL => $url,
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POST => false,
                CURLOPT_POSTFIELDS => null,
                CURLOPT_CONNECTTIMEOUT => 0,
                CURLOPT_TIMEOUT => 3600
            );
    
            $headers = array("Accept: {$accept}");
    
            if (!empty($data)){
                /*
                if (is_array($data) && ($method == 'POST' || $method == 'PUT')){
                    
                    foreach ($data as $k => &$v){
                        
                        if (substr($k, 0, 1) == '_') continue;
                        if (is_string($v)) continue;

                        if (is_int($v)){
                            $v = "(integer,{$v})";
                        }else if (is_float($v)){
                            $v = "(float,{$v})";
                        }else if (is_long($v)){
                            $v = "(long,{$v})";
                        }else if (is_double($v)){
                            $v = "(double,{$v})";
                        }
                        
                    }
                    
                }
                */
    
                switch ($method){
    
                    case 'POST':
                    case 'PUT':

                        foreach ($data as $k => &$v){
                            if (is_null($v)){
                                unset($data[$k]);
                            }
                        }
                        
                        foreach ($data as $k => &$v){
                            if (is_string($v)){
                                $v = utf8_encode($v);
                            }
                        }
                        $data = json_encode($data);
                    
                        $options[CURLOPT_POSTFIELDS] = $data;
    
                        if ($method == 'POST'){
                            $options[CURLOPT_POST] = true;
                        }
                        
                        $content_length = strlen($data);
    
                        break;
                        
                    case 'GET':
                        
                        $content_type = self::HEADER_FORM_URLENCODED;
                        $data = http_build_query($data);
                        $options[CURLOPT_URL] .= '?' . $data;
                        $content_length = strlen($data);
                    
                        break;
                        
                    case 'DELETE':
    
                        $data = http_build_query($data);
                        $options[CURLOPT_URL] .= '?' . $data;
                          $content_type = false;
                        $content_length = false;
                        
                        break;

                }

            }
            
            if (isset($content_length)){
                $headers[] = "Content-Length: {$content_length}";
            }
            
            if ($content_type){
                $headers[] = "Content-type: {$content_type}";
            }

            if ($method == 'GET'){
                
                $filters = array();
                if ($this->getOffsetStart() !== null){
                    $filters['rexster.offset.start'] = $this->getOffsetStart();
                }
                
                if ($this->getOffsetEnd() > 0){
                    $filters['rexster.offset.end'] = $this->getOffsetEnd();
                }
                
                if ($this->getReturnKeys()){
                    $filters['rexster.returnKeys'] = implode(',', $this->getReturnKeys());
                }

                if (!empty($filters)){
                    $options[CURLOPT_URL] .= (strstr($options[CURLOPT_URL], '?')? '&' : '?') . http_build_query($filters);
                }

            }    

            $options[CURLOPT_HTTPHEADER] = $headers;

            $ch = $this->_getHandle();
            curl_setopt_array($ch, $options);
            
            $this->_last_request = $options;

            $response = curl_exec($ch);
            $response = json_decode($response, true);

            if (!curl_errno($ch)){
                
                $this->_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                if (is_array($response) && (isset($response['message']) || isset($response['error']))){
                    
                    $this->_response_message = '';
                    if (!empty($response['message'])){
                        $this->_response_message .= $response['message'];
                    }
                    
                    if (!empty($response['error'])){
                        $this->_response_message .= $response['error'];
                    }

                    return false;
                    
                }else if (empty($response)){
                    
                    return false;
                    
                }else{
                    
                       return $response;
                       
                }
                
            }else{
    
                $error_msg = "CURL {$method} request to {$url} failed. Response Code: {$this->_response_code}";
                if (isset($response['data']['message'])){
                    $error_msg .= " - Message: {$response['message']}";
                }
                $error_msg .= 'CURL Options: ' . print_r($options, true);
                $error_msg .= 'Payload: ' . print_r($data, true);
                $error_msg .= 'CURL Error: ' . curl_error($ch);
                $error_msg .= 'CURL Info: ' . print_r(curl_getinfo($ch), true);
    
                throw new \RuntimeException($error_msg, $this->_response_code);
                
            }
    
        }
        
        /**
         * Get the last CURL request that was sent
         * 
         * @return array
         */
        public function getLastRequest(){
            
            return $this->_last_request;
            
        }
        

        /**
         * Alias of @method getResponseMessage()
         * 
         * @return string
         */
        public function getLastErrorMessage(){
            
            return $this->getResponseMessage();
            
        }
        
        /**
         * Get the response message from the Rexster api if there is any
         * 
         * @return string
         */
        public function getResponseMessage(){
            
            return $this->_response_message;
            
        }
        
        /**
         * Get the CURL response code from the last request
         * 
         * @return int
         */
        public function getResponseCode(){
            
            return $this->_response_code;
            
        }
        
        public function getReturnKeys(){
            
            return $this->_return_keys;
            
        }
        
        /**
         * Expects a comma separated list of property names to return in the results. 
         * Element meta-data will always be returned even if rexster.returnKeys are specified. 
         * If a valid value for this parameter is not specified, then all properties are returned.
         * 
         * @param array $keys
         * @return \Rexster\Client
         */
        public function setReturnKeys(array $keys){
            
            $this->_return_keys = $keys;
            
            return $this;
            
        }
        
        /**
         * Expects a numeric value that represents the start point for returning a set of records and is used in conjunction with rexster.offset.end to allow for paging of results. 
         * If used without a valid rexster.offset.end parameter specified, Rexster will return all remaining records in the set.
         * 
         * @param int $start
         * @return \Rexster\Client
         */
        public function setOffsetStart($start){
            
            $this->_offset_start = $start;
            
            return $this;
            
        }

        /**
         * Expects a numeric value that represents the end point for returning a set of records and is used in conjunction with rexster.offset.start to allow for paging of results. 
         * If used without a valid rexster.offset.start parameter specified, Rexster will assume the start value to be zero.
         * 
         * @param int $end
         * @return \Rexster\Client
         */
        public function setOffsetEnd($end){
            
            $this->_offset_end = $end;
            
            return $this;
            
        }
        
        public function getOffsetStart(){
            
            return $this->_offset_start;
            
        }
        
        public function getOffsetEnd(){
            
            return $this->_offset_end;
            
        }
        
        /**
         * Get a CURL handle
         * 
         * @return CURL
         */
        protected function _getHandle(){
    
            if (!$this->_handle){
                $this->_handle = curl_init();
            }
                
            return $this->_handle;
    
        }
    
        public function __destruct(){
    
            if ($this->_handle){
                curl_close($this->_handle);
            }
    
        }
        
        private function _isAssoc($array){
            
            return (bool) count(array_filter(array_keys($array), 'is_string'));
            
        }
    
    }