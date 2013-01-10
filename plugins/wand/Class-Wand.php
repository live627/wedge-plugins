<?php
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is http://code.mattzuba.com code.
 *
 * The Initial Developer of the Original Code is
 * Matt Zuba.
 * Portions created by the Initial Developer are Copyright (C) 2011
 * the Initial Developer. All Rights Reserved.
 *
 * Reworked for use with Wedge by
 * John Rayes (live627)
 *
 * Contributor(s): SlammedDime, Norv, emanuele, live627
 *
 * ***** END LICENSE BLOCK ***** */

/**
 * Populates the forum with dummy entries.
 *
 * @author mzuba
 */
class Wand
{
	private $blockSize = 75;
	private $loremIpsum = null;
	private $steps = array();
	private $step = null;

	private $timeStart = 0;

	public function __construct($options = array())
	{
		$this->steps['categories']['max'] = 10;
		$this->steps['categories']['current'] = 0;
		$this->steps['boards']['max'] = 100;
		$this->steps['boards']['current'] = 0;
		$this->steps['members']['max'] = 150000;
		$this->steps['members']['current'] = 0;
		$this->steps['moderators']['max'] = 15000;
		$this->steps['moderators']['current'] = 0;
		$this->steps['topics']['max'] = 370000;
		$this->steps['topics']['current'] = 0;
		$this->steps['messages']['max'] = 3000000;
		$this->steps['messages']['current'] = 0;

		// Allow plugins to add their own steps.
		call_hook('wand_steps', array(&$this->steps));

		$this->timeStart = microtime(true);

		// Override defaults
		foreach ($options as $_key => $_value)
			$this->$_key = $_value;

		$this->loremIpsum = new LoremIpsumGenerator();

		// Determine our 'currents'
		foreach ($this->steps as $key => $val)
		{
			$request = wesql::query('SELECT COUNT(*) FROM {db_prefix}' . $key);
			list ($this->steps[$key]['current']) = wesql::fetch_row($request);
			wesql::free_result($request);
		}
	}

	function start()
	{
		$this->timeStart = microtime(true);
		$i = 0;

		// Determine our 'currents'
		foreach ($this->steps as $key => $val)
		{
			$i++;
			if ($key != 'topics' && $this->steps[$key]['current'] < $this->steps[$key]['max'])
			{
				$this->step = $key;
				$func = 'make' . ucfirst($key);
				$end = false;
				break;
			}
			else
				$end = true;
		}
		if (!empty($func))
			$this->$func($i);

		$this->complete($i, $end);
	}

	private function makeCategories($i)
	{
		loadSource('Subs-Categories');

		while ($this->steps['categories']['current'] < $this->steps['categories']['max'] && $this->blockSize--)
		{
			$catOptions = array(
				'move_after' => 0,
				'cat_name' => 'Category Number ' . ++$this->steps['categories']['current'],
				'is_collapsible' => 1,
			);
			createCategory($catOptions);
		}

		$this->pause($i, 'categories');
	}

	private function makeBoards($i)
	{
		loadSource('Subs-Boards');

		while ($this->steps['boards']['current'] < $this->steps['boards']['max'] && $this->blockSize--)
		{
			$boardOptions = array(
				'board_name' => 'Board Number ' . ++$this->steps['boards']['current'],
				'board_description' => 'I am a sample description...',
				'target_category' => mt_rand(1, $this->steps['categories']['current']),
				'move_to' => 'top',
			);
			/* if (mt_rand() < (mt_getrandmax() / 2))
			{
				$boardOptions = array_merge($boardOptions, array(
					'target_board' => mt_rand(1, $this->steps['boards']['current']-1),
					'move_to' => 'child',
				));
			} */

			createBoard($boardOptions);
		}

		$this->pause($i, 'boards');
	}

	private function makeMembers($i)
	{
		loadSource('Subs-Members');

		while ($this->steps['members']['current'] < $this->steps['members']['max'] && $this->blockSize--)
		{
			$regOptions = array(
				'interface' => 'admin',
				'username' => 'Member ' . ++$this->steps['members']['current'],
				'email' => 'member_' . $this->steps['members']['current'] . '@' . $_SERVER['SERVER_NAME'] . (strpos($_SERVER['SERVER_NAME'], '.') === FALSE ? '.com' : ''),
				'password' => '',
				'require' => 'nothing'
			);

			registerMember($regOptions);
		}

		$this->pause($i, 'members');
	}

