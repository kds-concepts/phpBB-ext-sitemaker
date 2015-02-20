<?php
/**
 *
 * @package primetime
 * @copyright (c) 2013 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace primetime\core\blocks;

/**
 * Custom Block
 */
class custom extends \primetime\core\services\blocks\driver\block
{
	/** @var \phpbb\cache\service */
	protected $cache;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/**
	 * Constructor
	 *
	 * @param \phpbb\cache\service					$cache				Cache object
	 * @param \phpbb\db\driver\driver_interface		$db					Database object
	 * @param \phpbb\user							$user				User object
	 */
	public function __construct(\phpbb\cache\service $cache, \phpbb\db\driver\driver_interface $db, \phpbb\user $user)
	{
		$this->cache = $cache;
		$this->db = $db;
		$this->user = $user;
	}

	public function save($id, $content)
	{
		// Delete block data
		$this->db->sql_query('DELETE FROM ' . PT_CUSTOM_BLOCKS_TABLE . ' WHERE block_id = ' . (int) $id);

		$sql_data =	array(
			'block_id'			=> $id,
			'block_content'		=> $content,
			'bbcode_bitfield'	=> '',
			'bbcode_options'	=> 7,
			'bbcode_uid'		=> '',
		);

		generate_text_for_storage($sql_data['block_content'], $sql_data['bbcode_uid'], $sql_data['bbcode_bitfield'], $sql_data['bbcode_options'], true, true, true);

		$this->db->sql_query('INSERT INTO ' . PT_CUSTOM_BLOCKS_TABLE . ' ' . $this->db->sql_build_array('INSERT', $sql_data));
		$this->cache->destroy('pt_cblocks');

		return array(
			'id'		=> $id,
			'content'	=> $this->parse($sql_data),
			'callback'	=> 'previewCustomBlock',
		);
	}

	public function display($bdata, $edit_mode = false)
	{
		// As this content is not expected to change frequently, we cache it
		if (($cblock = $this->cache->get('pt_cblocks')) === false)
		{
			$sql = 'SELECT *
				FROM ' . PT_CUSTOM_BLOCKS_TABLE;
			$result = $this->db->sql_query($sql);

			$cblock = array();
			while ($row = $this->db->sql_fetchrow($result))
			{
				$cblock[$row['block_id']] = $row;
			}
			$this->db->sql_freeresult($result);

			$this->cache->put($cblock, 'pt_cblocks');
		}

		$content = '';
		if (isset($cblock[$bdata['bid']]))
		{
			$cblock = $cblock[$bdata['bid']];
			$content = $this->parse($cblock);
		}
		else
		{
			$cblock = array(
				'block_id'		=> $bdata['bid'],
				'block_content'	=> '',
				'bbcode_uid'	=> '',
			);
		}

		if ($edit_mode !== false)
		{
			decode_message($cblock['block_content'], $cblock['bbcode_uid']);
			$content = '<div id="block-editor-' . $cblock['block_id'] . '" class="editable-block" data-service="primetime.core.blocks.html" data-method="save" data-raw="' . $cblock['block_content'] . '">' . $content . '</div>';
		}

		return array(
			'title'		=> 'BLOCK_TITLE',
			'content'	=> $content,
		);
	}

	private function parse($data)
	{
		$content = generate_text_for_display($data['block_content'], $data['bbcode_uid'], $data['bbcode_bitfield'], $data['bbcode_options']);
		return html_entity_decode($content);
	}
}