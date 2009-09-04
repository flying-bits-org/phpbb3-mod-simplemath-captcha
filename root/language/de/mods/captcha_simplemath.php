<?php
/**
*
* SimpleMath captcha [Deutsch]
*
* @package language
* @version $Id$
* @copyright (c) 2009 nickvergessen
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, array(
	'CAPTCHA_SIMPLEMATH'		=> 'SimpleMath CAPTCHA',

	'SIMPLEMATH_CONFIRM_WRONG'	=> 'Dein Ergebnis für die Rechenaufgabe war falsch.',
	'SIMPLEMATH_EXPLAIN'		=> 'Um die Aufgabe zu lösen, gib das numerische Ergebnis der Rechenaufgabe ein.',
	'SIMPLEMATH_TEMPLATE'		=> '%1$s %2$s %3$s =',// Number, Operator, Number

	'VC_REFRESH'				=> 'Neue Rechenaufgabe',

	'operators'	=> array(
		/**
		* You can add any operator here...
		* The first key in the array of an operator must be the php-function-name.
		* The number of keys must be greater than 2.
		*/
		'+'	=> array(
			'bcadd',	// php function name
			'+',
			'plus',
		),
		'-'	=> array(
			'bcsub',
			'-',
			'minus',
		),
	),

	'numbers'	=> array(
		/**
		* You can add any number here...
		* The first key in the array of a number must be the integer-value as a string.
		* The number of keys may be 1 or more.
		*/
		0	=> array(
			'0',
			'null',
			'0',
		),
		1	=> array(
			'1',	// nummerisch
			'eins',	// deutsch
			'I',	// lateinisch
		),
		2	=> array(
			'2',
			'zwei',
			'II',
		),
		3	=> array(
			'3',
			'drei',
			'III',
		),
		4	=> array(
			'4',
			'vier',
			'IV',
		),
		5	=> array(
			'5',
			'fünf',
			'V',
		),
		6	=> array(
			'6',
			'sechs',
			'VI',
		),
		7	=> array(
			'7',
			'sieben',
			'VII',
		),
		8	=> array(
			'8',
			'acht',
			'VIII',
		),
		9	=> array(
			'9',
			'neun',
			'IX',
		),
		10	=> array(
			'10',
			'zehn',
			'X',
		),
	),
));

?>