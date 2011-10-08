<?php

wesql::insert('replace',
	'{db_prefix}awards_categories',
	array('category_name' => 'string'),
	array('Uncategorized'),
	array('id_category')
);

?>