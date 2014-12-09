<?php

class Subscription extends ActiveRecord\Model {
	static $has_one = array(
		array('channel'),
		array('user')
	);
}
