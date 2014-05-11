<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function Awards()
{
	global $context, $txt;

	// Format: 'sub-action' => array('function', 'permission')
	$sub_actions = array(
		'index' => array('ListAwards'),
		'assign' => array('AwardsAssign'),
		'modify' => array('EditAward'),
		'edit' => array('EditAward'),
		'settings' => array('AwardsSettings'),
		'viewassigned' => array('AwardsViewAssigned'),
		'categories' => array('ListCategories'),
		'editcategory' => array('EditCategory'),
		'upload' => array('UploadAwardImage'),
	);

	// Default to sub action 'index'
	if (!isset($_GET['sa']) || !isset($sub_actions[$_GET['sa']]))
		$_GET['sa'] = 'index';

	// Do the permission check, you might not be allowed here.
	isAllowedTo('manage_awards');

	// Language and template stuff, the usual.
	loadPluginLanguage('live627:awards', 'Awards');
	loadPluginLanguage('live627:awards', 'ManageAwards');
	loadPluginTemplate('live627:awards', 'ManageAwards');
	wetem::load($_GET['sa']);

	// Load up all the tabs...
	$context[$context['admin_menu_name']]['tab_data'] = array(
		'title' => $txt['awards'],
		'help' => 'awards',
		'description' => $txt['awards_description'],
	);

	// Calls a function based on the sub-action
	$sub_actions[$_GET['sa']][0]();
}

