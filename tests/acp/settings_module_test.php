<?php
/**
 *
 * @package sitemaker
 * @copyright (c) 2015 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace blitze\sitemaker\tests\acp;

use phpbb\request\request_interface;
use blitze\sitemaker\acp\settings_module;

require_once dirname(__FILE__) . '../../../../../../includes/functions_acp.php';

class settings_module_test extends \phpbb_database_test_case
{
	protected $config;
	protected $config_text;
	protected $template;
	protected $icon_picker;
	protected $util;
	protected $php_ext;

	static private $helper;
	protected $config_path;

	static public function setUpBeforeClass()
	{
		global $phpbb_root_path;

		parent::setUpBeforeClass();

		self::$helper = new \phpbb_test_case_helpers(__CLASS__);

		self::$helper->copy_dir($phpbb_root_path . 'ext/blitze/sitemaker/tests/acp/fixtures/ext/foo/bar', $phpbb_root_path . 'ext/foo/bar');
	}

	static public function tearDownAfterClass()
	{
		global $phpbb_root_path;

		parent::tearDownAfterClass();

		self::$helper->empty_dir($phpbb_root_path . 'ext/foo');
		rmdir($phpbb_root_path . 'ext/foo');
	}

	protected function setUp(): void
	{
		global $phpbb_root_path;

		parent::setUp();

		copy($phpbb_root_path . 'ext/blitze/sitemaker/tests/acp/fixtures/filemanager/test_config.php', $phpbb_root_path . 'ext/blitze/sitemaker/tests/acp/fixtures/filemanager/config.php');
    }

	protected function tearDown(): void
	{
		global $phpbb_root_path;

		parent::tearDown();

		unlink($phpbb_root_path . 'ext/blitze/sitemaker/tests/acp/fixtures/filemanager/config.php');
	}

	/**
	 * Define the extension to be tested.
	 *
	 * @return string[]
	 */
	protected static function setup_extensions()
	{
		return array('blitze/sitemaker');
	}

	/**
	 * Load required fixtures.
	 *
	 * @return mixed
	 */
	public function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__) . '/fixtures/settings.xml');
	}

	/**
	 * Get the settings_module object
	 *
	 * @param array $variable_map
	 * @param array $layout_prefs
	 * @param array $orphaned_blocks
	 * @param string $submit_var
	 * @return \blitze\sitemaker\acp\settings_module
	 */
	public function get_module(array $variable_map, array $layout_prefs = array(), array $orphaned_blocks = array(), $submit_var = '')
	{
		global $phpbb_container, $config, $db, $phpbb_dispatcher, $request, $template, $user, $phpbb_root_path, $phpEx;

		$db = $this->new_dbal();
		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();

		$config = new \phpbb\config\config(array(
			'sm_hide_birthday'		=> false,
			'sm_hide_login'			=> false,
			'sm_hide_online'		=> false,
			'sm_show_forum_nav'		=> true,
			'sm_navbar_menu'		=> 2,
			'sm_forum_icon'			=> 'fa fa-comments',
			'sm_filemanager'		=> false,
			'sitemaker_column_widths' => json_encode([
				1 => ['sidebar' => '200px'],
				2 => ['sidebar' => '25%'],
			]),
			'sm_orphaned_blocks'	=> json_encode($orphaned_blocks),
		));
		$this->config = &$config;
		$this->php_ext = $phpEx;

		$this->config_text = new \phpbb\config\db_text($db, 'phpbb_config_text');
		$this->config_text->set('sm_layout_prefs', json_encode($layout_prefs));

		$request = $this->getMockBuilder('\phpbb\request\request_interface')
			->disableOriginalConstructor()
			->getMock();
		$request->expects($this->any())
			->method('variable')
			->with($this->anything())
			->will($this->returnValueMap($variable_map));
		$request->expects($this->any())
			->method('is_set_post')
			->willReturnCallback(function($var) use ($submit_var) {
				return $submit_var === $var;
			});

		$translator = $this->getMockBuilder('\phpbb\language\language')
			->disableOriginalConstructor()
			->getMock();
		$translator->expects($this->any())
			->method('lang')
			->willReturnCallback(function () {
				return implode('-', func_get_args());
			});

		$user = new \phpbb\user($translator, '\phpbb\datetime');
		$user->data['user_id'] = 2;
		$user->data['user_lang'] = 'en';
		$user->data['user_form_salt'] = '';

		$tpl_data = array();
		$template = $this->getMockBuilder('\phpbb\template\template')
			->getMock();
		$template->expects($this->any())
			->method('assign_var')
			->will($this->returnCallback(function($key, $value) use (&$tpl_data) {
				$tpl_data[$key] = $value;
			}));
		$template->expects($this->any())
			->method('assign_vars')
			->will($this->returnCallback(function($data) use (&$tpl_data) {
				$tpl_data = array_merge($tpl_data, $data);
			}));
		$template->expects($this->any())
			->method('assign_block_vars')
			->will($this->returnCallback(function($key, $data) use (&$tpl_data) {
				$tpl_data[$key][] = $data;
			}));
		$template->expects($this->any())
			->method('assign_display')
			->will($this->returnCallback(function() use (&$tpl_data) {
				return $tpl_data;
			}));
		$this->template =& $template;

		$phpbb_container = new \phpbb_mock_container_builder();
		$phpbb_extension_manager = new \phpbb_mock_extension_manager(
			$phpbb_root_path,
			array(
				'blitze/sitemaker' => array(
					'ext_name'		=> 'blitze/sitemaker',
					'ext_active'	=> '1',
					'ext_path'		=> 'ext/blitze/sitemaker/',
				),
				'foo/bar' => array(
					'ext_name'		=> 'foo/bar',
					'ext_active'	=> '1',
					'ext_path'		=> 'foo/bar/',
				),
			),
			$phpbb_container);

		$blocks_manager = $this->getMockBuilder('\blitze\sitemaker\services\blocks\manager')
			->disableOriginalConstructor()
			->getMock();
		$blocks_manager->method('get_routes')
			->willReturn([]);
		$blocks_manager->method('get_unique_block_names')
			->willReturn([]);

		$url_checker = $this->getMockBuilder('\blitze\sitemaker\services\url_checker')
			->getMock();

		$this->icon_picker = $this->getMockBuilder('\blitze\sitemaker\services\icon_picker')
			->disableOriginalConstructor()
			->getMock();

		$this->blocks_cleaner = new \blitze\sitemaker\services\blocks\cleaner($this->config, $db, $blocks_manager, $url_checker, '', '');

		$table_prefix = 'phpbb_';
		$tables = array(
			'mapper_tables'	=> array(
				'menus'	=> $table_prefix . 'sm_menus',
			)
		);

		$mapper_factory = new \blitze\sitemaker\model\mapper_factory($config, $db, $tables);

		$this->config_path = dirname(__FILE__) . '/fixtures/filemanager/';
		$filesystem = new \phpbb\filesystem\filesystem();
		$filemanager = new \blitze\sitemaker\services\filemanager\settings($filesystem, $this->config_path, $phpEx);
		$filemanager->set_config_template($this->config_path . 'default.config');

		$phpbb_container->set('config_text', $this->config_text);
		$phpbb_container->set('ext.manager', $phpbb_extension_manager);
		$phpbb_container->set('language', $translator);
		$phpbb_container->set('blitze.sitemaker.icon_picker', $this->icon_picker);
		$phpbb_container->set('blitze.sitemaker.filemanager.settings', $filemanager);
		$phpbb_container->set('blitze.sitemaker.mapper.factory', $mapper_factory);
		$phpbb_container->set('blitze.sitemaker.blocks.cleaner', $this->blocks_cleaner);

		$module_service = $this->getMockBuilder('\blitze\sitemaker\acp\settings_module')
			->setMethods(['check_form_key'])
			->getMock();
		$module_service->expects($this->any())
			->method('check_form_key')
			->willReturn(true);

		return $module_service;
	}

	/**
	 * Data set for test_block_display
	 *
	 * @return array
	 */
	public function module_test_data()
	{
		return array(
			array(
				array(
					1 => array(
						'layout' => 'phpBB/ext/blitze/sitemaker/styles/all/template/layouts/portal/',
						'view' => 'simple',
					),
				),
				array(
					'styles' => [2, 5],
					'routes' => ['https://example.com/phpBB/no_exist.php'],
				),
				array(
					'styles' => array(
						array(
							'id' => '1',
							'name' => 'prosilver',
							'layout' => 'phpBB/ext/blitze/sitemaker/styles/all/template/layouts/portal/',
							'view' => 'simple',
						),
						array(
							'id' => '2',
							'name' => 'prosilver2',
							'layout' => 'phpBB/ext/blitze/sitemaker/styles/all/template/layouts/portal/',
							'view' => '',
						),
					),
					'layouts' => array(
						'blog' => 'phpBB/ext/blitze/sitemaker/styles/all/template/layouts/blog/',
						'my_layout' => 'phpBB/ext/foo/bar/styles/prosilver/template/layouts/my_layout/',
						'portal' => 'phpBB/ext/blitze/sitemaker/styles/all/template/layouts/portal/',
						'portal_alt' => 'phpBB/ext/blitze/sitemaker/styles/all/template/layouts/portal_alt/',
						'holygrail' => 'phpBB/ext/blitze/sitemaker/styles/all/template/layouts/holygrail/',
						'custom' => 'phpBB/ext/blitze/sitemaker/styles/all/template/layouts/custom/',
					),
					'filemanager' => array(
						'image_watermark'			=> '',
						'image_watermark_position'	=> 'br',
						'image_max_width'			=> 0,
						'image_resizing'			=> false,
					),
					'orphaned_blocks' => array(
						'styles' => [2, 5],
						'routes' => ['https://example.com/phpBB/no_exist.php'],
					),
					'sm_user_lang' => 'en',
					'menu_options' => '<option value="1">Menu 1</option><option value="2" selected="selected">Menu 2</option><option value="3">Menu 3</option>',
				),
			),
		);
	}

	/**
	 * Test the main method
	 *
	 * @dataProvider module_test_data
	 * @param array $layout_prefs
	 * @param array $orphaned_blocks
	 * @param array $expected
	 */
	public function test_module(array $layout_prefs, $orphaned_blocks, array $expected)
	{
		$module = $this->get_module(array(), $layout_prefs, $orphaned_blocks);

		$this->icon_picker->expects($this->once())
			->method('picker');

		$module->main();

		$result = $this->template->assign_display('settings');
		unset($result['S_FORM_TOKEN'], $result['u_action'], $result['icon_picker']);

		$expected['config'] = $this->config;

		$this->assertEquals($expected, $result);
	}

	/**
	 * Data set for test_save_settings
	 *
	 * @return array
	 */
	public function save_settings_test_data()
	{
		return array(
			array(
				'orphans',
				array(
					array('cleanup', array(0 => ''), false, request_interface::REQUEST, array('styles', 'blocks'))
				),
				array(
					'forum_icon'			=> 'fa fa-comments',
					'navbar_menu'			=> 2,
					'show_forum_nav'		=> true,
					'hide_login'			=> false,
					'hide_online'			=> false,
					'hide_birthday'			=> false,
					'filemanager'			=> false,
					'filemanager_config'	=> array(
						'image_watermark'			=> './image.jpg',
						'image_watermark_position'	=> 'br',
						'image_max_width'			=> 0,
						'image_resizing'			=> false,
					),
					'layout_prefs'			=> array(
						1 => array(
							'layout'	=> 'phpBB/ext/blitze/sitemaker/styles/all/template/layouts/portal/',
							'view'		=> 'simple',
						),
					),
					'orphaned_blocks'		=> null,
				),
				'BLOCKS_CLEANUP_DONE',
			),
			array(
				'submit',
				array(
					array('config', array('' => ''), false, request_interface::REQUEST, array(
						'sm_hide_birthday'	=> 1,
						'sm_hide_login'		=> 1,
						'sm_hide_online'	=> 1,
						'sm_show_forum_nav'	=> 1,
						'sm_navbar_menu'	=> 3,
						'sm_filemanager'	=> 1,
						'sm_forum_icon'		=> 'fa fa-car',
					)),
					array('filemanager', array('' => ''), false, request_interface::REQUEST, array(
						'image_watermark_coordinates' => '40x50',
						'image_max_width' => 800,
						'image_resizing' => 'true',
						'image_watermark' => './test.png',
					)),
					array('layouts', array(0 => array('' => '')), false, request_interface::REQUEST, array(
						1 => array (
							'layout' => 'phpBB/ext/blitze/sitemaker/styles/all/template/layouts/blog/',
							'view' => 'boxed',
						),
						2 => array (
							'layout' => 'phpBB/ext/foo/bar/styles/prosilver/template/layouts/my_layout/',
							'view' => 'simple',
						),
					)),
				),
				array(
					'forum_icon'			=> 'fa fa-car',
					'navbar_menu'			=> 3,
					'show_forum_nav'		=> 1,
					'hide_login'			=> 1,
					'hide_online'			=> 1,
					'hide_birthday'			=> 1,
					'filemanager'			=> 1,
					'filemanager_config'	=> array(
						'image_watermark'			=> './test.png',
						'image_watermark_position'	=> '40x50',
						'image_max_width'			=> 800,
						'image_resizing'			=> true,
					),
					'layout_prefs'			=> array(
						1 => array(
							'layout'	=> 'phpBB/ext/blitze/sitemaker/styles/all/template/layouts/blog/',
							'view'		=> 'boxed',
						),
						2 => array(
							'layout'	=> 'phpBB/ext/foo/bar/styles/prosilver/template/layouts/my_layout/',
							'view'		=> 'simple',
						),
					),
					'orphaned_blocks'		=> array(
						'styles' => [2, 5],
						'routes' => ['https://example.com/phpBB/no_exist.php'],
						'blocks' => ['my.invalid.block'],
					),
				),
				'SETTINGS_SAVED',
			),
		);
	}

	/**
	 * Test save settings
	 *
	 * @dataProvider save_settings_test_data
	 * @param string $submit_var
	 * @param array $variable_map
	 * @param array $expected
	 * @param string $message
	 */
	public function test_save_settings($submit_var, array $variable_map, array $expected, $message)
	{
		$layout_prefs = array(
			1 => array(
				'layout' => 'phpBB/ext/blitze/sitemaker/styles/all/template/layouts/portal/',
				'view' => 'simple',
			),
		);

		$orphaned_blocks = array(
			'styles' => [2, 5],
			'routes' => ['https://example.com/phpBB/no_exist.php'],
			'blocks' => ['my.invalid.block'],
		);

		$module = $this->get_module($variable_map, $layout_prefs, $orphaned_blocks, $submit_var);

		try
		{
			$module->main();
		}
		catch (\Exception $e)
		{
			preg_match('/\w+/i', $e->getMessage(), $matches);
			$this->assertEquals(!empty($matches[0]) ? $matches[0] : '', $message);
		}

		$result = array(
            'forum_icon'			=> $this->config['sm_forum_icon'],
            'navbar_menu'			=> $this->config['sm_navbar_menu'],
            'show_forum_nav'		=> $this->config['sm_show_forum_nav'],
			'hide_login'			=> $this->config['sm_hide_login'],
			'hide_online'			=> $this->config['sm_hide_online'],
			'hide_birthday'			=> $this->config['sm_hide_birthday'],
			'filemanager'			=> $this->config['sm_filemanager'],
			'filemanager_config'	=> include($this->config_path . 'config.' . $this->php_ext),
			'layout_prefs'			=> json_decode($this->config_text->get('sm_layout_prefs'), true),
			'orphaned_blocks'		=> json_decode($this->config['sm_orphaned_blocks'], true),
		);

		$this->assertEquals($expected, $result);
	}
}
