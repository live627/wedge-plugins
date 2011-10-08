<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

// The entrance point for all 'Manage Awards' actions.
function Awards()
{
	global $context, $txt, $scripturl, $sourcedir;

	$subActions = array(
		'main' => array('AwardsMain'),
		'assign' => array('AwardsAssign'),
		'modify' => array('AwardsModify'),
		'delete' => array('AwardsDelete'),
		'edit' => array('AwardsModify'),
		'settings' => array('AwardsSettings'),
		'viewassigned' => array('AwardsViewAssigned'),
		'categories' => array('ListCategories'),
		'editcategory' => array('EditCategory'),
		'deletecategory' => array('DeleteCategory'),
		'viewcategory' => array('ViewCategory'),
	);

	// Default to sub action 'index' or 'settings' depending on permissions.
	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';

	// Do the permission check, you might not be allowed here.
	isAllowedTo('manage_awards');

	// Language and template stuff, the usual.
	loadPluginLanguage('live627:awards', 'Awards');
	loadPluginLanguage('live627:awards', 'ManageAwards');
	loadPluginTemplate('live627:awards', 'ManageAwards');

	// Setup the admin tabs.
	$context[$context['admin_menu_name']]['tab_data'] = array(
		'title' => $txt['awards'],
		'help' => 'awards',
		'description' => $txt['awards_description'],
	);

	$context['tabindex'] = 1;
	loadBlock($_REQUEST['sa']);

	// Call the right function.
	$subActions[$_REQUEST['sa']][0]();
}

function AwardsMain()
{
	global $context, $scripturl, $modSettings, $txt;

	// Count the number of items in the database for create index
	$request = wesql::query('
		SELECT COUNT(*)
		FROM {db_prefix}awards'
	);

	list ($countAwards) = wesql::fetch_row($request);
	wesql::free_result($request);

	// Calculate the number of results to pull up.
	$maxAwards = 20;

	// Construct the page index
	$context['page_index'] = constructPageIndex($scripturl . '?action=admin;area=awards', $_REQUEST['start'], $countAwards, $maxAwards);
	$context['start'] = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;

	// Select the awards and their categories.
	$request = wesql::query('
		SELECT a.*, c.name
		FROM {db_prefix}awards AS a
			LEFT JOIN {db_prefix}awards_categories AS c ON (c.id_category = a.id_category)
		ORDER BY c.name DESC, a.name DESC
		LIMIT {int:start}, {int:end}',
		array(
			'start' => $context['start'],
			'end' => $maxAwards
		)
	);

	$context['categories'] = array();

	// Loop through the results.
	while ($row = wesql::fetch_assoc($request)){
		if(!isset($context['categories'][$row['id_category']]['name']))
			$context['categories'][$row['id_category']] = array(
				'name' => $row['name'],
				'view' => $scripturl . '?action=admin;area=awards;sa=viewcategory;id=' . $row['id_category'],
				'edit' => $scripturl . '?action=admin;area=awards;sa=editcategory;id=' . $row['id_category'],
				'delete' => $scripturl . '?action=admin;area=awards;sa=deletecategory;id=' . $row['id_category'] . ';' . $context['session_var'] . '=' . $context['session_id'],
				'awards' => array(),
			);

		$context['categories'][$row['id_category']]['awards'][] = array(
			'id' => $row['id_award'],
			'name' => $row['name'],
			'description' => $row['description'],
			'time' => timeformat($row['time_added']),
			'image' => $row['image'],
			'miniimage' => $row['miniimage'],
			'img' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['image'],
			'small' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['miniimage'],
			'edit' => $scripturl . '?action=admin;area=awards;sa=modify;id=' . $row['id_award'],
			'delete' => $scripturl . '?action=admin;area=awards;sa=delete;id=' . $row['id_award'] . ';' . $context['session_var'] . '=' . $context['session_id'],
			'assign' => $scripturl . '?action=admin;area=awards;sa=assign;step=1;id=' . $row['id_award'],
			'view_assigned' => $scripturl . '?action=admin;area=awards;sa=viewassigned;id=' . $row['id_award'],
		);
	}

	wesql::free_result($request);

	// Setup the title and template.
	$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_main'];
	$context['sub_template'] = 'main';

}

