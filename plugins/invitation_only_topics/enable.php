<?php

if (!defined('WEDGE_PLUGIN'))
	exit('<b>Error:</b> Cannot be run outside of Wedge\'s plugin manager!.');

$column = array(
	'name' => 'invited',
	'type' => 'tinyint',
	'size' => 3,
	'unsigned' => true,
);

wedbPackages::add_column('{db_prefix}topics', $column);

$index = array(
	'columns' => array(
		'invited',
	),
);

wedbPackages::add_index('{db_prefix}topics', $index);

?>