	private function makeModerators($i)
	{
		$inserts = array();

		while ($this->steps['moderators']['current'] < $this->steps['moderators']['max'] && $this->blockSize--)
		{
			$inserts[] = array(mt_rand(1, $this->steps['boards']['max']), mt_rand(1, $this->steps['members']['max']));
			++$this->steps['moderators']['current'];
		}

		wesql::insert('ignore',
			'{db_prefix}moderators',
			array('id_board' => 'int', 'id_member' => 'int'),
			$inserts,
			array('id_board', 'id_member')
		);

		$this->pause($i, 'moderators');
	}

	private function makeMessages($i)
	{
		loadSource('Subs-Post');

		while ($this->steps['messages']['current'] < $this->steps['messages']['max'] && $this->blockSize--)
		{
			$msgOptions = array(
				'subject' => trim($this->loremIpsum->getContent(mt_rand(1,6), 'txt')),
				'body' => trim($this->loremIpsum->getContent(mt_rand(5, 60), 'txt')),
				'approved' => true
			);

			$topicOptions = array(
				'id' => $this->steps['topics']['current'] < $this->steps['topics']['max'] && mt_rand() < (int)(mt_getrandmax() * ($this->steps['topics']['max'] / $this->steps['messages']['max'])) ? 0 : ($this->steps['topics']['current'] < $this->steps['topics']['max'] ? mt_rand(1, ++$this->steps['topics']['current']) : mt_rand(1, $this->steps['topics']['current'])),
				'board' => mt_rand(1, $this->steps['boards']['max']),
				'mark_as_read' => true,
			);

			$member = mt_rand(1, $this->steps['members']['max']);
			$posterOptions = array(
				'id' => $member,
				'name' => 'Member ' . $member,
				'email' => 'member_' . $member . '@' . $_SERVER['SERVER_NAME'] . '.com',
				'update_post_count' => true,
			);

			createPost($msgOptions, $topicOptions, $posterOptions);
		}
		$this->pause($i, 'messages');
	}

	private function pause($i, $curStep, $end = false)
	{
		if (!$end)
		{
			header('Content-Type: text/xml; charset=UTF-8');
			echo '<', '?xml version="1.0" encoding="UTF-8"?', '>
<response>
	<lastsave value="', $this->steps[$curStep]['current'], '" max="', $this->steps[$curStep]['max'], '" disp="', comma_format($this->steps[$curStep]['max'] - $this->steps[$curStep]['current']), '" li="', $i, '" />
</response>';

			obExit(false);
		}
	}

	private function fixupTopicsBoards()
	{
		wesql::query('
			UPDATE {db_prefix}messages as m, {db_prefix}topics as t
			SET m.id_board = t.id_board
			WHERE m.id_topic = t.id_topic');
	}

	private function complete($i, $end)
	{
		if ($end)
		{
			$this->fixupTopicsBoards();
			$this->pause(null, $end);
		}
	}

	function getSteps()
	{
		return $this->steps;
	}

	function getStep()
	{
		return $this->step;
	}
}

class LoremIpsumGenerator
{
	/**
	*    Copyright (c) 2009, Mathew Tinsley (tinsley@tinsology.net)
	*    All rights reserved.
	*
	*    Redistribution and use in source and binary forms, with or without
	*    modification, are permitted provided that the following conditions are met:
	*        * Redistributions of source code must retain the above copyright
	*          notice, this list of conditions and the following disclaimer.
	*        * Redistributions in binary form must reproduce the above copyright
	*          notice, this list of conditions and the following disclaimer in the
	*          documentation and/or other materials provided with the distribution.
	*        * Neither the name of the organization nor the
	*          names of its contributors may be used to endorse or promote products
	*          derived from this software without specific prior written permission.
	*
	*    THIS SOFTWARE IS PROVIDED BY MATHEW TINSLEY ''AS IS'' AND ANY
	*    EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	*    WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	*    DISCLAIMED. IN NO EVENT SHALL <copyright holder> BE LIABLE FOR ANY
	*    DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	*    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	*    LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	*    ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	*    (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	*    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	*/

	private $words, $wordsPerParagraph, $wordsPerSentence;

