<?php

if (!defined('WEDGE_PLUGIN'))
	exit('<b>Error:</b> Cannot be run outside of Wedge\'s plugin manager!.');

wedbPackages::remove_index('{db_prefix}topics', 'invited');
wedbPackages::remove_column('{db_prefix}topics', 'invited');

?>