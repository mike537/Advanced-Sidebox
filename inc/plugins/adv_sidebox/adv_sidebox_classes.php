<?php
/*
 * This file contains classes for Advanced Sidebox
 */

class Sidebox
{
	public $id;
	public $display_name;
	public $box_type;
	public $position = 0;
	public $display_order;
	public $stereo = false;
	public $content;

	public $valid = false;

	public $show_on_index = false;
	public $show_on_forumdisplay = false;
	public $show_on_showthread = false;
	public $show_on_portal = false;

	/*
	 * __construct() called upon creation
	 *
	 * @param - $sidebox can be an array fetched from db,
	 * 						a valid ID # or
	 *						left blank to create a new sidebox
	 */
	function __construct($sidebox = 0)
	{
		// try to load the sidebox
		$this->load($sidebox);

		// if nothing loaded then the sidebox is new (and unavailable for admin to use)
		if($this->content)
		{
			$this->valid = true;
		}
	}

	/*
	 * load()
	 *
	 * can be called on an existing object with or without data
	 *
	 * @param - $data can be an array fetched from the db or
	 *						a valid ID #
	 *
	 * if this sidebox is undefined (or bad data was received), do nothing
	 */
	function load($data)
	{
		global $db;

		// if data isn't an array, try it as an ID
		if(!is_array($data))
		{
			//if the ID is 0 then there is nothing to go on
			if((int) $data)
			{
				// otherwise check the db
				$this_query = $db->simple_select('sideboxes', '*', "id='{$data}'");

				// if it exists
				if($db->num_rows($this_query))
				{
					// store the data
					$data = $db->fetch_array($this_query);
				}
			}
		}

		// ID = 0 means nothing to do
		if($data['id'])
		{
			// good id? then store the data in our object
			$this->id = (int) $data['id'];
			$this->display_name = $data['display_name'];
			$this->box_type = $data['box_type'];
			$this->position = (int) $data['position'];
			$this->display_order = (int) $data['display_order'];
			$this->stereo = (int) $data['stereo'];

			$this->show_on_index = $data['show_on_index'];
			$this->show_on_forumdisplay = $data['show_on_forumdisplay'];
			$this->show_on_showthread = $data['show_on_showthread'];
			$this->show_on_portal = $data['show_on_portal'];

			$this->stereo = $data['stereo'];

			// stereo boxes get a little special consideration
			if($this->stereo)
			{
				// split the template variable into two channels
				if($this->position)
				{
					$this->content = '{$' . $this->box_type . '_r}';
				}
				else
				{
					$this->content = '{$' . $this->box_type . '_l}';
				}
			}
			else
			{
				// non-stereo still leaves the possibility of custom boxes
				if($this->box_type == 'custom_box')
				{
					// use their content as the replacement instead of a variable
					$this->content = $data['content'];
				}
				else
				{
					// otherwise just beuild a template variable for this sidebox
					$this->content = '{$' . $this->box_type . '}';
				}
			}
		}
	}

	/*
	 * save()
	 *
	 * can be called upon any existing sidebox
	 */
	function save()
	{
		global $db;

		// set up db array
		$this_box = array(
			"display_name"				=>	$db->escape_string($this->display_name),
			"box_type"						=>	$db->escape_string($this->box_type),
			"position"							=>	(int) $this->position,
			"display_order"					=> 	(int) $this->display_order,
			"stereo"							=>	(int) $this->stereo,
			"content"							=>	$db->escape_string($this->content),
			"show_on_index"				=>	(int) $this->show_on_index,
			"show_on_forumdisplay"	=>	(int) $this->show_on_forumdisplay,
			"show_on_showthread"	=>	(int) $this->show_on_showthread,
			"show_on_portal"				=>	(int) $this->show_on_portal
		);

		// ID means update an existing box
		if($this->id > 0)
		{
			$status = $db->update_query('sideboxes', $this_box, "id='" . (int) $this->id . "'");
		}
		else
		{
			// otherwise insert a new box
			$status = $db->insert_query('sideboxes', $this_box);
		}

		return $status;
	}