	function __construct($wordsPer = 100)
	{
		$this->wordsPerParagraph = $wordsPer;
		$this->wordsPerSentence = 24.460;
		$this->words = array(
			'lorem',
			'ipsum',
			'dolor',
			'sit',
			'amet',
			'consectetur',
			'adipiscing',
			'elit',
			'curabitur',
			'vel',
			'hendrerit',
			'libero',
			'eleifend',
			'blandit',
			'nunc',
			'ornare',
			'odio',
			'ut',
			'orci',
			'gravida',
			'imperdiet',
			'nullam',
			'purus',
			'lacinia',
			'a',
			'pretium',
			'quis',
			'congue',
			'praesent',
			'sagittis',
			'laoreet',
			'auctor',
			'mauris',
			'non',
			'velit',
			'eros',
			'dictum',
			'proin',
			'accumsan',
			'sapien',
			'nec',
			'massa',
			'volutpat',
			'venenatis',
			'sed',
			'eu',
			'molestie',
			'lacus',
			'quisque',
			'porttitor',
			'ligula',
			'dui',
			'mollis',
			'tempus',
			'at',
			'magna',
			'vestibulum',
			'turpis',
			'ac',
			'diam',
			'tincidunt',
			'id',
			'condimentum',
			'enim',
			'sodales',
			'in',
			'hac',
			'habitasse',
			'platea',
			'dictumst',
			'aenean',
			'neque',
			'fusce',
			'augue',
			'leo',
			'eget',
			'semper',
			'mattis',
			'tortor',
			'scelerisque',
			'nulla',
			'interdum',
			'tellus',
			'malesuada',
			'rhoncus',
			'porta',
			'sem',
			'aliquet',
			'et',
			'nam',
			'suspendisse',
			'potenti',
			'vivamus',
			'luctus',
			'fringilla',
			'erat',
			'donec',
			'justo',
			'vehicula',
			'ultricies',
			'varius',
			'ante',
			'primis',
			'faucibus',
			'ultrices',
			'posuere',
			'cubilia',
			'curae',
			'etiam',
			'cursus',
			'aliquam',
			'quam',
			'dapibus',
			'nisl',
			'feugiat',
			'egestas',
			'class',
			'aptent',
			'taciti',
			'sociosqu',
			'ad',
			'litora',
			'torquent',
			'per',
			'conubia',
			'nostra',
			'inceptos',
			'himenaeos',
			'phasellus',
			'nibh',
			'pulvinar',
			'vitae',
			'urna',
			'iaculis',
			'lobortis',
			'nisi',
			'viverra',
			'arcu',
			'morbi',
			'pellentesque',
			'metus',
			'commodo',
			'ut',
			'facilisis',
			'felis',
			'tristique',
			'ullamcorper',
			'placerat',
			'aenean',
			'convallis',
			'sollicitudin',
			'integer',
			'rutrum',
			'duis',
			'est',
			'etiam',
			'bibendum',
			'donec',
			'pharetra',
			'vulputate',
			'maecenas',
			'mi',
			'fermentum',
			'consequat',
			'suscipit',
			'aliquam',
			'habitant',
			'senectus',
			'netus',
			'fames',
			'quisque',
			'euismod',
			'curabitur',
			'lectus',
			'elementum',
			'tempor',
			'risus',
			'cras',
		);
	}

	function getContent($count, $format = 'html', $loremipsum = true)
	{
		$format = strtolower($format);

		if ($count <= 0)
			return '';

		switch ($format)
		{
			case 'txt':
				return $this->getText($count, $loremipsum);
			case 'plain':
				return $this->getPlain($count, $loremipsum);
			default:
				return $this->getHTML($count, $loremipsum);
		}
	}

	private function getWords(&$arr, $count, $loremipsum)
	{
		$i = 0;
		if ($loremipsum)
		{
			$i = 2;
			$arr[0] = 'lorem';
			$arr[1] = 'ipsum';
		}

		for ($i; $i < $count; $i++)
		{
			$index = array_rand($this->words);
			$word = $this->words[$index];
			//echo $index . '=>' . $word . '<br />';

			if ($i > 0 && $arr[$i - 1] == $word)
				$i--;
			else
				$arr[$i] = $word;
		}
	}

	private function getPlain($count, $loremipsum, $returnStr = true)
	{
		$words = array();
		$this->getWords($words, $count, $loremipsum);
		//print_r($words);

		$delta = $count;
		$curr = 0;
		$sentences = array();
		while ($delta > 0)
		{
			$senSize = $this->gaussianSentence();
			//echo $curr . '<br />';
			if (($delta - $senSize) < 4)
				$senSize = $delta;

			$delta -= $senSize;

			$sentence = array();
			for ($i = $curr; $i < ($curr + $senSize); $i++)
				$sentence[] = $words[$i];

			$this->punctuate($sentence);
			$curr = $curr + $senSize;
			$sentences[] = $sentence;
		}

		if ($returnStr)
		{
			$output = '';
			foreach ($sentences as $s)
				foreach ($s as $w)
					$output .= $w . ' ';

			return $output;
		}
		else
			return $sentences;
	}

