<?php
/**
 *
 * @package sitemaker
 * @copyright (c) 2013 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace blitze\sitemaker\services\menu\action;

class add_bulk extends base_action
{
	public function execute()
	{
		$menu_id = $this->request->variable('menu_id', 0);
		$parent_id = $this->request->variable('parent_id', 0);
		$bulk_list = $this->request->variable('add_list', '', true);

		if (!$menu_id)
		{
			return array('errors' => $this->user->lang('MENU_NOT_FOUND'));
		}

		$item_mapper = $this->mapper_factory->create('menu', 'items');

		return $item_mapper->add_items($menu_id, $parent_id, $bulk_list);
	}
}