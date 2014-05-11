<?php

/***************************************************************************
 *
 *   Newpoints Promotion System Integration plugin (/inc/plugins/newpoints/newpoints_lottery.php)
 *	 Author: Sama34 (Omar G.)
 *   
 *   Website: http://udezain.com.ar
 *
 *   Allows administrators to set minimum newpoints points for the promotion system.
 *
 ***************************************************************************/

/****************************************************************************
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
if(defined("IN_ADMINCP") && newpoints_promotion_is_installed())
{
	// Insert form data hook.
	$plugins->add_hook('admin_formcontainer_end', 'newpoints_promotion_formcontainer');

	// Save data hook.
	$plugins->add_hook('admin_user_group_promotions_edit_commit', 'newpoints_promotion_commit');
	$plugins->add_hook('admin_user_group_promotions_add_commit', 'newpoints_promotion_commit');

	// Insert requirements array hooks.
	$plugins->add_hook('admin_user_group_promotions_edit', 'newpoints_promotion_requirements');
	$plugins->add_hook('admin_user_group_promotions_add', 'newpoints_promotion_requirements');

	// Patch core file hook.
	$plugins->add_hook('newpoints_admin_plugins_start', 'newpoints_promotion_edit');
}

/*** Newpoints ACP side. ***/
function newpoints_promotion_info()
{
	global $lang, $cache, $mybb;
	newpoints_lang_load("newpoints_promotion");
	$plugins = $cache->read("newpoints_plugins");

	$info =  array(
		'name'			=> $lang->promotion_plugin_n,
		'description'	=> $lang->promotion_plugin_d,
		'website'		=> 'http://udezain.com.ar',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://udezain.com.ar',
		'version'		=> '1.0 BETA',
		'compatibility'	=> '16*'
	);
	// Provide some additional status information, if the plugin is enabled.
    if(newpoints_promotion_is_installed() && is_array($plugins) && is_array($plugins['active']) && $plugins['active']['newpoints_promotion'])
    {
        /*global $PL;
        $PL or require_once PLUGINLIBRARY;
		 What is this for? IDK.
        $editurl = $PL->url_append("index.php?module=newpoints-plugins",
                                   array("newpoints_promotion" => "edit",
                                         "my_post_key" => $mybb->post_code));
        $undourl = $PL->url_append("index.php",
                                   array("module" => "newpoints-plugins",
                                         "newpoints_promotion" => "undo",
                                         "my_post_key" => $mybb->post_code));*/

        $editurl = "index.php?module=newpoints-plugins&amp;newpoints_promotion=edit&amp;my_post_key=".$mybb->post_code;
        $undourl = "index.php?module=newpoints-plugins&amp;newpoints_promotion=undo&amp;my_post_key=".$mybb->post_code;

        $info["description"] .= "<br /><a href=\"{$editurl}\">{$lang->promotion_applyedits}</a>. | <a href=\"{$undourl}\">{$lang->promotion_undoedits}</a>.";
    }
	return $info;
}

// Installed the plugin.
function newpoints_promotion_install()
{
	global $lang;
	newpoints_lang_load("newpoints_promotion");

	// Lets check if PluginLibrary is installed, if not, show friendly error.
	if(!file_exists(PLUGINLIBRARY))
    {
        flash_message($lang->promotion_pl_missing, "error");
        admin_redirect("index.php?module=newpoints-plugins");
    }

	global $db, $PL;
	$PL or require_once PLUGINLIBRARY;

	// Lets check PluginLibrary version, if less than 6, show friendly error.
	if($PL->version < 6)
	{
		flash_message($lang->promotion_pl_tooold, "error");
		admin_redirect("index.php?module=newpoints-plugins");
	}
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
	global $db;
	if($db->field_exists("newpoints", "promotions"))
	{
		$db->drop_column("promotions", "newpoints");
	}
	if($db->field_exists("newpointstype", "promotions"))
	{
		$db->drop_column("promotions", "newpointstype");
	}
}