	private function getText($count, $loremipsum)
	{
		$sentences = $this->getPlain($count, $loremipsum, false);
		$paragraphs = $this->getParagraphArr($sentences);

		$paragraphStr = array();
		foreach ($paragraphs as $p)
		{
			$paragraphStr[] = $this->paragraphToString($p);
		}

		$paragraphStr[0] = "\t" . $paragraphStr[0];
		return implode("\n\n\t", $paragraphStr);
	}

	private function getParagraphArr($sentences)
	{
		$wordsPer = $this->wordsPerParagraph;
		$sentenceAvg = $this->wordsPerSentence;
		$total = count($sentences);

		$paragraphs = array();
		$pCount = 0;
		$currCount = 0;
		$curr = array();

		for ($i = 0; $i < $total; $i++)
		{
			$s = $sentences[$i];
			$currCount += count($s);
			$curr[] = $s;
			if ($currCount >= ($wordsPer - round($sentenceAvg / 2.00)) || $i == $total - 1)
			{
				$currCount = 0;
				$paragraphs[] = $curr;
				$curr = array();
				//print_r($paragraphs);
			}
			//print_r($paragraphs);
		}

		return $paragraphs;
	}

	private function getHTML($count, $loremipsum)
	{
		$sentences = $this->getPlain($count, $loremipsum, false);
		$paragraphs = $this->getParagraphArr($sentences);
		//print_r($paragraphs);

		$paragraphStr = array();
		foreach ($paragraphs as $p)
		{
			$paragraphStr[] = "<p>\n" . $this->paragraphToString($p, true) . '</p>';
		}

		//add new lines for the sake of clean code
		return implode("\n", $paragraphStr);
	}

	private function paragraphToString($paragraph, $htmlCleanCode = false)
	{
		$paragraphStr = '';
		foreach ($paragraph as $sentence)
		{
			foreach ($sentence as $word)
				$paragraphStr .= $word . ' ';

			if ($htmlCleanCode)
				$paragraphStr .= "\n";
		}
		return $paragraphStr;
	}

	/*
	* Inserts commas and periods in the given
	* word array.
	*/
	private function punctuate(&$sentence)
	{
		$count = count($sentence);
		$sentence[$count - 1] = $sentence[$count - 1] . '.';

		if ($count < 4)
			return $sentence;

		$commas = $this->numberOfCommas($count);

		for ($i = 1; $i <= $commas; $i++)
		{
			$index = (int) round($i * $count / ($commas + 1));

			if ($index < ($count - 1) && $index > 0)
			{
				$sentence[$index] = $sentence[$index] . ',';
			}
		}
	}

	/*
	* Determines the number of commas for a
	* sentence of the given length. Average and
	* standard deviation are determined superficially
	*/
	private function numberOfCommas ($len)
	{
		$avg = (float) log($len, 6);
		$stdDev = (float) $avg / 6.000;

		return (int) round($this->gauss_ms($avg, $stdDev));
	}

	/*
	* Returns a number on a gaussian distribution
	* based on the average word length of an english
	* sentence.
	* Statistics Source:
	*	http://hearle.nahoo.net/Academic/Maths/Sentence.html
	*	Average: 24.46
	*	Standard Deviation: 5.08
	*/
	private function gaussianSentence()
	{
		$avg = (float) 24.460;
		$stdDev = (float) 5.080;

		return (int) round($this->gauss_ms($avg, $stdDev));
	}

	/*
	* The following three functions are used to
	* compute numbers with a guassian distrobution
	* Source:
	* 	http://us.php.net/manual/en/function.rand.php#53784
	*/
	private function gauss()
	{   // N(0,1)
		// returns random number with normal distribution:
		//   mean=0
		//   std dev=1

		// auxilary vars
		$x=$this->random_0_1();
		$y=$this->random_0_1();

		// two independent variables with normal distribution N(0,1)
		$u = sqrt(-2 * log($x)) * cos(2 * pi() * $y);
		$v = sqrt(-2 * log($x)) * sin(2 * pi() * $y);

		// i will return only one, couse only one needed
		return $u;
	}

	private function gauss_ms($m = 0.0, $s = 1.0)
	{
		return $this->gauss() * $s + $m;
	}

	private function random_0_1()
	{
		return (float) rand() / (float) getrandmax();
	}

}