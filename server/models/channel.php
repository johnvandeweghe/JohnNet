<?php

class Channel extends ActiveRecord\Model {
	static $belongs_to = array(
		array('application')
	);
}