function AwardsModify()
{
	global $context, $scripturl, $txt, $modSettings, $settings, $boarddir;

	// Check if they are saving the changes
	if (isset($_POST['award_save'])){
		checkSession('post');

		// Check if any of the values where left empty
		if (empty($_POST['name']))
			fatal_lang_error('awards_error_empty_badge_name');
		if (empty($_FILES['awardFile']['name']) && $_POST['id'] == 0)
			fatal_lang_error('awards_error_no_file');

		$id = (int) $_POST['id'];

		// Clean the values
		$name = strtr($smcFunc['htmlspecialchars']($_POST['name'], ENT_QUOTES), array("\r" => '', "\n" => '', "\t" => ''));
		$description = strtr($smcFunc['htmlspecialchars']($_POST['description'], ENT_QUOTES), array("\r" => '', "\n" => '', "\t" => ''));
		$category = (int) $_REQUEST['id_category'];
		$time_added = time();

		// Now to insert the data for this new award.
		if ($id < 1){
			wesql::insert('replace',
				'{db_prefix}awards',
				array('name' => 'string', 'description' => 'string', 'time_added' => 'int', 'id_category' => 'int'),
				array($name, $description, $time_added, $category),
				array('id_award')
			);

			// Get the id_award
			$id_award = wesql::insert_id('{db_prefix}awards', 'id_award');

			// Now upload the file
			AwardsUpload($id_award);
		} else {
			// Edit the award
			$editAward = wesql::query('
				UPDATE {db_prefix}awards
				SET
					name = {string:awardname},
					description = {string:gamename},
					id_category = {int:category}
				WHERE id_award = {int:id_award}',
				array(
					'awardname' => $_REQUEST['name'],
					'gamename' => $_POST['description'],
					'id_award' => $id,
					'category' => $category
				)
			);

			// Are we uploading a new image for this award?
			if (isset($_FILES['awardFile']) && $_FILES['awardFile']['error'] === 0 && $editAward === true){
				// Lets make sure that we delete the file that we are supposed to and not something harmful
				$request = wesql::query('
					SELECT image, miniimage
					FROM {db_prefix}awards
					WHERE id_award = {int:id}',
					array(
						'id' => $id
					)
				);

				list ($image, $miniimage) = wesql::fetch_row($request);
				wesql::free_result($request);

				// Delete the file first.
				if (file_exists($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $image))
					@unlink($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $image);
				if (file_exists($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $miniimage))
					@unlink($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $miniimage);

				// Now add the new one.
				AwardsUpload($id_award);
			}
		}

		redirectexit('action=admin;area=awards;sa=modify;saved=1');
	}

	$request = wesql::query('
		SELECT *
		FROM {db_prefix}awards_categories
		ORDER BY name ASC',
		array()
	);

	while ($row = wesql::fetch_assoc($request))
		$context['categories'][] = array(
			'id' => $row['id_category'],
			'name' => $row['name'],
		);

	wesql::free_result($request);

	// Load the data for editing
	if (isset($_REQUEST['id'])){
		$id = (int) $_REQUEST['id'];

		// Check if awards is clean.
		if (empty($id) || $id <= 0)
			fatal_lang_error('awards_error_no_id');

		// Load single award info for editing.
		$request = wesql::query('
			SELECT *
			FROM {db_prefix}awards
			WHERE id_award = {int:id}
			LIMIT 1',
			array(
				'id' => $id
			)
		);
		$row = wesql::fetch_assoc($request);

		// Check if that award exists
		if (count($row['id_award']) != 1)
			fatal_lang_error('awards_error_no_award');

		$context['editing'] = true;
		$context['award'] = array(
			'id' => $row['id_award'],
			'name' => $row['name'],
			'description' => $row['description'],
			'category' => $row['id_category'],
			'time' => timeformat($row['time_added']),
			'image' => $row['image'],
			'miniimage' => $row['miniimage'],
			'img' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['image'],
			'small' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['miniimage'],
		);

		// Free results
		wesql::free_result($request);

		// Set the title
		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_edit_award'];
	} else {
		// Setup place holders.
		$context['editing'] = false;
		$context['award'] = array(
			'id' => 0,
			'name' => '',
			'description' => '',
			'category' => 1,
		);

		// Set the title
		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_manage_awards'];
	}

	$context['sub_template'] = 'modify';
}

