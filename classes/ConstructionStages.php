<?php

class ConstructionStages
{
	private $db;

	public function __construct()
	{
		$this->db = Api::getDb();
	}

	public function getAll()
	{
		//Get All Records
		$stmt = $this->db->prepare("
			SELECT
				ID as id,
				name, 
				strftime('%Y-%m-%dT%H:%M:%SZ', start_date) as startDate,
				strftime('%Y-%m-%dT%H:%M:%SZ', end_date) as endDate,
				duration,
				durationUnit,
				color,
				externalId,
				status
			FROM construction_stages
		");
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getSingle($id)
	{
		//Get Record By Id
		$stmt = $this->db->prepare("
			SELECT
				ID as id,
				name, 
				strftime('%Y-%m-%dT%H:%M:%SZ', start_date) as startDate,
				strftime('%Y-%m-%dT%H:%M:%SZ', end_date) as endDate,
				duration,
				durationUnit,
				color,
				externalId,
				status
			FROM construction_stages
			WHERE ID = :id
		");
		$stmt->execute(['id' => $id]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function post(ConstructionStagesCreate $data)
	{
		//insert new record
		$stmt = $this->db->prepare("
			INSERT INTO construction_stages
			    (name, start_date, end_date, duration, durationUnit, color, externalId, status)
			    VALUES (:name, :start_date, :end_date, :duration, :durationUnit, :color, :externalId, :status)
			");
		$stmt->execute([
			'name' => $data->name,
			'start_date' => $data->start_date,
			'end_date' => $data->end_date,
			'duration' => $data->duration,
			'durationUnit' => $data->durationUnit,
			'color' => $data->color,
			'externalId' => $data->externalId,
			'status' => $data->status,
		]);

		$id = $this->db->lastInsertId();
		$this->updateDuration($id);
		return $this->getSingle($id);
	}

	public function patch(ConstructionStagesUpdate $data)
	{
		//check the id is EXISTS
		$record = $this->getSingle($data->id);
		if(!empty($record) && $this->isValidStatus($data))
		{
			$set = $execute = [];
			foreach($data as $key => $val)
			{
				//prepare set value for update entity
				if($val != '' && $key != 'id') 
				{
					$set[] = "{$key} = :{$key}";
					$execute[$key] = $val; 
				}
			}
			
			if(!empty($set))
			{
				//update entity
				$execute['id'] = $data->id;
				$set = implode(',',$set);
				$stmt = $this->db->prepare("UPDATE construction_stages SET {$set} WHERE id = :id");
				$stmt->execute($execute);

				$this->updateDuration($data->id);
			}

			//fetch updated record
			$record = $this->getSingle($data->id);
		}
		return $record;
	}

	private function isValidStatus($data)
	{
		//if status isset and is not valid, return error
		if(isset($data->status) && !in_array($data->status,['NEW','PLANNED','DELETED'])) 
		{
			return false;
		}

		return true;
	}

	public function delete($id)
	{
		//check the id is EXISTS
		$record = $this->getSingle($id);
		if(!empty($record))
		{
			$stmt = $this->db->prepare("UPDATE construction_stages SET status = 'DELETED' WHERE id = :id");
			$stmt->execute(['id' => $id]);
			$record = $this->getSingle($id);
		}
		return $record;
	}


	private function updateDuration($id)
	{
		//check the id is EXISTS
		$record = $this->getSingle($id);
		if(!empty($record))
		{
			$duration = "NULL";
			$start_date = $record[0]['startDate'];
			$end_date = $record[0]['endDate'];
			$unit = (!empty($record[0]['durationUnit'])) ? $record[0]['durationUnit'] : 'DAYS';

			if($end_date !== null && strtotime($end_date) > strtotime($start_date))
			{
				//Get diff date
				$start_date = new DateTime($start_date);
				$end_date = new DateTime($end_date);
				$interval = $start_date->diff($end_date);

				//Calculate duration based on unit
				$hours = $interval->h;
				switch($unit)
				{
					case 'HOURS':
						$duration = $hours + ($interval->days*24);
						break;
					case 'DAYS':
						$duration = $interval->days + ($hours/24);
						break;
					case 'WEEKS':
						$duration = ($interval->days + ($hours/24))/7;
						break;
				}


				$duration = round($duration,2);
			} 

			//Update duration 
			$stmt = $this->db->prepare("UPDATE construction_stages SET duration = '{$duration}',  durationUnit = '{$unit}'  WHERE id = :id");
			$stmt->execute(['id' => $id]);
		}
	}
}