	/*
	 * build_table_row()
	 *
	 * can be called on any exisiting sidebox object
	 *
	 * @param - $this_table must be a valid object of class Table
	 */
	function build_table_row($this_table)
	{
		global $mybb, $lang;

		if (!$lang->adv_sidebox)
		{
			$lang->load('adv_sidebox');
		}

		if($this_table instanceof Table)
		{
			// construct the table row.
			$this_table->construct_cell($this->display_name, array("width" => '10%'));
			$this_table->construct_cell($this->build_script_list(), array("width" => '10%'));
			$this_table->construct_cell('<a href="' . ADV_SIDEBOX_EDIT_URL . '&amp;mode=' . $mybb->input['mode'] . '&amp;box=' . $this->id . '"><img src="' . $mybb->settings['bburl'] . '/images/icons/pencil.gif" alt="' . $lang->adv_sidebox_edit . '" title="' . $lang->adv_sidebox_edit . '" />&nbsp;' . $lang->adv_sidebox_edit . '</a>', array("width" => '10%'));
			$this_table->construct_cell('<a href="' . ADV_SIDEBOX_DEL_URL . '&amp;mode=' . $mybb->input['mode'] . '&amp;box=' . $this->id . '"><img src="' . $mybb->settings['bburl'] . '/images/usercp/delete.png" alt="' . $lang->adv_sidebox_edit . '" title="' . $lang->adv_sidebox_edit . '" />&nbsp;' . $lang->adv_sidebox_delete . '</a>', array("width" => '10%'));
			$this_table->construct_row();
		}
	}

	/*
	 * build_script_list()
	 *
	 * builds a comma seperated list of scripts that this sidebox will display on, 'All Scripts' if all, a single name if 1, nothing if none.
	 */
	function build_script_list()
	{
		// if all scripts be brief
		if($this->show_on_index && $this->show_on_forumdisplay && $this->show_on_showthread && $this->show_on_portal)
		{
			return 'All Scripts';
		}
		else
		{
			// otherwise, break it down
			$script_list = array();

			if($this->show_on_index)
			{
				$script_list[] = 'Index';
			}

			if($this->show_on_forumdisplay)
			{
				$script_list[] = 'Forum';
			}

			if($this->show_on_showthread)
			{
				$script_list[] = 'Thread';
			}

			if($this->show_on_portal)
			{
				$script_list[] = 'Portal';
			}
			// return a comma space separated list
			return implode(", ", $script_list);
		}
	}

	/*
	 * remove()
	 *
	 * removes the sidebox from the database
	 */
	function remove()
	{
		// if this is a valid module
		if($this->id)
		{
			global $db;

			// attempt to delete it and return the result
			return $db->query("DELETE FROM " . TABLE_PREFIX . "sideboxes WHERE id='" . (int) $this->id . "'");
		}
	}
}

/*
 * wrapper for modules
 */
class Sidebox_addon
{
	public $base_name = '';
	public $name = '';
	public $description = '';
	public $stereo = false;
	public $valid = false;
	public $module_type;

	public $is_installed = false;

	/*
	 * __construct()
	 *
	 * called upon creation. loads module if possible and attempts to validate
	 */
	function __construct($module)
	{
		// no input, no go
		if($module)
		{
			$this->load($module);
		}
	}

	/*
	 * load()
	 *
	 * attempts to load a module by name.
	 */
	function load($module)
	{
		// input is necessary
		if($module)
		{
			// if the directory exists, it isn't . or .. and it contains a valid module file . . .
			if(is_dir(ADV_SIDEBOX_MODULES_DIR . "/" . $module) && !in_array($module, array(".", "..")) && file_exists(ADV_SIDEBOX_MODULES_DIR . "/" . $module . "/adv_sidebox_module.php"))
			{
				// require the module for inspection/info
				require_once ADV_SIDEBOX_MODULES_DIR . "/" . $module . "/adv_sidebox_module.php";

				// if the info function exists . . .
				if(function_exists($module . '_asb_info'))
				{
					// get the data
					$info_function = $module . '_asb_info';
					$this_info = $info_function();

					// validate and store data
					$this->valid = true;
					$this->base_name = $module;
					$this->name = $this_info['name'];
					$this->description = $this_info['description'];

					if($this_info['stereo'])
					{
						$this->stereo = true;
					}

					// if the is_installed() function exists
					if(function_exists($module . '_asb_is_installed'))
					{
						// check whether it is installed and flag it complex
						$is_installed_function = $module . '_asb_is_installed';

						$this->is_installed = $is_installed_function();
						$this->module_type = 'complex';
					}
					else
					{
						// otherwise it is a simple module
						$this->module_type = 'simple';
						$this->is_installed = false;
					}
				}
				else
				{
					// bad module
					$this->valid = false;
				}
			}
			else
			{
				$this->valid = false;
			}
		}
	}

