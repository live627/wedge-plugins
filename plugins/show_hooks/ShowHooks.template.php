<?php

function template_hooks()
{
	global $context, $txt;

	echo '
	<div id="admincenter">
		<we:cat>
			', $context['page_title'], '
		</we:cat>
		<form action="<URL>?action=admin;area=hooks" method="post" accept-charset="UTF-8">
			<div class="right">
				Filter results:
				<select name="select_hooks">
					' . implode('
					', $context['plugin_choices']) . '
				</select>
				<input type="submit" name="change_hooks" value="' . $txt['hooks_button_go'] . '" class="submit" />
			</div>
		</form>';

	foreach ($context['filter_enabled_plugins'] as $enabled_plugin_id => $enabled_plugin_name)
		echo '
		<we:title>', $context['plugin_details'][$enabled_plugin_name]['name'], '</we:title>
		<div class="description">', $context['plugin_details'][$enabled_plugin_name]['description'], '</div>
		', template_show_list('list_hooks_' . $enabled_plugin_name);

	echo '
	</div>';
}

?>