<?php

/***************************************************************************
 *
 *	Newpoints Promotion System Integration plugin (/inc/plugins/newpoints/newpoints_promotion.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2014-2015 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Allows administrators to set minimum newpoints points for the promotion system.
 *
 ***************************************************************************

****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Add the hooks we are going to use.
if(defined("IN_ADMINCP"))
{
	// Insert form data hook.
	$plugins->add_hook('admin_formcontainer_output_row', 'newpoints_promotion_formcontainer_output_row');

	// Save data hook.
	$plugins->add_hook('admin_user_group_promotions_edit_commit', 'newpoints_promotion_commit');
	$plugins->add_hook('admin_user_group_promotions_add_commit', 'newpoints_promotion_commit');
}

$plugins->add_hook('task_promotions', 'newpoints_promotion_task');

/*** Newpoints ACP side. ***/
function newpoints_promotion_info()
{
	global $lang;
	isset($lang->newpoints_promotion) or newpoints_lang_load("newpoints_promotion");

	return array(
		'name'			=> 'Newpoints Promotion System Integration',
		'description'	=> $lang->newpoints_promotion_desc,
		'website'		=> 'http://omarg.me',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://omarg.me',
		'version'		=> '2.0',
		'versioncode'	=> 2000,
		'compatibility'	=> '2*'
	);
}

// _activate() routine
function newpoints_promotion_activate()
{
	global $PL, $lang, $cache;

	// Insert/update version into cache
	$plugins = $cache->read('ougc_plugins');
	if(!$plugins)
	{
		$plugins = array();
	}

	$info = newpoints_promotion_info();

	if(!isset($plugins['newpoints_promotion']))
	{
		$plugins['newpoints_promotion'] = $info['versioncode'];
	}

	/*~*~* RUN UPDATES START *~*~*/

	/*~*~* RUN UPDATES END *~*~*/

	$plugins['newpoints_promotion'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);
}

// Installed the plugin.
function newpoints_promotion_install()
{
	global $db;

	if(!$db->field_exists("newpoints", "promotions"))
	{
		$db->add_column("promotions", "newpoints", "int NOT NULL default '0'");
	}
	if(!$db->field_exists("newpointstype", "promotions"))
	{
		$db->add_column("promotions", "newpointstype", "char(2) NOT NULL default ''");
	}
}

// Uninstall the plugin.
function newpoints_promotion_uninstall()
{
	global $db, $cache;
	if($db->field_exists("newpoints", "promotions"))
	{
		$db->drop_column("promotions", "newpoints");
	}
	if($db->field_exists("newpointstype", "promotions"))
	{
		$db->drop_column("promotions", "newpointstype");
	}

	// Delete version from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['newpoints_promotion']))
	{
		unset($plugins['newpoints_promotion']);
	}

	if(!empty($plugins))
	{
		$cache->update('ougc_plugins', $plugins);
	}
	else
	{
		$cache->delete('ougc_plugins');
	}
}

// Check if this plugin is installed.
function newpoints_promotion_is_installed()
{
	global $db;

	return $db->field_exists("newpoints", "promotions");
}

/*** Lets do it... ***/
// Output the form if we are in the right place.
function newpoints_promotion_formcontainer_output_row(&$args)
{
	global $run_module, $form_container, $mybb, $db, $lang, $form, $options, $options_type, $promotion;

	if(!($run_module == 'user' && !empty($form_container->_title) && $mybb->get_input('module') == 'user-group_promotions' && in_array($mybb->get_input('action'), array('add', 'edit'))))
	{
		return;
	}

	isset($lang->newpoints_promotion) or newpoints_lang_load("newpoints_promotion");

	if($args['label_for'] == 'requirements')
	{
		$options['newpoints'] = $lang->newpoints_promotion;
		$args['content'] = $form->generate_select_box('requirements[]', $options, $mybb->input['requirements'], array('id' => 'requirements', 'multiple' => true, 'size' => 5));
	}

	if($args['label_for'] == 'timeregistered')
	{
		if($mybb->get_input('pid', 1) && !isset($mybb->input['newpoints']))
		{
			$newpoints = $promotion['newpoints'];
			$newpointstype = $promotion['newpointstype'];
		}
		else
		{
			$newpoints = $mybb->get_input('newpoints');
			$newpointstype = $mybb->get_input('newpointstype');
		}

		$form_container->output_row($lang->setting_newpoints_promotion, $lang->setting_newpoints_promotion_desc, $form->generate_numeric_field('newpoints', (int)$newpoints, array('id' => 'newpoints'))." ".$form->generate_select_box("newpointstype", $options_type, $newpointstype, array('id' => 'newpointstype')), 'newpoints');
	}
}

// Save our data.
function newpoints_promotion_commit()
{
	global $db, $mybb, $pid, $update_promotion, $pid;

	is_array($update_promotion) or $update_promotion = array();

	$update_promotion['newpoints'] = $mybb->get_input('newpoints', 1);
	$update_promotion['newpointstype'] = $db->escape_string($mybb->get_input('newpointstype'));

	if($mybb->get_input('action') == 'add')
	{
		$db->update_query('promotions', $update_promotion, "pid='{$pid}'");
	}
}

// Task hook
function newpoints_promotion_task(&$args)
{
	if(in_array('newpoints', explode(',', $args['promotion']['requirements'])) && (int)$args['promotion']['newpoints'] >= 0 && !empty($args['promotion']['newpointstype']))
	{
		$args['sql_where'] .= "{$args['and']}newpoints{$args['promotion']['newpointstype']}'{$args['promotion']['newpoints']}'";
		$args['and'] = ' AND ';
	}
}