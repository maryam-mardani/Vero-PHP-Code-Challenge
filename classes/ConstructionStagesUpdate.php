<?php

class ConstructionStagesUpdate
{
	public $id;
	public $name;
	public $start_date;
	public $end_date;
	public $duration;
	public $durationUnit;
	public $color;
	public $externalId;
	public $status;

	/**
	 * create an object of posted data and set values from $data 
	 */
	public function __construct($data) {

		if(is_object($data)) {
			$vars = get_object_vars($this);

			//prepare field' data of construction stage
			foreach ($vars as $name => $value) {

				if (isset($data->$name)) 
				{
					//set data value 
					$this->$name = $data->$name;
				}
			}
		}

		//set id based on query string params
		$this->id = (isset($_GET['id'])) ? $_GET['id'] : NULL;
	}
}