// Check if this plugin is installed.
function newpoints_promotion_is_installed()
{
	global $db;
	if($db->field_exists("newpoints", "promotions") || $db->field_exists("newpointstype", "promotions"))
	{
		return true;
	}
	return false;
}

/*** Lets do it... ***/
// Output the form if we are in the right place.
function newpoints_promotion_formcontainer()
{
	global $run_module, $form_container, $mybb;
	if($run_module == 'user' && !empty($form_container->_title) && $mybb->input['module'] == 'user-group_promotions' && in_array($mybb->input['action'], array('add', 'edit')))
	{
		global $db, $lang, $form, $options_type;
		newpoints_lang_load("newpoints_promotion");
		if(intval($mybb->input['pid']))
		{
			$q = $db->fetch_array($db->simple_select('promotions', 'newpoints,newpointstype', "pid='{$mybb->input['pid']}'"));
			$newpoints = $q['newpoints'];
			$newpointstype = $q['newpointstype'];
			unset($q);
		}
		else
		{
			$newpoints = $mybb->input['newpoints'];
			$newpointstype = $mybb->input['newpointstype'];
		}
		$form_container->output_row($lang->promotion_plugin_setting_n, $lang->promotion_plugin_setting_d, $form->generate_text_box('newpoints', intval($newpoints), array('id' => 'newpoints'))." ".$form->generate_select_box("newpointstype", $options_type, $newpointstype, array('id' => 'newpointstype')), 'newpoints');
	}
}

// Save our data.
function newpoints_promotion_commit()
{
	global $db, $mybb;
	$data = array(
		"newpoints" => intval($mybb->input['newpoints']),
		"newpointstype" => $db->escape_string($mybb->input['newpointstype']),
	);
	$db->update_query("promotions", $data, "pid='".intval($mybb->input['pid'])."'");
}

// We need to add 'newpoints' as a requirement automatically if '0' is not entered.
function newpoints_promotion_requirements()
{
	global $mybb;
	if($mybb->request_method == "post" && $mybb->input['newpoints'])
	{
		$mybb->input['requirements'][] = 'newpoints';
	}
}

// Save the data.
function newpoints_promotion_edit()
{
    global $mybb;

    // Only perform edits if we were given the correct post key.
    if($mybb->input['my_post_key'] != $mybb->post_code)
    {
        return;
    }

    global $PL;
    $PL or require_once PLUGINLIBRARY;
	
	// Edit the core depending in input.
    if($mybb->input['newpoints_promotion'] == 'edit' || $check == true)
    {
		$result = $PL->edit_core("newpoints_promotion", "inc/tasks/promotions.php",
			array(
				'search' => array("if(in_array('postcount',"),
				'before' => array(
					'if(in_array(\'newpoints\', $requirements) && intval($promotion[\'newpoints\']) >= 0 && !empty($promotion[\'newpointstype\']))',
					'{',
					'	$sql_where .= "{$and}newpoints {$promotion[\'newpointstype\']} \'{$promotion[\'newpoints\']}\'";',
					'	$and = " AND ";',
					'}',
				),
			),
			true
		);
	}
    elseif($mybb->input['newpoints_promotion'] == 'undo')
    {
		$result = $PL->edit_core(
			"newpoints_promotion", "inc/tasks/promotions.php",
			array(),
			true
		);
    }
    else
    {
		return;
    }

	global $lang;
	newpoints_lang_load("newpoints_promotion");

	// Apply the edit...
    if($result === true)
    {
        // redirect with success
        flash_message($lang->promotion_pl_edited, "success");
        admin_redirect("index.php?module=newpoints-plugins");
    }

    else
    {
        // redirect with failure (could offer the result string for download instead)
        flash_message($lang->promotion_pl_error, "error");
        admin_redirect("index.php?module=newpoints-plugins");
    }
}
?>