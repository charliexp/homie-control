<?php

namespace App;

class BaseController {

    protected $_app;
    protected $args;
    protected $db;
    protected $mqtt;
    protected $settings;

    protected $_request;
    protected $_response;

    
    public function __construct(\Slim\App $app, ArgParser $args, MySQL $db, \phpMQTT $mqtt, $settings) {
        $this->_app = $app;

        $this->args = $args;
        $this->db = $db;
        $this->mqtt = $mqtt;

        $this->settings = $settings;
    }


    /**
     * Hijack $request and $response by defining child methods as protected
     * @param  string   $method
     * @param  array    $args   
     * @return callable
     */
    public function __call($method, $fnargs) {
        list($request, $response, $args) = $fnargs;

        $this->_request = $request;
        $this->_response = $response;

        if (method_exists($this, $method)) {
            return call_user_func([$this, $method], $request, $response, $args);
        } else {
            print $method.' doesnt exist on controller '.get_class($this);
            die();
        }
    }

    /**
     * 
     * @param  string Error message
     * @param  int    HTTP Error Code
     * @return null
     */
    protected function error($message, $code=400) {
        // $this->_response->withJSON($message)->withStatus($code);
        throw new \AppException($message, $code);
    }


    /**
     * JSONify an object and output
     * @param  object to JSONify
     * @return null
     */
    protected function output($object=null) {
        if (is_null($object))
            return $this->_response;
        else
            return $this->_response->withJSON($object);
    }



    /**
     * Verfiy $arg is present and valid, if not throw json error
     * @param  string  argument
     * @param  boolean allow this arg to be empty
     * @return null
     */
    protected function required($arg, $allowEmpty=False) {
        if (!$this->args->has($arg)) $this->error($arg.' is required');
        if (!$allowEmpty && $this->args->isEmpty($arg)) $this->error($arg.' cannot be empty');
        if ($this->args->valid($arg)) $this->error($this->args->valid($arg));
    }



    /**
     * Calculate start end for pagination
     * @param  array    argument
     * @param  per_page default value
     * @return null
     */
    protected function _get_start_end(&$args, $per_page=15) {
        $start = 0;
        $pp = $this->args->has('per_page') ? $this->args->value('per_page') : $per_page;
        $end = $pp;
        
        if ($this->args->has('page')) {
            $pg = $this->args->value('page') - 1;
            $start = $pg*$pp;
        }
        
        array_push($args, $start);
        array_push($args, $end);
    }


}
