<?php
/**
 *
 * @package primetime
 * @copyright (c) 2013 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace primetime\primetime\core\forum;

class manager
{
	/**
	 * Auth object instance
	 * @var \phpbb\auth\auth
	 */
	protected $auth;

	/**
	 * Cache
	 * @var \phpbb\cache\service
	 */
	protected $cache;

	/**
	 * Config
	 * @var \phpbb\config\db
	 */
	protected $config;

	/**
	 * User object
	 * @var \phpbb\user
	 */
	protected $user;

	/** @var string */
	protected $phpbb_root_path = null;

	/** @var string */
	protected $php_ext = null;

	/**
	 * Constructor
	 *
	 * @param \phpbb\auth\auth				$auth				Auth object
	 * @param \phpbb\cache\service			$cache				Cache object
	 * @param \phpbb\config\db				$config				Config object
	 * @param \phpbb\user					$user				User object
	 * @param string						$phpbb_root_path	Path to the phpbb includes directory.
	 * @param string						$php_ext			php file extension
	 */
	public function __construct(\phpbb\auth\auth $auth, \phpbb\cache\driver\driver_interface $cache, \phpbb\config\db $config, \phpbb\user $user, $phpbb_root_path, $php_ext)
	{
		$this->auth = $auth;
		$this->cache = $cache;
		$this->config = $config;
		$this->user = $user;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;

		if (!class_exists('acp_forums'))
		{
			include($this->phpbb_root_path . 'includes/acp/acp_forums.' . $this->php_ext);
		}

		$this->user->add_lang('acp/forums');
		$this->forum = new \acp_forums();
	}

	public function add(&$forum_data)
	{
		$forum_data += array(
			'parent_id'				=> $this->config['primetime_parent_forum_id'],
			'forum_type'			=> FORUM_POST,
			'type_action'			=> '',
			'forum_status'			=> ITEM_UNLOCKED,
			'forum_parents'			=> '',
			'forum_name'			=> '',
			'forum_link'			=> '',
			'forum_link_track'		=> false,
			'forum_desc'			=> '',
			'forum_desc_uid'		=> '',
			'forum_desc_options'	=> 7,
			'forum_desc_bitfield'	=> '',
			'forum_rules'			=> '',
			'forum_rules_uid'		=> '',
			'forum_rules_options'	=> 7,
			'forum_rules_bitfield'	=> '',
			'forum_rules_link'		=> '',
			'forum_image'			=> '',
			'forum_style'			=> 0,
			'display_subforum_list'	=> false,
			'display_on_index'		=> false,
			'forum_topics_per_page'	=> 0,
			'enable_indexing'		=> true,
			'enable_icons'			=> false,
			'enable_prune'			=> false,
			'enable_post_review'	=> true,
			'enable_quick_reply'	=> false,
			'prune_days'			=> 7,
			'prune_viewed'			=> 7,
			'prune_freq'			=> 1,
			'prune_old_polls'		=> false,
			'prune_announce'		=> false,
			'prune_sticky'			=> false,
			'show_active'			=> false,
			'forum_password'		=> '',
			'forum_password_confirm'=> '',
			'forum_password_unset'	=> false,
		);

		$errors = $this->forum->update_forum_data($forum_data);

		if (!sizeof($errors))
		{
			$forum_perm_from = $this->config['primetime_forum_perm_from'];

			// Copy permissions?
			if (!empty($forum_perm_from) && $forum_perm_from != $forum_data['forum_id'])
			{
				copy_forum_permissions($forum_perm_from, $forum_data['forum_id'], false, false);
				cache_moderators();
			}

			$this->auth->acl_clear_prefetch();
			$this->cache->destroy('sql', FORUMS_TABLE);
		}

		return $errors;
	}

	function remove($forum_id, $action_posts = 'delete', $action_subforums = 'delete', $posts_to_id = 0, $subforums_to_id = 0)
	{
		$this->forum->delete_forum($forum_id, $action_posts, $action_subforums, $posts_to_id, $subforums_to_id);
	}
}
