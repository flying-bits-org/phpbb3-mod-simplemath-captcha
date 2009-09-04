<?php
/**
*
* @package VC - SimpleMath
* @version $Id$
* @copyright (c) 2009 nickvergessen
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* Placeholder for autoload
*/

/**
* @package VC
*/
class phpbb_captcha_simplemath
{
	var $confirm_id;
	var $confirm_code;
	var $code;
	var $attempts = 0;
	var $type;
	var $solved = 0;
	var $captcha_vars = false;

	var $simplemath_operator;
	var $simplemath_number1;
	var $simplemath_number2;
	var $simplemath_template;

	/**
	* @param int $type  as per the CAPTCHA API docs, the type
	*/
	function init($type)
	{
		global $config, $db, $user;

		$user->add_lang('mods/captcha_simplemath');

		// read input
		$this->confirm_id = request_var('confirm_id', '');
		$this->confirm_code = request_var('confirm_code', '');
		$refresh = request_var('refresh_vc', false) && $config['confirm_refresh'];

		$this->type = (int) $type;

		if (!strlen($this->confirm_id) || !$this->load_code())
		{
			// we have no confirm ID, better get ready to display something
			$this->generate_code();
		}
		else if ($refresh)
		{
			$this->regenerate_code();
		}
	}
	
	/**
	* API function
	*/
	function &get_instance()
	{
		$instance =& new phpbb_captcha_simplemath();
		return $instance;
	}

	/**
	* API function
	*/
	function is_installed()
	{
		return true;
	}

	/**
	* API function - for the captcha to be available, it must have installed itself and there has to be at least one question in the board's default lang
	*/
	function is_available()
	{
		global $user;

		$user->add_lang('mods/captcha_simplemath');

		return true;
	}


	/**
	* API function
	*/
	function has_config()
	{
		return false;
	}


	/**
	* API function
	*/
	function get_name()
	{
		return 'CAPTCHA_SIMPLEMATH';
	}

	/**
	* API function
	*/
	function get_class_name()
	{
		return 'phpbb_captcha_simplemath';
	}


	/**
	* API function - not needed as we don't display an image
	*/
	function execute_demo()
	{
	}

	/**
	* API function - not needed as we don't display an image
	*/
	function execute()
	{
	}

	/**
	* API function - send the question to the template
	*/
	function get_template()
	{
		global $config, $template;

		if ($this->is_solved())
		{
			return false;
		}
		else
		{
			$template->assign_vars(array(
				'SIMPLEMATH_TEMPLATE'		=> $this->simplemath_template,
				'CONFIRM_ID'				=> $this->confirm_id,
				'S_CONFIRM_CODE'			=> true,
				'S_TYPE'					=> $this->type,
				'S_CONFIRM_REFRESH'			=> ($config['enable_confirm'] && $config['confirm_refresh'] && $this->type == CONFIRM_REG) ? true : false,
			));

			return 'captcha_simplemath.html';
		}
	}

	/**
	* API function - we just display a mockup so that the captcha doesn't need to be installed
	*/
	function get_demo_template()
	{
		global $template, $user;

		$this->generate_rand_simplemath();

		$template->assign_vars(array(
			'SIMPLEMATH_RESULT'		=> $this->code,
			'SIMPLEMATH_TEMPLATE'	=> $this->simplemath_template,
		));

		return 'captcha_simplemath_acp_demo.html';
	}

	/**
	*  API function
	*/
	function get_hidden_fields()
	{
		$hidden_fields = array();

		// this is required - otherwise we would forget about the captcha being already solved
		if ($this->solved)
		{
			$hidden_fields['confirm_code'] = $this->code;
		}
		$hidden_fields['confirm_id'] = $this->confirm_id;
		return $hidden_fields;
	}

	/**
	* API function
	*/
	function garbage_collect($type)
	{
		global $db, $config;

		$sql = 'SELECT DISTINCT c.session_id
			FROM ' . CONFIRM_TABLE . ' c
			LEFT JOIN ' . SESSIONS_TABLE . ' s
				ON (c.session_id = s.session_id)
			WHERE s.session_id IS NULL' .
				((empty($type)) ? '' : ' AND c.confirm_type = ' . (int) $type);
		$result = $db->sql_query($sql);

		if ($row = $db->sql_fetchrow($result))
		{
			$sql_in = array();
			do
			{
				$sql_in[] = (string) $row['session_id'];
			}
			while ($row = $db->sql_fetchrow($result));

			if (sizeof($sql_in))
			{
				$sql = 'DELETE FROM ' . CONFIRM_TABLE . '
					WHERE ' . $db->sql_in_set('session_id', $sql_in);
				$db->sql_query($sql);
			}
		}
		$db->sql_freeresult($result);
	}

	/**
	* API function - we don't drop the tables here, as that would cause the loss of all entered questions.
	*/
	function uninstall()
	{
		$this->garbage_collect(0);
	}

	/**
	*  API function - set up shop
	*/
	function install()
	{
		return;
	}


