<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function GT()
{
	isAllowedTo('view_tweets');

	if (!empty($settings['gtName']))
		fatal_lang_error('gt_name_unset', false);

	loadPluginLanguage('live627:gt', 'GT');
	gt_render();
}

function gt_ic()
{
	global $context, $theme, $settings;

	if (allowedTo('view_tweets'))
	{
		wetem::load('gt_ic', 'sidebar');
		gt_render();
	}
}

function gt_render()
{
	global $context, $theme, $settings;

	if (!empty($settings['gtName']))
	{
		$context['css_main_files'][] = 'gt';
		$context['skin_folders'][] = array($context['plugins_dir']['live627:gt'] . '/', 'live627:gt_');
		$theme['live627:gt_url'] = $context['plugins_dir']['live627:gt'];
		add_js('
	$(document).ready(function(){
		var username = ' . JavaScriptEscape($settings['gtName']) . '; // set user name
		var format = "json"; // set format, you really don"t have an option on this one
		var url = "http://api.twitter.com/1/statuses/user_timeline/" + username + "." + format + "?callback=?"; // make the url
		var fi = ' . (!empty($settings['gtFadeIn']) ? 'true' : 'false') . ';
		var num = ' . (!empty($settings['gtNum']) ? 'true' : 'false') . ';

		$.getJSON(url, function(data) {
			if (fi)
				$("#tweets p").hide();
			$("#tweets p").empty();
			len = data.length;
			for (i = 0; i < len; i++)
			{
				var tweet = data[i].text;

				tweet = tweet.replace(/(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig, function(url) {
					return "<a href=\\"" + url + "\\">" + url + "</a>";
				}).replace(/@([_a-z0-9]+)/ig, function(reply) {
					return reply.charAt(0) + "<a href=\\"http://twitter.com/" + reply.substring(1) + "\\">" + reply.substring(1) + "</a>";
				}).replace(/#([_a-z0-9]+)/ig, function(reply) {
					return reply.charAt(0) + "<a href=\\"http://twitter.com/" + reply.substring(1) + "\\">" + reply.substring(1) + "</a>";
				});

				if (num)
					tweet = (i + 1) + ": " + tweet;
				$("#tweets p").append(tweet + "<br><br>");
			}
			if (fi)
				$("#tweets p").fadeIn();
		});
	});');
	}
}

function template_gt()
{
	global $txt;

	echo '
		<we:title>', $txt['gt_title'], '</we:title>
		<div id="tweets"><p>Connecting...</p></div>';
}

function template_gt_ic()
{
	global $txt;

	echo '
		<we:title>Latest Tweets</we:title>
		<div id="tweets"><p>Connecting...</p></div>';
}

?>