// !!! TODO: Tie this in with media
function AwardsUpload($id_award)
{
	global $context, $modSettings, $boarddir, $txt;

	// Check if $_FILE was set.
	if (empty($_FILES['awardFile']) || !isset($id_award))
		fatal_lang_error('awards_error_no_file');

	// Lets try to CHMOD the awards dir.
	if (!is_writable($boarddir . '/' . $modSettings['awards_dir']))
		@chmod($boarddir . '/' . $modSettings['awards_dir'], 0755);

	// Define $award
	$award = $_FILES['awardFile'];

	// Check if file was uploaded.
	if ($award['error'] === 1 || $award['error'] === 2)
		fatal_lang_error('awards_error_upload_size');
	elseif ($award['error'] !== 0)
		fatal_lang_error('awards_error_upload_failed');

	// Check the extensions
	$goodExtensions = array('jpg', 'jpeg', 'gif', 'png');
	if (!in_array(strtolower(substr(strrchr($award['name'], '.'), 1)), $goodExtensions))
		fatal_lang_error('awards_error_wrong_extension');
	else {
		$newName = $boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $id_award . '.' . strtolower(substr(strrchr($award['name'], '.'), 1));
		$miniName = $boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $id_award . '-mini.' . strtolower(substr(strrchr($award['name'], '.'), 1));
	}

	// Now move the file to the right directory
	move_uploaded_file($award['tmp_name'], $newName);

	// Try to CHMOD the uploaded file
	@chmod($newName, 0755);

	if($_FILES['awardFileMini']['error'] != 4){
		// Define $award
		$award = $_FILES['awardFileMini'];

		// Check if file was uploaded.
		if ($award['error'] === 1 || $award['error'] === 2)
			fatal_lang_error('awards_error_upload_size');
		elseif ($award['error'] !== 0)
			fatal_lang_error('awards_error_upload_failed');

		// Check the extensions
		$goodExtensions = array('jpg', 'jpeg', 'gif', 'png');
		if (!in_array(strtolower(substr(strrchr($award['name'], '.'), 1)), $goodExtensions))
			fatal_lang_error('awards_error_wrong_extension');
		else
			$miniName = $boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $id_award . '-mini.' . strtolower(substr(strrchr($award['name'], '.'), 1));

		// Now move the file to the right directory
		move_uploaded_file($award['tmp_name'], $miniName);

		// Try to CHMOD the uploaded file
		@chmod($miniName, 0755);
	} else
		copy($newName, $miniName);

	wesql::query('
		UPDATE {db_prefix}awards
		SET
			image = {string:file},
			miniimage = {string:mini}
		WHERE id_award = {int:id}',
		array(
			'file' => basename($newName),
			'mini' => basename($miniName),
			'id' => $id_award
		)
	);
}

function AwardsDelete()
{
	global $$boarddir, $modSettings;

	// Check the session
	checkSession('get');

	$id = (int) $_GET['id'];

	// Select the file name to delete
	$request = wesql::query('
		SELECT image, miniimage
		FROM {db_prefix}awards
		WHERE id_award = {int:award}',
		array(
			'award' => $id
		)
	);
	list ($image, $miniimage) = wesql::fetch_row($request);
	wesql::free_result($request);

	// Now delete the award from the server
	@unlink($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $image);
	@unlink($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $miniimage);

	// Now delete the entry from the database.
	wesql::query('
		DELETE FROM {db_prefix}awards
		WHERE id_award = {int:award}
		LIMIT 1',
		array(
			'award' => $id
		)
	);

	// Ok since this award doesn't exists any more lets remove it from the member
	wesql::query('
		DELETE FROM {db_prefix}awards_members
		WHERE id_award = {int:award}',
		array(
			'award' => $id
		)
	);

	// Redirect the exit
	redirectexit('action=admin;area=awards');
}

function AwardsAssign()
{
	global $context, $sourcedir, $modSettings, $txt;

	// First step, select the member and awards
	if (!isset($_REQUEST['step']) || $_REQUEST['step'] == 1){
		// Select the awards for the drop down.
		$request = wesql::query('
			SELECT id_award, name, image
			FROM {db_prefix}awards
			ORDER BY name ASC',
			array()
		);

		$context['awards'] = array();

		while ($row = wesql::fetch_assoc($request)){
			$context['awards'][$row['id_award']] = array(
				'name' => $row['name'],
				'image' => $row['image'],
			);
		}

		wesql::free_result($request);

		// Set the current step.
		$context['step'] = 1;

		// Set the title
		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_select_badge'];
	} else if(isset($_REQUEST['step']) && $_REQUEST['step'] == 2){

		// Make sure that they picked an award and members to assign it to...
		foreach($_POST['recipient_to'] as $recipient)
			if($recipient != '{MEMBER_ID}')
				$members[] = (int) $recipient;

		if(empty($members) || empty($_POST['award']))
			fatal_lang_error('awards_error_no_members');

		// Set a valid date, award.
		$date_received = (int) $_POST['year'] . '-' . (int) $_POST['month'] . '-' . (int) $_POST['day'];
		$_POST['award'] = (int) $_POST['award'];

		$values = array();

		// Prepare the values.
		foreach ($members as $member)
			$values[] = array($_POST['award'], $member, $date_received);

		// Insert the data
		wesql::insert('ignore',
			'{db_prefix}awards_members',
			array('id_award' => 'int', 'id_member' => 'int', 'date_received' => 'string'),
			$values,
			array('id_member', 'id_award')
		);

		// Redirect to show the members with this award.
		redirectexit('action=admin;area=awards;sa=viewassigned;id=' . $_POST['award']);
	}

	$context['sub_template'] = 'assign';
}

