<?php

namespace WebSocket\Models;

abstract class Model {

	protected $fieldNames = [];
	protected $tableName = '';

	protected $db;

	public $id;

	public function __construct(&$db, $data){
		$this->db = $db;

		if(!is_array($data)){
			//Pull directly
			$this->id = $data;
			$this->reload();
		} else {
			//Pull/update
			if(isset($data['id'])) {
				$this->id = $data['id'];
				$this->reload();
			}
			foreach($data as $key=>$value){
				if($key !== 'id' && in_array($key, $this->fieldNames)){
					$this->{$key} = $value;
				}
			}
			$this->save();
		}
		//if data is int, assume "id": do lookup, pull data
		//if data is array, make new row for entry if doesn't contain "id", if it does, update that row
	}

	public static function find_by_array($array){
		//return self with fields populated by array search
	}

	public static function find_all_by_array($array){
		//return array of self with fields populated by array search
	}

	public function reload(){
		$statement = $this->db->prepare("select `" . implode('`, `', $this->fieldNames) . "` from " . $this->tableName . " where `id` = :id");
		$statement->execute(array(':id' => (int)$this->id));
		if($row = $statement->fetch(\PDO::FETCH_ASSOC)){
			foreach($this->fieldnames as $field){
				$this->{$field} = $row[$field];
			}
		} else {
			throw new \Exception('Row not found');
		}
	}
	public function save(){
		$set = [];
		$bindings = [];
		foreach($this->fieldNames as $type => $fieldName){
			if($fieldName != 'id') {
				$set[] = "`$fieldName`= :$fieldName";
			}
			$bindings[":" . $fieldName] = $this->{$fieldName};
		}

		if($this->id){
			$statement = $this->db->prepare("UPDATE " . $this->tableName . " SET " . implode(',', $set) . " where `id` = :id");
		} else {
			$statement = $this->db->prepare("INSERT INTO " . $this->tableName . " (`" . implode('`, `', $this->fieldNames) . "`) VALUES (:" . implode(',:', $this->fieldNames) . ")");
		}
		$statement->execute($bindings);
		if(!$this->id){
			$this->id = $this->db->lastInsertId();
		}
	}

	public function delete(){
		$statement = $this->db->prepare("DELETE FROM " . $this->tableName . " where `id` = :id");
		$statement->execute(array(':id' => (int)$this->id));
	}
}
