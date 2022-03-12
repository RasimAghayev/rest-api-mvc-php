<?php
class ValidField {
    private $_errors = array();

    /**
     * ValidField constructor.
     * @param array $data
     * @param array $rules
     */
    public function __construct($data,$rules=[]){
            foreach($data as $item => $item_value) {
                if (key_exists($item, $rules)) {
                        $exp[$item]=$this->explodetext('|',$rules[$item]);
                        foreach($exp[$item] as $rule => $rule_value){
                            if(method_exists($this,$rule_value)) {
                                call_user_func_array([$this, $rule_value], array($item_value,$rule_value,$item));
                            }else{
                                $ruleList=$this->explodetext(':',$rule_value);
                                call_user_func_array([$this, $ruleList[0]], array($item_value,$ruleList[1],$item));
                            }
                        }
                }else{
                    $this->addError($item, ucwords($item). ' item not found.');
                }
            }
        }
    /**
     * @param string $criteria
     * @param string $value
     * @return array
     */
    public function explodeText($criteria,$value): array {
        return explode($criteria,$value);
    }
    /**
     * @param bool $item_value
     * @param bool $rule_value
     * @param string $item
     */
    private function req($item_value,$rule_value,$item){
        if(empty($item_value) && $rule_value){
            $this->addError($item,ucwords($item). ' required');
        }
    }
    /**
     * @param $item_value
     * @param $rule_value
     * @param $item
     */
    private function min($item_value,$rule_value,$item){
        if(strlen($item_value) < $rule_value){
            $this->addError($item, ucwords($item). ' should be minimum '.$rule_value. ' characters');
        }
    }
    /**
     * @param $item_value
     * @param $rule_value
     * @param $item
     */
    private function max($item_value,$rule_value,$item){
        if(strlen($item_value) > $rule_value){
            $this->addError($item, ucwords($item). ' should be maximum '.$rule_value. ' characters');
        }
    }

    /**
     * @param $item_value
     * @param $rule_value
     * @param $item
     */
    private function numeric($item_value,$rule_value,$item){
        if(!ctype_digit($item_value) && $rule_value){
            $this->addError($item, ucwords($item). ' should be numeric');
        }
    }

    /**
     * @param $item_value
     * @param $rule_value
     * @param $item
     */
    private function alpha($item_value,$rule_value,$item){
        if(!ctype_alpha($item_value) && $rule_value){
            $this->addError($item, ucwords($item_value). ' should be alphabetic characters');
        }
    }

    /**
     * @param $item_value
     * @param $rule_value
     * @param $item
     */
    private function email($item_value,$rule_value,$item){
        if(!filter_var($item_value, FILTER_VALIDATE_EMAIL) && $rule_value){
            $this->addError($item, ucwords($item_value). ' should be email style');
        }
    }

    /**
     * @param $item_value
     * @param $rule_value
     * @param $item
     */
    private function url($item_value,$rule_value,$item){
        if(!filter_var($item_value, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED) && $rule_value){
            $this->addError($item, ucwords($item_value). ' should be URL style');
        }
    }

    /**
     * @param $item_value
     * @param $rule_value
     * @param $item
     */
    private function strength($item_value,$rule_value,$item){
        $ruleList=[
            "uppercase" => preg_match('@[A-Z]@', $item_value),
            "lowercase" => preg_match('@[a-z]@', $item_value),
            "number" => preg_match('@[0-9]@', $item_value),
            "specialChars" => preg_match('@[^\w]@', $item_value)
//            "duplicate" => preg_match_all('/([a-z])(?=.*\1)/', $item_value, $matches)
        ];
        $rule_values=$this->explodeText(",",$rule_value);
        foreach ($rule_values as $rule_key=>$rule_val) {
            if (!$ruleList[$rule_val]) {
                $this->addError($item, "Should include at least one ".ucwords($rule_val));
            }
        }
    }

    /**
     * @param $item
     * @param $error
     */
    private function addError($item, $error){
        $this->_errors[$item][] = $error;
    }
    /**
     * @return array|bool
     */
    public function error(){
        if(empty($this->_errors)) return false;
        return $this->_errors;
    }
}