function AwardsViewAssigned()
{
	global $context, $scripturl, $modSettings, $txt;

	$id = (int) $_REQUEST['id'];

	// An award must be selected.
	if (empty($id) || $id <= 0)
		fatal_lang_error('awards_error_no_award');

	// Remove the badge from these members
	if (isset($_POST['unassign'])){
		checkSession('post');

		// Delete the rows from the database for the members selected.
		wesql::query('
			DELETE FROM {db_prefix}awards_members
			WHERE id_award = {int:id}
				AND id_member IN (' . implode(', ', $_POST['member']) . ')',
			array(
				'id' => $id
			)
		);

		// Redirect to the badges
		redirectexit('action=admin;area=awards;sa=viewassigned;id=' . $id);
	}

	// Load the award info
	$request = wesql::query('
		SELECT *
		FROM {db_prefix}awards
		WHERE id_award = {int:id}
		LIMIT 1',
		array(
			'id' => $id
		)
	);

	// Check if ths award actually exists
	if (wesql::num_rows($request) < 1)
		fatal_lang_error('awards_error_no_award');

	// Fetch the award info just once
	while ($row = wesql::fetch_assoc($request))
		$context['award'] = array(
			'id' => $row['id_award'],
			'name' => $row['name'],
			'description' => $row['description'],
			'image' => $row['image'],
			'img' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['image'],
			'members' => array(),
		);

	wesql::free_result($request);

	// Now load the members' info
	$request = wesql::query('
		SELECT
			m.member_name, m.real_name, a.id_member, a.date_received
		FROM {db_prefix}awards_members AS a
			LEFT JOIN {db_prefix}members AS m ON (m.id_member = a.id_member)
		WHERE a.id_award = {int:id}',
		array(
			'id' => $id
		)
	);

	while ($row = wesql::fetch_assoc($request))
		$context['award']['members'][] = array(
			'id' => $row['id_member'],
			'name' => $row['member_name'],
			'date' => $row['date_received'],
			'link' => '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>',
		);

	wesql::free_result($request);

	// Set the context values
	$context['page_title'] = $txt['awards_title'] . ' - ' . $context['award']['name'];
	$context['sub_template'] = 'view_assigned';
}

function AwardsSettings()
{
	global $context, $modSettings, $txt, $boarddir;

	$context['sub_template'] = 'settings';
	$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_settings'];

	// Save the settings
	if (isset($_POST['save_settings'])){
		// Check the session
		checkSession('post');

		// Strip any slashes from the awards dir
		$_POST['awards_dir'] = str_replace(array('\\', '/'), '', $_POST['awards_dir']);

		// Try to create a new dir if it doesn't exists.
		if (!is_dir($boarddir . '/' . $_POST['awards_dir']) && trim($_POST['awards_dir']) != '')
			if (!mkdir($boarddir . '/' . $_POST['awards_dir'], 0755))
				$context['awards_mkdir_fail'] = true;

		// Now save
		updateSettings(array(
			'awards_dir' => $smcFunc['htmlspecialchars']($_POST['awards_dir'], ENT_QUOTES),
			'awards_favorites' => isset($_POST['awards_favorites']) ? 1 : 0,
			'awards_in_post' => isset($_POST['awards_in_post']) ? (int) $_POST['awards_in_post'] : 4,
		));
	}
}

