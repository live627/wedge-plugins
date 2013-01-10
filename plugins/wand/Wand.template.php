<?php

function template_wand()
{
	global $context, $txt;

	echo '
	<div id="admincenter">
		<we:cat>
			', $context['page_title'], '
		</we:cat>
		<div class="windowbg2 wrc">
			This will populate the database with<br>
			', $context['steps'], '.<br>
			<hr>
			<a href="<URL>?action=admin;area=wand;do">Start now!</a>
		</div>
	</div>';
}

function template_wand2()
{
	global $context, $txt;

	echo '
	<div id="admincenter">
		<we:cat>
			', $context['page_title'], '
		</we:cat>
		<div class="windowbg2 wrc">
			<div class="two-columns">
				Please wait while this process is being completed.<br><br>
				<h6>Current step</h6>
				<div class="progress_bar">
					<div id="step_progress" class="green_percent"></div>
				</div>
				<h6>Overall progress</h6>
				<div class="progress_bar">
					<div id="overall_progress" class="blue_percent"></div>
				</div>
			</div>
			<div class="two-columns">
				<ol>
					<li>Generating <span>', $context['steps'], '</li>
				</ol>
				<ul class="reset">
					<li>Estimated time remaining:</li>
					<li id="t">Calculating...</li>
				</ul>

			</div>
		</div>
		<script><!-- // --><![CDATA[
			window.onload = doAutoSubmit;

			function doAutoSubmit()
			{
				var t1 = new Date();

				$.get(weUrl(\'action=admin;area=wand;do;xml\'), function (data) {
					var
						obj = $(\'lastsave\', data),
						li = obj.attr(\'li\'),
						current = obj.attr(\'value\'),
						max = obj.attr(\'max\'),
						disp = obj.attr(\'disp\'),
						width = (current / max) * 100,
						overall_progress = ', $context['totals']['current'], ',
						overall_total = ', $context['totals']['max'], ',
						overall_width = (overall_progress / overall_total) * 100,
						s = (new Date() - t1) / 1000,
						t = Math.round((s / current) * (max - current)),
						overall_t = Math.round((s / overall_progress) * (overall_total - overall_progress));

					updateStepProgress(width, overall_width);
					setTimeout(doAutoSubmit, 500);

					$(\'#admincenter li:nth-child(-n+\' + li + \')\').attr(\'class\', \'p\');
					$(\'#admincenter li:nth-child(\' + li + \')\').attr(\'class\', \'b\').find(\'span\').text(disp);
					$(\'#t\').html(\'about \' + overall_t + \' second(s)<br>about \' + s * (1 / width) + \' second(s) for the current step\');
				});
			}

			// This function dynamically updates the step progress bar - and overall one as required.
			function updateStepProgress(width, overall_width)
			{
				document.getElementById(\'step_progress\').style.width = width + "%";
				document.getElementById(\'overall_progress\').style.width = overall_width + "%";
			}
		// ]]></script>
	</div>';
}

?>