<?php

Class ConstructionStagesValidator {


    private $data; // argument
    private $validation = [];

    public $errMsg;
    public $value;

    /**
     * Config validation rule for all fields
     */
    public function __construct($data)
    {
        $this->data = $data;
        //default for null is false
        $this->validation['name'] = ['title' => 'Name','rule' => 'string_max_length:255'];
        $this->validation['start_date'] = ['title' => 'Start Date','rule' => 'date_time'];
        $this->validation['end_date'] = ['title' => 'End Date','rule' => 'date_time|date_min:#start_date#','null' => true];
        $this->validation['durationUnit'] = ['title' => 'Duration Unit','rule' => "enum_value:HOURS,DAYS,WEEKS",'default' => 'DAYS','null' => true];
        $this->validation['color'] = ['title' => 'Color','rule' => "hex_color",'null' => true];
        $this->validation['externalId'] = ['title' => 'External Id','rule' => "string_max_length:255",'null' => true];
        $this->validation['status'] = ['title' => 'Status','rule' => "enum_value:NEW,PLANNED,DELETED",'default' => 'NEW'];

    }

    /**
     * Validate all posted fields 
     * @return boolean
     */
    public function isValid()
    {
        //Validate all params
        foreach($this->data as $name => $value)
        {
            //if any field is not valid, return error
            if(!$this->validate($name,$value))
            {
                return false;
                break;
            }
        }

        return true;
    }

    /**
     * Validate each field based on validation config
     * @return boolean
     */
    public function validate($field,$value)
    {
        //set initial data in this value
        $this->value = $value;

        //check if any validation is defined, or not return true
        if(isset($this->validation[$field]))
        {
            //get rule of field
            $validation = $this->validation[$field];
            $title = $validation['title'];
            $is_null = isset($validation['null']) ? $validation['null'] : false; 
            $rules = explode('|',$this->validation[$field]['rule']);

            
            //if value is empty , set default value if exists
            if(empty($this->value) && isset($validation['default']))
            {
                $this->value = $validation['default'];
            }
            
            //at first, will check data is not null
            if($this->value !== NULL)
            {
                if(!empty($this->value))
                {
                    //if value is not emty, will check rule
                    foreach($rules as $rule)
                    {
                        //if rule is valid
                        $rule = explode(':',$rule);
                        $param = isset($rule[1]) ? $rule[1] : '';
                        //for replace a data in validation is using #fieldname# format
                        if(strpos($param,'#') !== false) 
                        {
                            $param = str_replace('#','',$param);
                            $param = $this->data->{$param};
                        }
                        //call validation rull function
                        if(!$this->{$rule[0]}($title,$this->value,$param)) return false;
                    }
                }
            }
            elseif($is_null === false)
            {
                //if data is null but should not be null, return false
                $this->errMsg = 'The '.$title.' can not be null';
                return false;
            }            
        }

        //if there is no any validation for field, return true;
        return true;
    }
    

    /**
     * Check Max length of data
     *  @return boolean
     */
    private function string_max_length($title,$value,$param) {

        if(strlen($value) > $param) 
        {
            $this->errMsg = 'The '.$title.' should be less than '.$param;
            return false;
        } 
        return true;
    }

    
    /**
     * Check min date for a date-time field
     *  @return boolean
     */
    private function date_min($title,$value,$param) {
       if(!empty($param) && strtotime($value) <= strtotime($param))
       {
            $this->errMsg = 'The '.$title.' should be later than '.$param;
            return false;
       }
       return true;
    }

    
    /**
     * Check Hex color format
     *  @return boolean
     */
    private function hex_color($title,$value,$param)
    {
        if(!preg_match('/^#[a-f0-9]{6}$/i', $value)) 
        {
            $this->errMsg = 'The '.$title.' is not a valid hex color';
            return false;
        }
        return true;
    }

    
    /**
     * Check enum data
     *  @return boolean
     */
    private function enum_value($title,$value,$param)
    {
        $param = explode(',',$param);
        if(!in_array($value,$param)) 
        {
            $this->errMsg = 'The '.$title.' is not valid';
            return false;
        }
        return true;
    }


    
    /**
     * Check date-time iso8601 format
     *  @return boolean
     */
    private function date_time($title,$value,$param)
    {
        if(!empty($value))
        {
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z$/', $value, $parts) == true) {
                $time = gmmktime($parts[4], $parts[5], $parts[6], $parts[2], $parts[3], $parts[1]);
    
                $input_time = strtotime($value);
                if ($input_time === false)
                {
                    $this->errMsg = 'The '.$title.' is not a valid date & time';
                    return false;
                } 
                return $input_time == $time;
            } 
            else { 
                $this->errMsg = 'The '.$title.' is not a valid date & time';
                return false;
            }
        }
        return true;
    }
}