function EditCategory()
{
	global $context, $scripturl, $txt, $modSettings, $settings;

	if(isset($_REQUEST['id'])){
		$id = (int) $_REQUEST['id'];

		// Needs to be an int!
		if (empty($id) || $id <= 0)
			fatal_lang_error('awards_error_no_id_category');

		// Load single award info for editing.
		$request = wesql::query('
			SELECT *
			FROM {db_prefix}awards_categories
			WHERE id_category = {int:id}
			LIMIT 1',
			array(
				'id' => $id
			)
		);
		$row = wesql::fetch_assoc($request);

		// Check if that award exists
		if (count($row['id_category']) != 1)
			fatal_lang_error('awards_error_no_category');

		$context['editing'] = true;
		$context['category'] = array(
			'id' => $row['id_category'],
			'name' => $row['name'],
		);

		wesql::free_result($request);

		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_edit_category'];
	} else {
		// Setup place holders.
		$context['editing'] = false;
		$context['category'] = array(
			'id' => 0,
			'name' => '',
		);

		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_manage_categories'];
	}

	// Check if they are saving the changes
	if (isset($_POST['save'])){
		checkSession('post');

		$name = trim(strtr(westr::htmlspecialchars($_REQUEST['name'], ENT_QUOTES), array("\r" => '', "\n" => '', "\t" => '')));

		// Check if any of the values were left empty
		if (empty($name))
			fatal_lang_error('awards_error_empty_name');

		// Now to insert the data for this new award.
		if ($_POST['id_category'] == 0){
			wesql::insert('replace',
				'{db_prefix}awards_categories',
				array('name' => 'string'),
				array($name),
				array('id_category')
			);
		} else {
			// Set $id_award
			$id_category = (int) $_POST['id_category'];

			// Edit the award
			$request = wesql::query('
				UPDATE {db_prefix}awards_categories
				SET name = {string:category}
				WHERE id_category = {int:id}',
				array(
					'category' => $name,
					'id' => $id_category
				)
			);
		}

		// Redirect back to the mod.
		redirectexit('action=admin;area=awards;sa=editcategory;saved=1');
	}

	loadBlock('edit_category');
}

