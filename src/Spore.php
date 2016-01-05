<?php
namespace PHP_Spore
{
    class Spore
    {
        protected $spec;
    /**
     * @param array $spec
     * @return Spore
     */
        public static function newFromArray(array $spec) : Spore
        {
            return new Spore($spec);
        }
    /**
     * Spore constructor.
     * @param array $spec
     * @return void
     */
        protected function __construct(array $spec)
        {
            $this->spec = $spec;
        }

        protected function exec(array $call, array $arguments)
        {
            $method = strtolower($call["method"] ?? "GET");
            $url = $this->getRequestUrl($call);
            $arguments = $this->getRequestParams($arguments);

            switch($method) {
                case "get":
                    $request = Spore_Request::get($url, $arguments)
                        ->send();
                    break;
                case "post":
                    $request = Spore_Request::post($url, $arguments)
                        ->send();
                    break;
                default:
                    throw new Spore_Exception('Invalid method: ' . $method, 1);
            }

            return $request;
        }
    /**
     * Returns the url for the request
     *
     * @param array $method
     * @return string
     */
        private function getRequestUrl(array $method) : string
        {
            $url = $method["override_url"] ?? $this->spec["base_url"];
            return $url . $method["path"];
        }
    /**
     * @param array $params
     * @return array
     */
        private function getRequestParams(array $arguments) : array
        {
            $params = [];
            if(isset($arguments["form-data"])) {
                $params["json"] = $arguments["form-data"];
            }
            if(isset($arguments["params"])) {
                $params["query"] = $arguments["params"];
            }

            return $params;
        }

    /**
     * Check data for required arguments
     * @param array $method
     * @param array $parameters
     * @return bool
     * @throws Spore_Exception
     */
        private function validateParameters(array $method, array $parameters)
        {
            if(isset($method["params"]) && !empty($method["params"])) {
                foreach($method["params"] as $param => $opts) {
                    if(is_integer($param) && is_string($opts)){
                        $param = $opts;  $opts = null;
                    }
                    // Check for required params
                    if($opts && isset($opts["required"])) {
                        if(!isset($parameters["params"][$param])) {
                            throw new Spore_Exception("Validation failed in requires parameter: " . $param);
                        }
                    }
                }
            }

            return true;
        }

        public function __call($name, $arguments)
        {
            if (!isset ($this->spec['methods'][$name])) {
                throw new Spore_Exception('Invalid method: "' . $name . '"');
            }


            $this->validateParameters($this->spec['methods'][$name], $arguments[0]);
            return $this->exec($this->spec['methods'][$name], $arguments[0]);
        }
    }
}