	/*
	 * build_template()
	 *
	 * runs template building code for the current module referenced by this object
	 */
	function build_template()
	{
		// if the files are intact . . .
		if(file_exists(ADV_SIDEBOX_MODULES_DIR . "/" . $this->base_name . "/adv_sidebox_module.php"))
		{
			// . . . run the module's template building code.
			require_once ADV_SIDEBOX_MODULES_DIR . "/" . $this->base_name . "/adv_sidebox_module.php";

			if(function_exists($this->base_name . '_asb_build_template'))
			{
				$build_template_function = $this->base_name . '_asb_build_template';
				$build_template_function();
			}
		}
	}

	/*
	 * build_table_row()
	 *
	 * ACP module management page function to build a table row for the current sidebox object
	 *
	 * @param - $this_table must be a valid object of the Table class
	 */
	function build_table_row($this_table)
	{
		global $mybb, $lang;

		if (!$lang->adv_sidebox)
		{
			$lang->load('adv_sidebox');
		}

		$this_table->construct_cell($this->name);
		$this_table->construct_cell($this->description);

		// complex modules get install/uninstall links
		if($this->module_type == 'complex')
		{
			// installed?
			if($this->is_installed)
			{
				// uninstall link
				$this_table->construct_cell('<a href="' . ADV_SIDEBOX_URL . '&amp;action=uninstall_addon&amp;addon=' . $this->base_name . '"><img src="' . $mybb->settings['bburl'] . '/inc/plugins/adv_sidebox/images/delete.png" />&nbsp;' . $lang->adv_sidebox_uninstall . '</a>');
			}
			else
			{
				// install link
				$this_table->construct_cell('<a href="' . ADV_SIDEBOX_URL . '&amp;action=install_addon&amp;addon=' . $this->base_name . '"><img src="' . $mybb->settings['bburl'] . '/inc/plugins/adv_sidebox/images/new.png" />&nbsp;' . $lang->adv_sidebox_install . '</a>');
			}
		}
		else
		{
			// simple modules can't install/uninstall
			$this_table->construct_cell('');
		}

		// delete link
		$this_table->construct_cell('<a href="' . ADV_SIDEBOX_URL . '&amp;action=delete_addon&amp;addon=' . $this->base_name . '" onclick="return confirm(\'' . $lang->adv_sidebox_modules_del_warning . '\');"><img src="' . $mybb->settings['bburl'] . '/images/invalid.gif" />&nbsp;' . $lang->adv_sidebox_delete . '</a>');
		$this_table->construct_row();
	}

	/*
	 * install()
	 *
	 * access the given modules install routine
	 */
	function install()
	{
		// only complex modules can install/uninstall
		if($this->module_type == 'complex')
		{
			// already installed?
			if($this->is_installed)
			{
				// remove the leftovers before installing
				$status = $this->uninstall();
			}

			// validate the module
			if(is_dir(ADV_SIDEBOX_MODULES_DIR . "/" . $this->base_name) && !in_array($this->base_name, array(".", "..")) && file_exists(ADV_SIDEBOX_MODULES_DIR . "/" . $this->base_name . "/adv_sidebox_module.php"))
			{
				require_once ADV_SIDEBOX_MODULES_DIR . "/" . $this->base_name . "/adv_sidebox_module.php";

				// and if the install routine exists, run it
				if(function_exists($this->base_name . '_asb_install'))
				{
					$install_function = $this->base_name . '_asb_install';
					$install_function();
				}
			}
		}
	}

	/*
	 * uninstall()
	 *
	 * access the given module's uninstall routine
	 */
	function uninstall()
	{
		// only complex modules can be installed/uninstalled
		if($this->module_type == 'complex')
		{
			// installed?
			if($this->is_installed)
			{
				// validate the module
				if(is_dir(ADV_SIDEBOX_MODULES_DIR . "/" . $this->base_name) && !in_array($this->base_name, array(".", "..")) && file_exists(ADV_SIDEBOX_MODULES_DIR . "/" . $this->base_name . "/adv_sidebox_module.php"))
				{
					require_once ADV_SIDEBOX_MODULES_DIR . "/" . $this->base_name . "/adv_sidebox_module.php";

					// and uninstall it if possible
					if(function_exists($this->base_name . '_asb_uninstall'))
					{
						$uninstall_function = $this->base_name . '_asb_uninstall';
						$uninstall_function();
					}
				}
			}
		}
	}

	/*
	 * remove()
	 *
	 * uninstalls (if necessary) and physically deletes the module from the server
	 */
	function remove()
	{
		// make sure no trash is left behind
		$this->uninstall();

		// nuke it
		my_rmdir_recursive(ADV_SIDEBOX_MODULES_DIR . "/" . $this->base_name);
		rmdir(ADV_SIDEBOX_MODULES_DIR . "/" . $this->base_name);
	}
}

?>
