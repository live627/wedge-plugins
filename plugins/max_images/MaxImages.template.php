<?php

function template_modfilter_imgs()
{
	global $context, $txt;

	$js_conds = array();
	echo '
		<br>', $txt['modfilter_imgs_is'], '
		<select name="rangesel" onchange="validateImgs();">';

	foreach (array('lt', 'lte', 'eq', 'gte', 'gt') as $item)
	{
		echo '
			<option value="', $item, '">', $txt['modfilter_range_' . $item], '</option>';
		$js_conds[] = $item . ': ' . JavaScriptEscape($txt['modfilter_range_' . $item]);
	}

	echo '
		</select>
		<input type="text" size="5" name="imgs" style="padding: 3px 5px 5px 5px" onchange="validateImgs();">
		<div class="pagesection ruleSave">
			<div class="floatright">
				<input class="new" type="submit" value="', $txt['modfilter_condition_done'], '" onclick="addImgs(e);">
			</div>
		</div>';

	add_js('
	function validateImgs()
	{
		var
			applies_type = $("#rulecontainer select[name=rangesel]").val(),
			imgs = $("#rulecontainer input[name=imgs]").val(),
			pc_num = parseInt(imgs);

		$("#rulecontainer .ruleSave").toggle(in_array(applies_type, ["lt", "lte", "eq", "gte", "gt"]) && imgs == pc_num && pc_num >= 0);
	};

	function addImgs(e)
	{
		e.preventDefault();
		var
			range = {' . implode(',', $js_conds) . '},
			pc = ' . JavaScriptEscape($txt['modfilter_cond_imgs']) . ',
			applies_type = $("#rulecontainer select[name=rangesel]").val(),
			imgs = $("#rulecontainer input[name=imgs]").val(),
			pc_num = parseInt(imgs);

		if (in_array(applies_type, ["lt", "lte", "eq", "gte", "gt"]) && imgs == pc_num && pc_num >= 0)
			addRow(pc, range[applies_type] + " " + imgs, "imgs", applies_type + ";" + imgs);
	};');
}

?>