function ListAwards()
{
	global $txt, $context;

	// Deleting?
	if (isset($_POST['delete'], $_POST['remove']))
	{
		checkSession();
		deleteAwards($_POST['remove']);
		redirectexit('action=admin;area=awards');
	}

	// Changing the status?
	if (isset($_POST['save']))
	{
		checkSession();
		foreach (total_getAwards() as $award)
		{
			$active = !empty($_POST['active'][$award['id_award']]) ? 'yes' : 'no';
			if ($active != $award['active'])
				wesql::query('
					UPDATE {db_prefix}awards
					SET active = {string:active}
					WHERE id_award = {int:award}',
					array(
						'active' => $active,
						'award' => $award['id_award'],
					)
				);

			$can_search = !empty($_POST['can_search'][$award['id_award']]) ? 'yes' : 'no';
			if ($can_search != $award['can_search'])
				wesql::query('
					UPDATE {db_prefix}awards
					SET can_search = {string:can_search}
					WHERE id_award = {int:award}',
					array(
						'can_search' => $can_search,
						'award' => $award['id_award'],
					)
				);
		}
		redirectexit('action=admin;area=awards');
	}

	// New award?
	if (isset($_POST['new']))
		redirectexit('action=admin;area=awards;sa=edit');

	$listOptions = array(
		'id' => 'awards',
		'base_href' => '<URL>?action=action=admin;area=awards',
		'default_sort_col' => 'name',
		'no_items_label' => $txt['awards_error_no_badges'],
		'items_per_page' => 25,
		'get_items' => array(
			'function' => 'list_getAwards',
		),
		'get_count' => array(
			'function' => 'list_getNumAwards',
		),
		'columns' => array(
			'name' => array(
				'header' => array(
					'value' => $txt['awards_badge_name'],
					'style' => 'text-align: left;',
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<div class="floatleft award_icon_%1$d">%2$s<dfn>%3$s</dfn></div>',
						'params' => array(
							'id_award' => false,
							'name' => false,
							'description' => false,
						),
					),
					'style' => 'width: 40%;',
				),
				'sort' => array(
					'default' => 'name',
					'reverse' => 'name DESC',
				),
			),
			'active' => array(
				'header' => array(
					'value' => $txt['awards_active'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;
						$isChecked = $rowData[\'active\'] == \'no\' ? \'\' : \' checked\';
						return sprintf(\'<span id="active_%1$s" class="color_%4$s">%3$s</span>&nbsp;<input type="checkbox" name="active[%1$s]" id="active_%1$s" value="%1$s"%2$s>\', $rowData[\'id_award\'], $isChecked, $txt[$rowData[\'active\']], $rowData[\'active\']);
					'),
					'style' => 'width: 10%; text-align: center;',
				),
				'sort' => array(
					'default' => 'active DESC',
					'reverse' => 'active',
				),
			),
			'can_search' => array(
				'header' => array(
					'value' => $txt['awards_can_search'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;
						$isChecked = $rowData[\'can_search\'] == \'no\' ? \'\' : \' checked\';
						return sprintf(\'<span id="can_search_%1$s" class="color_%4$s">%3$s</span>&nbsp;<input type="checkbox" name="can_search[%1$s]" id="can_search_%1$s" value="%1$s"%2$s>\', $rowData[\'id_award\'], $isChecked, $txt[$rowData[\'can_search\']], $rowData[\'can_search\']);
					'),
					'style' => 'width: 10%; text-align: center;',
				),
				'sort' => array(
					'default' => 'can_search DESC',
					'reverse' => 'can_search',
				),
			),
			'modify' => array(
				'header' => array(
					'value' => $txt['modify'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<a href="<URL>?action=admin;area=awards;sa=edit;in=%1$s">' . $txt['modify'] . '</a>',
						'params' => array(
							'id_award' => false,
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
					'sprintf' => array(
						'format' => '<input type="checkbox" name="remove[%1$s]" id="remove_%1$s" value="%1$s">',
						'params' => array(
							'id_award' => false,
						),
					),
					'style' => 'width: 10%; text-align: center;',
				),
			),
		),
		'form' => array(
			'href' => '<URL>?action=admin;area=awards',
			'name' => 'customProfileawards',
		),
		'additional_rows' => array(
			array(
				'position' => 'below_table_data',
				'value' => '<input type="submit" name="save" value="' . $txt['save'] . '" class="submit">&nbsp;&nbsp;<input type="submit" name="delete" value="' . $txt['delete'] . '" onclick="return confirm(' . JavaScriptEscape($txt['awards_confirm_delete_award']) . ');" class="delete">&nbsp;&nbsp;<input type="submit" name="new" value="' . $txt['awards_add_award'] . '" class="new">',
				'style' => 'text-align: right;',
			),
		),
	);
	add_plugin_css_file('live627:awards', 'awards', true);
	loadSource('Subs-List');
	createList($listOptions);
	wetem::load('show_list');
	$context['default_list'] = 'awards';
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

function list_getAwards($start, $items_per_page, $sort)
{
	$list = array();
	$request = wesql::query('
		SELECT *
		FROM {db_prefix}awards
		ORDER BY {raw:sort}
		LIMIT {int:start}, {int:items_per_page}',
		array(
			'sort' => $sort,
			'start' => $start,
			'items_per_page' => $items_per_page,
		)
	);
	while ($row = wesql::fetch_assoc($request))
		$list[] = $row;
	wesql::free_result($request);

	return $list;
}

function total_getAwards()
{
	$list = array();
	$request = wesql::query('
		SELECT *
		FROM {db_prefix}awards');
	while ($row = wesql::fetch_assoc($request))
		$list[$row['id_award']] = $row;
	wesql::free_result($request);
	return $list;
}

function list_getNumAwards()
{
	$request = wesql::query('
		SELECT COUNT(*)
		FROM {db_prefix}awards');
	list ($count) = wesql::fetch_row($request);
	wesql::free_result($request);

	return $count;
}

function EditAward()
{
	global $context, $txt, $settings, $settings, $boarddir;

	// Sort out the context!
	$context['in'] = isset($_REQUEST['in']) ? (int) $_REQUEST['in'] : 0;

	// Check if they are saving the changes
	if (isset($_POST['save']))
	{
		checkSession();

		$name = strtr(westr::htmlspecialchars($_POST['name'], ENT_QUOTES), array("\r" => '', "\n" => '', "\t" => ''));
		$description = strtr(westr::htmlspecialchars($_POST['description'], ENT_QUOTES), array("\r" => '', "\n" => '', "\t" => ''));

		if (empty($_POST['name']))
			fatal_lang_error('awards_error_empty_badge_name');
		$category = (int) $_REQUEST['id_category'];

		if ($context['in'])
			$request = wesql::query('
				UPDATE {db_prefix}awards_categories
				SET name = {string:name},
					description = {string:description},
					id_media = {string:id_media},
					category = {string:category}
				WHERE id_award = {int:id_award}',
				array(
					'name' => $name,
					'description' => $description,
					'category' => $category,
					'id_media' => $_POST['id_media'],
					'id_award' => $context['in'],
				)
			);
		else
			wesql::insert('',
				'{db_prefix}awards',
				array('name' => 'string', 'description' => 'string', 'id_category' => 'int', 'id_media' => 'int'),
				array($name, $description, $category, $_POST['id_media']),
				array('id_award')
			);

		//
		clean_cache('css', 'awards');

		redirectexit('action=admin;area=awards');
	}

	$request = wesql::query('
		SELECT *
		FROM {db_prefix}awards_categories
		ORDER BY name ASC');

	while ($row = wesql::fetch_assoc($request))
		$context['categories'][] = array(
			'id' => $row['id_category'],
			'name' => $row['name'],
		);

	wesql::free_result($request);

	if ($context['in'])
	{
		$request = wesql::query('
			SELECT *
			FROM {db_prefix}awards
			WHERE id_award = {int:id}
			LIMIT 1',
			array(
				'id' => $context['in']
			)
		);
		$row = wesql::fetch_assoc($request);
		wesql::free_result($request);

		// Check if that award exists
		if (count($row['id_award']) != 1)
			fatal_lang_error('awards_error_no_award');

		$context['award'] = array(
			'id' => $row['id_award'],
			'name' => $row['name'],
			'description' => $row['description'],
			'category' => $row['id_category'],
			'media' => '<div class="information"><img src="<URL>?action=media;sa=media;in=' . $row['id_media'] . ';thumb" class="floatleft aea" /><div class="floatleft" style="margin-left: 2em;">' . $row['name'] . '<hr>[media id=' . $row['id_media'] . ']</div><br class="clear"><input type="hidden" name="id_media" value="' . $row['id_media'] . '" /></div>',
		);
		$context['page_title'] = $txt['awards_edit_award'];
	}
	else
	{
		$context['award'] = array(
			'id' => 0,
			'name' => '',
			'description' => '',
			'media' => '',
			'category' => 1,
		);
		$context['page_title'] = $txt['awards_add_award'];
	}

	add_css_file('media', true);
	add_js_file('scripts/sha1.js');
	add_plugin_js_file('live627:awards', 'uploader.js');
	loadSource('media/Aeva-Subs-Vital');
	loadLanguage('Media');
	loadPluginLanguage('Dragooon:MultiAttach', 'plugin');

	$max_php_size = (int) min(aeva_getPHPSize('upload_max_filesize'), aeva_getPHPSize('post_max_size'));

	add_js('
 	txt_drag_help = ', JavaScriptEscape($txt['multiattach_drag_help']), ';
	txt_drag_help_subtext = ', JavaScriptEscape($txt['multiattach_drag_help_subtext']), ';
	var max_php_size = ' . $max_php_size . ';
	var media_file_too_large_php = ' . JavaScriptEscape(sprintf($txt['media_file_too_large_php'], round($max_php_size / 1048576, 1))) . ';
	var pluginId = ' . $context['in'] . ';

	//
	$(\'input[type=file]#icon\').uploader({
		url: weUrl(\'action=admin;area=awards;sa=upload;in=\' + pluginId),
		fileDataValidator: function (name, size, type)
		{
			if (size > max_php_size)
			{
				alert(name + \': icon too large. Try to get it to be under \' + (max_php_size / 1024) + \' KB.\');
				return false;
			}
			return true;
		},
		ajaxEvents:
		{
			error: function (jqXHR, textStatus, errorThrown, hash)
			{
				var errData = $.parseJSON(jqXHR.responseText), ret = \'\';
				$.each(errData, function (index, value)
				{
					ret += \'<div class="error">\' + value + \'</div>\';
				});
				$(\'div#icon\' + hash).addClass(\'errorbox\').removeClass(\'information\').html(ret);
			},
			success: function (data, hash)
			{
				$(\'div#icon\' + hash).html(data);
				$(\'input[type=file]#icon\').remove();
			}
		},

		xhrUploadEvents:
		{
			progress: function (e, hash)
			{
				if (e.lengthComputable)
				{
					var percentComplete = e.loaded / e.total;
					$(\'progress#icon\' + hash).attr({value: e.loaded, max: e.total});
				}
				else
				{
					// Unable to compute progress information since the total size is unknown
				}
			},

			loadstart: function (e, hash)
			{
				$(\'#icon_container\').children(\'div:not([id^=dropzone])\').remove().end().append(\'<div id="icon\' + hash + \'" class="information center"><progress id="icon\' + hash + \'"/></div>\');
			},

			abort: function (e, hash)
			{
				$(\'progress\').remove();
			},

			cancel: function (e, hash)
			{
				$(\'progress\').remove();
			}
		}
	});');
}

function UploadAwardImage()
{
	global $amSettings, $scripturl, $txt;

	// Sort out the context!
	$context['in'] = isset($_GET['in']) ? (int) $_GET['in'] : 0;

	header('Content-type: text/plain; charset=utf-8');
	loadLanguage('Media');
	loadSource('media/Subs-Media');
	loadSource('media/Aeva-Subs-Vital');
	$force_thumbnail = false;

	if (empty($amSettings['enable_cache']) || ($amSettings = cache_get_data('aeva_settings', 60)) == null)
	{
		$amSettings = array();
		$request = wesql::query('
			SELECT name, value
			FROM {db_prefix}media_settings');
		while ($row = wesql::fetch_assoc($request))
			$amSettings[$row['name']] = $row['value'];
		wesql::free_result($request);

		// Cache the settings
		if ($amSettings['enable_cache'])
			cache_put_data('aeva_settings', $amSettings, 60);
	}

	$request = wesql::query('
		SELECT id_file, id_thumb, id_preview, l.id_media
		FROM {db_prefix}awards AS l
			LEFT JOIN {db_prefix}media_items AS mi ON (mi.id_media = l.id_media)
		WHERE l.id_award = {int:current_award}',
		array(
			'current_award' => $context['in'],
		)
	);
	$award = wesql::fetch_assoc($request);
	$editing = !empty($award['id_file']);

	// Delete any old file if editing
	if ($editing)
		aeva_deleteFiles(array($award['id_file'], $award['id_thumb'], $award['id_preview']), true);

	// Some side data to prevent errors
	$data = array(
		'id_album' => 1,
		'id_media' => 0,
		'title' => '',
		'description' => '',
		'keywords' => '',
		'id_file' => 0,
		'id_thumb' => 0,
		'id_preview' => 0,
		'item_member' => 0,
		'media_type' => 'image',
	);

	$fileOpts = array(
		'filepath' => $_FILES['filename']['tmp_name'],
		'filename' => $_FILES['filename']['name'],
		'album' => $data['id_album'],
		'skip_thumb' => $force_thumbnail,
		'skip_preview' => $force_thumbnail && ($data['id_preview'] == 0),
		'destination' => aeva_getSuitableDir($data['id_album']),
		'is_uploading' => true,
		'force_id_file' => $data['id_file'],
		'force_id_thumb' => $data['id_thumb'] > 4 ? $data['id_thumb'] : 0,
		'force_id_preview' => $data['id_preview'],
	);

	$mime = array(
		'image/bmp',
		'image/png',
		'image/gif',
		'image/jpeg',
	);
	if (!in_array($_FILES['filename']['type'], $mime))
		$ret = array('error' => 'invalid_extension', 'error_context' => array(westr::htmlspecialchars($_FILES['filename']['type'])));
	else
		$ret = aeva_createFile($fileOpts);

	if (isset($ret['error']))
	{
		$errs = array();
		$errors = array(
			'file_not_found' => 'upload_failed',
			'dest_not_found' => 'upload_failed',
			'size_too_big' => 'upload_file_too_big',
			'width_bigger' => 'error_width',
			'height_bigger' => 'error_height',
			'invalid_extension' => 'invalid_extension',
			'dest_empty' => 'dest_failed',
		);
		$errs[] = vsprintf($txt['media_' . (isset($errors[$ret['error']]) ? $errors[$ret['error']] : 'upload_failed')], $ret['error_context']);
		header('HTTP/1.1 403 Forbidden');
		die(json_encode($errs));
	}
	else
	{
		$id_file = $ret['file'];
		$id_thumb = empty($ret['thumb']) ? 0 : $ret['thumb'];
		$id_preview = empty($ret['preview']) ? 0 : $ret['preview'];
		$time = empty($ret['time']) ? 0 : $ret['time'];

		// Get the array ready for creating/modifying
		$options = array(
			'id' => 0,
			'title' => empty($name) ? date('d m Y, G:i') : $name,
			'description' => '',
			'album' => $data['id_album'],
			'keywords' => '',
			'id_file' => $id_file,
			'id_thumb' => $id_thumb,
			'id_preview' => $id_preview,
			'time' => $time,
			'embed_url' =>'',
			'id_member' => we::$user['id'],
			'mem_name' => we::$user['name'],
			'approved' => 1,
		);

		if (!empty($editing))
		{
			$id_media = $options['id_media'] = $award['id_media'];
			$options['skip_log'] = true;
			aeva_modifyItem($options);
			wesql::query('
				UPDATE {db_prefix}awards
				SET
					id_media = {int:id_media}
				WHERE id_award = {int:id_award}',
				array(
					'id_award' => $context['in'],
					'id_media' => $id_media
				)
			);
		}
		else
			$id_media = aeva_createItem($options);

		die('<img src="' . $scripturl . '?action=media;sa=media;in=' . $id_media . ';thumb" class="floatleft aea" /><div class="floatleft" style="margin-left: 2em;">' . $_FILES['filename']['name'] . '<hr>[media id=' . $id_media . ']</div><br class="clear"><input type="hidden" name="id_media" value="' . $id_media . '" /></div>');
	}
}

function deleteAwards($awards)
{
	$request = wesql::query('
		SELECT id_media
		FROM {db_prefix}awards
		WHERE id_award IN ({array_int:awards})',
		array(
			'awards' => $awards,
		)
	);
	$media = array();
	while ($row = wesql::fetch_row($request))
		$media[] = $row[0];
	wesql::free_result($request);

	loadSource('media/Subs-Media');
	loadSource('media/Aeva-Subs-Vital');
	aeva_deleteItems($media, true, false);

	wesql::query('
		DELETE FROM {db_prefix}awards
		WHERE id_award IN ({array_int:awards})',
		array(
			'awards' => $awards,
		)
	);

	wesql::query('
		DELETE FROM {db_prefix}log_member_awards
		WHERE id_award IN ({array_int:awards})',
		array(
			'awards' => $awards,
		)
	);

	redirectexit('action=admin;area=awards');
}

function AwardsAssign()
{
	global $context, $settings, $txt;

	// Sort out the context!
	$context['in'] = isset($_REQUEST['in']) ? (int) $_REQUEST['in'] : 0;
	$assigned = array();
	$context['awards'] = total_getAwards();

	if ($context['in'])
	{
		$request = wesql::query('
			SELECT am.id_member, real_name
			FROM {db_prefix}log_member_awards AS am
				INNER JOIN {db_prefix}members AS mem ON (mem.id_member = am.id_member)
			WHERE am.id_award = {int:current_award}',
			array(
				'current_award' => $context['awards'][$context['in']]['id_award'],
			)
		);
		while ($row = wesql::fetch_row($request))
			$assigned += array((int) $row[0] => $row[1]);

		if (!empty($_POST['recipient_to']))
			$assigned = $_POST['recipient_to'];
	}

	// First step, select the member and awards
	if (!isset($_REQUEST['step']) || $_REQUEST['step'] == 1)
	{
		add_plugin_css_file('live627:awards', 'awards', true);
		add_js_file('scripts/suggest.js');
		add_js('
	new weAutoSuggest({
		bItemList: true,
		sControlId: \'add_member\',
		sPostName:  \'recipient_to\',
		sTextDeleteItem: ', JavaScriptEscape($txt['autosuggest_delete_item']), '');

		if (!empty($assigned))
			add_js(',
		aListItems: ', json_encode($assigned));

		add_js('
	});');

		// Set the current step.
		$context['step'] = 1;

		// Set the title
		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_assign'];
	}
	elseif (isset($_REQUEST['step']) && $_REQUEST['step'] == 2)
	{
		foreach ($_POST['recipient_to'] as $recipient)
			if ($recipient != '{MEMBER_ID}')
				$members[] = (int) $recipient;

		if (empty($members) || empty($context['in']))
			fatal_lang_error('awards_error_no_members');
		$values = array();
		foreach ($members as $member)
			$values[] = array($context['in'], $member, time());

		wesql::insert('ignore',
			'{db_prefix}log_member_awards',
			array('id_award' => 'int', 'id_member' => 'int', 'date_issued' => 'string'),
			$values,
			array('id_member', 'id_award')
		);

		redirectexit('action=admin;area=awards;sa=viewassigned;id=' . $context['in']);
	}

	$context['sub_template'] = 'assign';
}

function AwardsViewAssigned()
{
	global $txt, $context;

	// Deleting?
	if (isset($_POST['delete'], $_POST['remove']))
	{
		checkSession();
		deleteAssignedMembers($_POST['remove']);
		redirectexit('action=admin;area=awards;sa=viewassigned');
	}

	$context['awards'] = total_getAwards();

	// Changing the status?
	if (isset($_POST['save']))
	{
		checkSession();
		foreach (total_getAwards() as $award)
		{
			$award = !empty($_POST['in'][$award['id_member']]) ? 'yes' : '';
			if ($award != $award['active'])
				wesql::query('
					UPDATE {db_prefix}log_member_awards
					SET id_award = {string:active}
					WHERE id_member = {int:award}',
					array(
						'new_award' => $context['awards'][$_POST['in']]['id_award'],
						'current_member' => $award['id_award'],
					)
				);
		}
		redirectexit('action=admin;area=viewassigned');
	}

	$listOptions = array(
		'id' => 'assigned_members',
		'base_href' => '<URL>?action=action=admin;area=awards;sa=viewassigned',
		'default_sort_col' => 'name',
		'no_items_label' => $txt['awards_error_no_assigned_members'],
		'items_per_page' => 25,
		'get_items' => array(
			'function' => 'list_getAssignedMembers',
		),
		'get_count' => array(
			'function' => 'list_getNumAssignedMembers',
		),
		'columns' => array(
			'name' => array(
				'header' => array(
					'value' => $txt['awards_category_name'],
					'style' => 'text-align: left;',
				),
				'data' => array(
					'db' => 'real_name',
					'style' => 'width: 40%;',
				),
				'sort' => array(
					'default' => 'real_name',
					'reverse' => 'real_name DESC',
				),
			),
			'award' => array(
				'header' => array(
					'value' => $txt['awards_num_in_category'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $context;

						$ret = \'
							<select name="in">\';

						foreach ($context[\'awards\'] as $award)
							$ret = \'
								<option value="\' . $award[\'id_award\'] . \'" class="award_icon_\' . $award[\'id_award\'] . \'"\' . $rowData[\'id_award\'] == $award[\'id_award\'] ? \' selected="selected"\' : \'\' . \'>\' . $award[\'name\'] . \'</option>\';

						$ret = \'
							</select>\';

						return $ret;
					'),
					'style' => 'width: 10%; text-align: center;',
				),
			),
			'remove' => array(
				'header' => array(
					'value' => $txt['awards_unassign'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;
						return sprintf(\'<span id="remove_%1$s" class="color_no">%2$s</span>&nbsp;<input type="checkbox" name="remove[%1$s]" id="remove_%1$s" value="%1$s">\', $rowData[\'id_member\'], $txt[\'no\']);
					'),
					'style' => 'width: 10%; text-align: center;',
				),
			),
		),
		'form' => array(
			'href' => '<URL>?action=admin;area=awards;sa=viewassigned',
			'name' => 'customProfileawards',
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
	wetem::load('show_list');
	$context['default_list'] = 'assigned_members';
	$context['page_title'] = $txt['awards_list_assigned_members'];
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

function list_getAssignedMembers($start, $items_per_page, $sort)
{
	$list = array();
	$request = wesql::query('
		SELECT am.id_member, real_name
		FROM {db_prefix}log_member_awards AS am
			INNER JOIN {db_prefix}members AS mem ON (mem.id_member = am.id_member)
		ORDER BY {raw:sort}
		LIMIT {int:start}, {int:items_per_page}',
		array(
			'sort' => $sort,
			'start' => $start,
			'items_per_page' => $items_per_page,
		)
	);
	while ($row = wesql::fetch_assoc($request))
		$list[] = $row;
	wesql::free_result($request);

	return $list;
}

function list_getNumAssignedMembers()
{
	$request = wesql::query('
		SELECT COUNT(*)
		FROM {db_prefix}log_member_awards');

	list ($numAssignedMembers) = wesql::fetch_row($request);
	wesql::free_result($request);

	return $numAssignedMembers;
}

function deleteAssignedMembers($assigned_members)
{
	wesql::query('
		DELETE FROM {db_prefix}log_member_awards
		WHERE id_member IN ({array_int:assigned_members})',
		array(
			'assigned_members' => $assigned_members,
		)
	);
}

function AwardsSettings()
{
	global $context, $settings, $txt, $boarddir;

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
			'awards_dir' => westr::htmlspecialchars($_POST['awards_dir'], ENT_QUOTES),
			'awards_favorites' => isset($_POST['awards_favorites']) ? 1 : 0,
			'awards_in_post' => isset($_POST['awards_in_post']) ? (int) $_POST['awards_in_post'] : 4,
		));
	}
}

function EditCategory()
{
	global $context, $txt, $settings, $settings;

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
		if ($_POST['id_category'] == 0)
			wesql::insert('',
				'{db_prefix}awards_categories',
				array('name' => 'string'),
				array($name),
				array('id_category')
			);
		else
		{
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

	wetem::load('edit_category');
}

function ListCategories()
{
	global $txt, $context;

	// Deleting?
	if (isset($_POST['delete'], $_POST['remove']))
	{
		checkSession();
		deleteCategories($_POST['remove']);
		redirectexit('action=admin;area=awards;sa=categories');
	}

	// New award?
	if (isset($_POST['new']))
		redirectexit('action=admin;area=awards;sa=editcategory');

	$listOptions = array(
		'id' => 'categories',
		'base_href' => '<URL>?action=action=admin;area=awards;sa=categories',
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
						global $txt;

						return sprintf(\'<a href="<URL>?action=admin;area=awards;sa=viewcategory;in=$1">$2</a> ($3)\', $rowData[\'id_category\'], $txt[\'showAwards\'], $rowData[\'awards\']);
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
						'format' => '<a href="' . '<URL>?action=admin;area=awards;sa=postawardedit;in=%1$s">' . $txt['modify'] . '</a>',
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
			'href' => '<URL>?action=admin;area=awards;sa=categories',
			'name' => 'customProfileawards',
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
	wetem::load('show_list');
	$context['default_list'] = 'categories';
	$context['page_title'] = $txt['awards_list_categories'];
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

function deleteCategories($categories)
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

// Dynamic function to cache admin menu icons into admenu.css
function dynamic_award_icons($match)
{
	global $context, $gal_url, $scripturl;

	// Call the all-important file
	loadSource('media/Subs-Media');

	// Load stuff
	loadMediaSettings($gal_url, true, true);

	$ina = total_getAwards();
	$rep = '';
	foreach ($ina as $val)
	{
		list ($path, $filename, $is_new) = getMediaPath($val['id_media']);
		$rep .= '
.award_icon_' . $val['id_award'] . '
	background: url(' . $scripturl . '?action=media&sa=media&in=' . $val['id_media'] . '&thumb) no-repeat 4px 3px
	padding-left: math(width(' . $path . ')px * 1.35px) !important
	min-width: width(' . $path . ')px
	min-height: height(' . $path . ')px';
	}

	return $rep;
}

?>