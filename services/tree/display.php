<?php
/**
 *
 * @package primetime
 * @copyright (c) 2013 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace primetime\base\services\tree;

/**
* Display nested sets
*/
abstract class display
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	protected $items_table;
	protected $pk;
	protected $primetime;
	protected $errors = array();
	protected $data = array();
	protected $sql_where;

	/**
	* Construct
	*
	* @param \phpbb\db\driver\driver_interface		$db             Database connection
	* @param \primetime\base\services\util			$primetime		Primetime object
	* @param string									$table_name		Table name
	* @param string									$pk				Primary key
	* @param string									$sql_where		Column restriction
	*/
	public function __construct(\phpbb\db\driver\driver_interface $db, \primetime\base\services\util $primetime, $table, $pk, $sql_where = '')
	{
		$this->db = $db;
		$this->pk = $pk;
		$this->items_table = $table;
		$this->sql_where = $sql_where;
		$this->primetime = $primetime;
	}

	/**
	 * Is subject node an ancestor of the object node?
	 */
	public function is_in_path($object, $subject)
	{
		return ($subject['left_id'] < $object['left_id'] && $subject['right_id'] > $object['right_id']) ? true : false;
	}

	/**
	 * Count node descendants
	 */
	public function count_descendants($row)
	{
		return ($row['right_id'] - $row['left_id'] - 1) / 2;
	}

	/**
	 * Get node row
	 */
	public function get_row($node_id)
	{
		if (isset($this->data[$node_id]))
		{
			return $this->data[$node_id];
		}

		$sql = "SELECT *
			FROM $this->items_table
			WHERE $this->pk = " . (int) $node_id .
				(($this->sql_where) ? ' AND ' . $this->sql_where : '');
		$result = $this->db->sql_query($sql);
		$this->data[$node_id] = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $this->data[$node_id];
	}

	/**
	* Get branch
	*/
	public function get_branch($node_id, $type = 'all', $order = 'descending', $include_node = true)
	{
		switch ($type)
		{
			case 'parents':
				$condition = 'n1.left_id BETWEEN n2.left_id AND n2.right_id';
			break;

			case 'children':
				$condition = 'n2.left_id BETWEEN n1.left_id AND n1.right_id';
			break;

			default:
				$condition = '(n2.left_id BETWEEN n1.left_id AND n1.right_id OR n1.left_id BETWEEN n2.left_id AND n2.right_id)';
			break;
		}

		$rows = array();
		$condition .= ($this->sql_where) ? ' AND n2.' . $this->sql_where : '';

		$sql = "SELECT n2.*
			FROM $this->items_table n1
			LEFT JOIN $this->items_table n2 ON ($condition)
			WHERE n1.{$this->pk} = " . (int) $node_id .
				(($this->sql_where) ? ' AND n1.' . $this->sql_where : '') . '
			ORDER BY n2.left_id ' . (($order == 'descending') ? 'ASC' : 'DESC');
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			if (!$include_node && $row[$this->pk] == $node_id)
			{
				continue;
			}

			$rows[$row[$this->pk]] = $row;
		}
		$this->db->sql_freeresult($result);

		return $rows;
	}

	public function qet_tree_sql($start = 0, $level = false, $sql_array = array())
	{
		$sql_query = array(
			'SELECT'	=> 't.*',
			'FROM'		=> array(
				$this->items_table => ' t'
			),
			'WHERE'		=> 't.depth ' . (($level) ? " BETWEEN $start AND " . ($start + $level) : ' >= ' . $start) .
							(($this->sql_where) ? ' AND t.' . $this->sql_where : ''),
			'ORDER_BY'	=> 't.left_id ASC'
		);

		return $this->db->sql_build_query('SELECT', $this->primetime->merge_dbal_arrays($sql_query, $sql_array));
	}

	public function get_tree_array($start = 0, $level = false, $sql_array = array())
	{
		$sql = $this->qet_tree_sql($start, $level, $sql_array);
		$result = $this->db->sql_query($sql);

		$data = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$data[$row[$this->pk]] = $row;
		}
		$this->db->sql_freeresult($result);

		return $data;
	}

	public function display_list($data, &$template, $handle = 'tree')
	{
		$prev_depth = 0;
		$parental_depth = array(0 => -1);
		$data = array_values($data);

		for ($i = 0, $size = sizeof($data); $i < $size; $i++)
		{
			$row 		= $data[$i];
			$this_depth	= $parental_depth[$row['parent_id']] + 1;
			$repeat		= abs($prev_depth - $this_depth);

			$tpl_data	= array(
				'S_PREV_DEPTH'	=> $prev_depth,
				'S_THIS_DEPTH'	=> $this_depth,
				'S_NUM_KIDS'	=> $this->count_descendants($row),
			);

			$template->assign_block_vars($handle, array_merge($tpl_data, array_change_key_case($row, CASE_UPPER)));

			for ($j = 0; $j < $repeat; $j++)
			{
				$template->assign_block_vars($handle . '.close', array());
			}

			$prev_depth = $this_depth;
			$parental_depth[$row[$this->pk]] = $this_depth;
		}

		for ($i = 0; $i < $prev_depth; $i++)
		{
			$template->assign_block_vars('close_' . $handle, array());
		}
	}

	/**
	 * Get tree as form options or data
	 *
	 * @param	array	$db_data	Raw tree data from database
	 * @param	string	$title_column	Database column name to use as label/title for each item
	 * @param	array	$selected_ids	Array of selected items
	 * @param	string	$return_mode	options | data
	 * @param	string	$pad_with		Character used to denote nesting
	 *
	 * @return	mixed	Returns array of padded titles or html string of options depending on $return_mode
	 */
	public function display_options($db_data, $title_column, $selected_ids = array(), $return_mode = 'options', $pad_with = '&nbsp;&nbsp;&nbsp;&nbsp;')
	{
		$right = 0;
		$padding = '';
		$return_options = '';
		$return_data = array();
		$padding_store = array('0' => '');

		$db_data = array_values($db_data);
		for ($i = 0, $size = sizeof($db_data); $i < $size; $i++)
		{
			$row = $db_data[$i];

			if ($row['left_id'] < $right)
			{
				$padding .= $pad_with;
				$padding_store[$row['parent_id']] = $padding;
			}
			else if ($row['left_id'] > $right + 1)
			{
				$padding = (isset($padding_store[$row['parent_id']])) ? $padding_store[$row['parent_id']] : '';
			}

			$right = $row['right_id'];
			$title = $padding . '&#x251c;&#x2500; ' . $row[$title_column];
			$selected = (in_array($row[$this->pk], $selected_ids)) ? ' selected="selected' : '';

			$return_options .= '<option value="' . $row[$this->pk] . '"' . $selected . '>' . $title . '</option>';
			$return_data[$row[$this->pk]] = $title;
		}

		return ($return_mode == 'options') ? $return_options : $return_data;
	}

	public function set_sql_condition($where)
	{
		$this->sql_where = $where;
	}

	/**
	 * Return errors
	 * 
	 * @return array
	 */
	public function get_errors()
	{
		return $this->errors;
	}

	public function reset_data()
	{
		$this->data = array();
	}
}