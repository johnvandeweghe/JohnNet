<?php

namespace WebSocket\Models;


class Application extends Model {

	protected $fieldNames = '["id", "secret"]';
	protected $tableName = "applications";

	public $secret;
}