	/**
	*  API function - see what has to be done to validate
	*/
	function validate()
	{
		global $config, $db, $user;

		$error = '';
		if (!$this->confirm_id)
		{
			$error = $user->lang['SIMPLEMATH_CONFIRM_WRONG'];
		}
		else
		{
			if ($this->check_code())
			{
				// $this->delete_code(); commented out to allow posting.php to repeat the question
				$this->solved = true;
			}
			else
			{
				$error = $user->lang['SIMPLEMATH_CONFIRM_WRONG'];
			}
		}

		if (strlen($error))
		{
			// okay, incorrect answer.
			$this->new_attempt();
			return $error;
		}
		else
		{
			return false;
		}
	}

	/**
	*  Select a question
	*/
	function generate_code()
	{
		global $db, $user;

		$this->confirm_id = md5(unique_id($user->ip));
		$this->solved = 0;
		$this->generate_rand_simplemath();

		$sql = 'INSERT INTO ' . CONFIRM_TABLE . ' ' . $db->sql_build_array('INSERT', array(
				'confirm_id'	=> (string) $this->confirm_id,
				'session_id'	=> (string) $user->session_id,
				'confirm_type'	=> (int) $this->type,
				'code'			=> (int) $this->code,
		));
		$db->sql_query($sql);
	}

	/**
	* New Question, if desired.
	*/
	function regenerate_code()
	{
		global $db, $user;

		$this->generate_rand_simplemath();
		$this->solved = 0;

		$sql_ary['code'] = (int) $this->code;
		$sql = 'UPDATE ' . CONFIRM_TABLE . ' SET
				' . $db->sql_build_array('UPDATE', $sql_ary) . "
			WHERE confirm_id = '" . $db->sql_escape($this->confirm_id) . "'
				AND session_id = '" . $db->sql_escape($user->session_id) . "'";
		$db->sql_query($sql);
	}

	/**
	* Wrong answer, so we increase the attempts and use a different question.
	*/
	function new_attempt()
	{
		global $db, $user;

		$this->generate_rand_simplemath();
		$this->solved = 0;

		$sql_ary['code'] = (int) $this->code;
		$sql = 'UPDATE ' . CONFIRM_TABLE . ' SET
				' . $db->sql_build_array('UPDATE', $sql_ary) . ",
				attempts = attempts + 1
			WHERE confirm_id = '" . $db->sql_escape($this->confirm_id) . "'
				AND session_id = '" . $db->sql_escape($user->session_id) . "'";
		$db->sql_query($sql);
	}

	/**
	* Look up everything we need and populate the instance variables.
	*/
	function load_code()
	{
		global $db, $user;

		$sql = 'SELECT code, attempts
			FROM ' . CONFIRM_TABLE . "
			WHERE confirm_id = '" . $db->sql_escape($this->confirm_id) . "'
				AND session_id = '" . $db->sql_escape($user->session_id) . "'
				AND confirm_type = " . $this->type;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if ($row)
		{
			$this->code = $row['code'];
			$this->attempts = $row['attempts'];
			return true;
		}

		return false;
	}

	/**
	* The actual validation
	*/
	function check_code()
	{
		return ($this->code == $this->confirm_code);
	}

	/**
	* API function - clean the entry
	*/
	function delete_code()
	{
		global $db, $user;

		$sql = 'DELETE FROM ' . CONFIRM_TABLE . "
			WHERE confirm_id = '" . $db->sql_escape($confirm_id) . "'
				AND session_id = '" . $db->sql_escape($user->session_id) . "'
				AND confirm_type = " . $this->type;
		$db->sql_query($sql);
	}

	/**
	* API function 
	*/
	function get_attempt_count()
	{
		return $this->attempts;
	}

	/**
	* API function 
	*/
	function reset()
	{
		global $db, $user;

		$sql = 'DELETE FROM ' . CONFIRM_TABLE . "
			WHERE session_id = '" . $db->sql_escape($user->session_id) . "'
				AND confirm_type = " . (int) $this->type;
		$db->sql_query($sql);

		// we leave the class usable by generating a new question
		$this->generate_code();
	}

	/**
	* API function 
	*/
	function is_solved()
	{
		//@todo:CONSTANT
		if (request_var('confirm_code', false) && $this->solved === 0)
		{
			$this->validate();
		}
		return (bool) $this->solved;
	}

	/**
	* API function - The ACP backend, this marks the end of the easy methods
	*/
	function acp_page($id, &$module)
	{
		global $user;

		trigger_error($user->lang['CAPTCHA_NO_OPTIONS'] . adm_back_link($module->u_action));
	}

	/**
	* API function - The ACP backend, this marks the end of the easy methods
	*/
	function generate_rand_simplemath()
	{
		global $user;
		$user->add_lang('mods/captcha_simplemath');

		$this->simplemath_operator = $user->lang['operators'][array_rand($user->lang['operators'])];
		$this->simplemath_number1 = $user->lang['numbers'][array_rand($user->lang['numbers'])];
		$this->simplemath_number2 = $user->lang['numbers'][array_rand($user->lang['numbers'])];

		$this->code = $this->simplemath_operator[0]($this->simplemath_number1[0], $this->simplemath_number2[0]);
		unset($this->simplemath_operator[0]);

		$this->simplemath_template = sprintf($user->lang['SIMPLEMATH_TEMPLATE'], $this->simplemath_number1[array_rand($this->simplemath_number1)], $this->simplemath_operator[array_rand($this->simplemath_operator)], $this->simplemath_number2[array_rand($this->simplemath_number2)]);
	}
}

?>