<?php

namespace WebSocket\Models;


class Server extends Model {

	protected $fieldNames = ['id', 'name'];
	protected $tableName = "servers";

	public $name;
}
