<?php

namespace WebSocket\Models;


class User extends Model {

	protected $fieldNames = ['id', 'server_id'];
	protected $tableName = "users";

	public $server_id;
}