function ListCategories()
{
	global $txt, $scripturl, $context, $settings, $modSettings;

	// Deleting?
	if (isset($_POST['delete'], $_POST['remove']))
	{
		checkSession();
		removeCategories($_POST['remove']);
		redirectexit('action=admin;area=awards;sa=categories');
	}

	// New field?
	if (isset($_POST['new']))
		redirectexit('action=admin;area=awards;sa=editcategory');

	$listOptions = array(
		'id' => 'categories',
		'base_href' => $scripturl . '?action=action=admin;area=awards;sa=categories',
		'default_sort_col' => 'name',
		'no_items_label' => $txt['awards_error_no_categories'],
		'items_per_page' => 25,
		'get_items' => array(
			'function' => 'list_getCategories',
		),
		'get_count' => array(
			'function' => 'list_getNumCategories',
		),
		'columns' => array(
			'name' => array(
				'header' => array(
					'value' => $txt['awards_category_name'],
					'style' => 'text-align: left;',
				),
				'data' => array(
					'db_htmlsafe' => 'name',
					'style' => 'width: 40%;',
				),
				'sort' => array(
					'default' => 'name',
					'reverse' => 'name DESC',
				),
			),
			'awards' => array(
				'header' => array(
					'value' => $txt['awards_num_in_category'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $scripturl, $txt;

						return sprintf(\'<a href="%1$s?action=admin;area=awards;sa=viewcategory;in=%2$d">%3$s</a> (%4$d)\', $scripturl, $rowData[\'id_category\'], $txt[\'showAwards\'], $rowData[\'awards\']);
					'),
					'style' => 'width: 10%; text-align: center;',
				),
			),
			'modify' => array(
				'header' => array(
					'value' => $txt['modify'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<a href="' . $scripturl . '?action=admin;area=awards;sa=postfieldedit;fid=%1$s">' . $txt['modify'] . '</a>',
						'params' => array(
							'id_category' => false,
						),
					),
					'style' => 'width: 10%; text-align: center;',
				),
			),
			'remove' => array(
				'header' => array(
					'value' => $txt['remove'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;
						return sprintf(\'<span id="remove_%1$s" class="color_no">%2$s</span>&nbsp;<input type="checkbox" name="remove[%1$s]" id="remove_%1$s" value="%1$s">\', $rowData[\'id_category\'], $txt[\'no\']);
					'),
					'style' => 'width: 10%; text-align: center;',
				),
			),
		),
		'form' => array(
			'href' => $scripturl . '?action=admin;area=awards;sa=categories',
			'name' => 'customProfileFields',
		),
		'additional_rows' => array(
			array(
				'position' => 'below_table_data',
				'value' => '<input type="submit" name="save" value="' . $txt['save'] . '" class="submit">&nbsp;&nbsp;<input type="submit" name="delete" value="' . $txt['delete'] . '" onclick="return confirm(' . JavaScriptEscape($txt['awards_confirm_delete_category']) . ');" class="delete">&nbsp;&nbsp;<input type="submit" name="new" value="' . $txt['awards_add_category'] . '" class="new">',
				'style' => 'text-align: right;',
			),
		),
	);
	loadSource('Subs-List');
	createList($listOptions);
	loadBlock('show_list');
	$context['default_list'] = 'categories';
	$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_list_categories'];
	$context['header'] .= '
	<style>
		.color_yes
		{
			color: green;
		}
		.color_no
		{
			color: red;
		}
	</style>';
}

function list_getCategories($start, $items_per_page, $sort)
{
	$list = array();
	$request = wesql::query('
		SELECT id_category, name
		FROM {db_prefix}awards_categories
		ORDER BY {raw:sort}
		LIMIT {int:start}, {int:items_per_page}',
		array(
			'sort' => $sort,
			'start' => $start,
			'items_per_page' => $items_per_page,
		)
	);
	while ($row = wesql::fetch_assoc($request))
		$list[$row['id_category']] = $row;
	wesql::free_result($request);

	// Select the categories.
	$request = wesql::query('
		SELECT id_category, COUNT(*) AS num_awards
		FROM {db_prefix}awards
		GROUP BY id_category'
	);
	while ($row = wesql::fetch_assoc($request))
		$list[$row['id_category']]['awards'] = $row['num_awards'];

	wesql::free_result($request);

	return $list;
}

function list_getNumCategories()
{
	$request = wesql::query('
		SELECT COUNT(*)
		FROM {db_prefix}awards_categories');

	list ($numCategories) = wesql::fetch_row($request);
	wesql::free_result($request);

	return $numCategories;
}

function removeCategories($categories)
{
	if (in_array(1, $categories))
		fatal_lang_error('awards_error_delete_main_category');

	// Will any awards go astray after we delete their category?
	wesql::query('
		UPDATE {db_prefix}awards
		SET id_category = 1
		WHERE id_category IN ({array_int:categories})',
		array(
			'categories' => $categories,
		)
	);

	// Now delete the entry from the database.
	wesql::query('
		DELETE FROM {db_prefix}awards_categories
		WHERE id_category IN ({array_int:categories})',
		array(
			'categories' => $categories,
		)
	);
}

function ViewCategory()
{
	global $context, $scripturl, $modSettings, $txt;

	// Clean up!
	$id_category = (int) $_REQUEST['id'];
	$max_awards = 15;
	$context['start'] = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;

	// Count the number of items in the database for create index
	$request = wesql::query('
		SELECT COUNT(*)
		FROM {db_prefix}awards
		WHERE id_category = {int:id}',
		array(
			'id' => $id_category
		)
	);

	list ($count_awards) = wesql::fetch_row($request);

	wesql::free_result($request);

	// And find the category name
	$request = wesql::query('
		SELECT name
		FROM {db_prefix}awards_categories
		WHERE id_category = {int:id}
		LIMIT 1',
		array(
			'id' => $id_category
		)
	);

	list ($context['category']) = wesql::fetch_row($request);

	wesql::free_result($request);

	// Grab all qualifying awards
	$request = wesql::query('
		SELECT *
		FROM {db_prefix}awards
		WHERE id_category = {int:id}
		ORDER BY name DESC
		LIMIT {int:start}, {int:end}',
		array(
			'id' => $id_category,
			'start' => $context['start'],
			'end' => $max_awards,
		)
	);

	while ($row = wesql::fetch_assoc($request))
		$context['awards'][] = array(
			'id' => $row['id_award'],
			'name' => $row['name'],
			'description' => $row['description'],
			'img' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['image'],
			'small' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['miniimage'],
			'edit' => $scripturl . '?action=admin;area=awards;sa=modify;id=' . $row['id_award'] . ';' . $context['session_var'] . '=' . $context['session_id'],
		);

	wesql::free_result($request);

	$context['page_index'] = constructPageIndex($scripturl . '?action=admin;area=awards;sa=viewcategory', $context['start'], $count_awards, $max_awards);
	$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_viewing_category'];
	$context['sub_template'] = 'view_category';
}

?>