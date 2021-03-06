<?php
/**
 *
 * @package sitemaker
 * @copyright (c) 2019 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace blitze\sitemaker\blocks;

use blitze\sitemaker\services\blocks\driver\block;

/**
 * Feeds Block
 */
class feeds extends block
{
	/** @var \phpbb\language\language */
	protected $translator;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\template\twig\environment */
	protected $twig;

	/** @var string */
	protected $cache_dir;

	/** @var array */
	protected $feed_fields = ['title', 'description', 'category', 'categories', 'author', 'authors', 'contributor', 'contributors', 'copyright', 'image_url', 'image_title', 'image_link', 'image_width', 'image_height', 'permalink', 'link', 'links'];

	/** @var array */
	protected $item_fields = ['id', 'title', 'description', 'content', 'category', 'categories', 'author', 'authors', 'contributor', 'contributors', 'copyright', 'date', 'updated_date', 'gmdate', 'updated_gmdate', 'permalink', 'link', 'links', 'enclosure', 'enclosures', 'latitude', 'longitude', 'source'];

	/**
	 * Constructor
	 *
	 * @param \phpbb\language\language				$translator			Language object
	 * @param \phpbb\request\request_interface		$request			Request object
	 * @param \phpbb\template\twig\environment		$twig				Twig environment
	 * @param string 								$cache_dir			Path to cache directory
	 */
	public function __construct(\phpbb\language\language $translator, \phpbb\request\request_interface $request, \phpbb\template\twig\environment $twig, $cache_dir)
	{
		$this->translator = $translator;
		$this->request = $request;
		$this->twig = $twig;
		$this->cache_dir = $cache_dir;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_config(array $settings)
	{
		$template_default = '<a target="_blank" href="{{ item.link }}">{{ item.title }}</a>';
		return array(
			'legend1'			=> 'SETTINGS',
			'feeds'			=> array('lang' => 'FEED_URLS', 'type' => 'multi_input:0', 'default' => []),
			'template'		=> array('type' => 'custom', 'default' => $template_default, 'object' => $this, 'method' => 'get_cfg_feeds_template'),
			'max'			=> array('lang' => 'MAX_ITEMS', 'validate' => 'int:1', 'type' => 'number:1', 'default' => 5),
			'cache'			=> array('lang' => 'CACHE_DURATION', 'validate' => 'int:1', 'type' => 'number:1', 'default' => 6, 'append' => 'HOURS_SHORT'),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function display(array $bdata, $edit_mode = false)
	{
		$title = 'FEEDS';
		$content = '';
		$settings = $bdata['settings'];
		$feed_urls = $this->get_feeds_array($settings['feeds']);

		if (sizeof($feed_urls))
		{
			if ($items = $this->get_feed_items($feed_urls, $content, $settings['max'], $settings['cache']))
			{
				// We try to render block with user-provided trig template
				try
				{
					$template = $this->twig->createTemplate($this->get_template($settings['template']));

					return array(
						'title'		=> $title,
						'content'	=> $template->render([
							'items'	=> $items,
						])
					);
				}
				catch (\Exception $e)
				{
					$content = $e->getMessage();
				}
			}
		}
		else
		{
			$content = $this->translator->lang('FEED_URL_MISSING');
		}

		return array(
			'title'		=> $title,
			'content'	=> ($edit_mode) ? $content : '',
		);
	}

	/**
	 * @param string $template
	 * @return string
	 */
	public function get_cfg_feeds_template($template)
	{
		$this->ptemplate->assign_vars([
			'template'	=> $template,
		]);

		return $this->ptemplate->render_view('blitze/sitemaker', 'cfg_fields/feeds.html', 'cfg_feeds');
	}

	/**
	 * Called when editing feed block to get available rss/atom fields
	 * @return array
	 */
	public function get_fields()
	{
		$this->translator->add_lang('feed_fields', 'blitze/sitemaker');

		$feeds = $this->request->variable('feeds', array(0 => ''));
		$feeds = $this->get_feeds_array($feeds);

		$message = '';
		$data = array('items' => []);
		$fields = array('items' => $this->get_field_defaults('items'));
		$feed_items = $this->get_feed_items($feeds, $message, 0, 0, 1);

		foreach ($feed_items as $feed)
		{
			$feed_data = $feed_fields = [];
			$fields['items']['children'] += $this->get_feed_fields($feed, $feed_data);

			foreach ($this->item_fields as $field)
			{
				$value = $feed->{$field};
				$feed_data[$field] = $value;
				$feed_fields[$field] = $this->build_tags($field, $value);
			}

			$data['items'][] = array_filter($feed_data);
			$fields['items']['children'] = array_replace_recursive($fields['items']['children'], array_filter($feed_fields));
		}

		return [
			'fields'	=> array_filter($fields),
			'data'		=> array_filter($data),
			'message'	=> $message,
		];
	}

	/**
	 * @param array $feed_urls
	 * @param string $message
	 * @param int $max
	 * @param int $cache
	 * @return array
	 */
	protected function get_feed_items(array $feed_urls, &$message, $max, $cache = 0, $items_per_feed = 0)
	{
		$items = [];

		if (sizeof($feed_urls))
		{
			try
			{
				/**
				 * The below class cannot be added as a non-shared service using DI
				 * as it does not follow best practises for class contructs.
				 * It contains logic and method calls in the contructor for one thing.
				 * Passing it as a non-shared service does not work
				 */
				$feed = new \blitze\sitemaker\services\simplepie\feed;
				$feed->set_feed_url($feed_urls);
				$feed->enable_cache((bool) $cache);
				$feed->set_cache_location($this->cache_dir);
				$feed->set_cache_duration($cache * 3600);

				if ($items_per_feed)
				{
					$feed->set_item_limit($items_per_feed);
				}

				$feed->init();
				$feed->handle_content_type();

				if (!($items = $feed->get_items(0, $max)))
				{
					$message = $this->translator->lang('FEED_PROBLEMS');
				}
			}
			catch (\Exception $e)
			{
				$message = $e->getMessage();
			}
		}

		return array_filter((array) $items);
	}

	/**
	 * @param \blitze\sitemaker\services\simplepie\item $feed
	 * @return array
	 */
	protected function get_feed_fields(\blitze\sitemaker\services\simplepie\item $item, array &$data)
	{
		$feed_props = [];
		foreach ($this->feed_fields as $field)
		{
			$feed_props[$field] = $this->build_tags($field, $item->feed->{$field});
			$data['feed'][$field] = $item->feed->{$field};
		}

		$feed_props = array_filter($feed_props);
		$data['feed'] = array_filter($data['feed']);

		$fields = [];
		if (sizeof($feed_props))
		{
			$fields['feed'] = $this->get_field_defaults('feed');
			$fields['feed']['children'] = $feed_props;
		}

		return $fields;
	}

	/**
	 * @param string $field
	 * @param mixed $value
	 * @return array|string
	 */
	protected function build_tags($field, $value)
	{
		if (empty($value))
		{
			return '';
		}

		$data = $this->get_field_defaults($field);

		if ($this->is_array_of_objects($value))
		{
			$value = array_slice($value, 0, 1);
			$this->iterate_props($value, $data);
		}
		else if (gettype($value) === 'object')
		{
			$props = array_filter(get_object_vars($value));
			$this->iterate_props($props, $data);
		}

		return $data;
	}

	/**
	 * @param array $props
	 * @param array $data
	 * @return void
	 */
	protected function iterate_props(array $props, array &$data)
	{
		ksort($props);
		foreach ($props as $prop => $value)
		{
			$data['children'][$prop] = $this->build_tags($prop, $value);
		}
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	protected function is_array_of_objects($value)
	{
		return (is_array($value) && gettype($value[0]) === 'object');
	}

	/**
	 * @param string $field
	 * @return array
	 */
	protected function get_field_defaults($field)
	{
		$field = (string) $field;
		return [
			'text'			=> $field,
			'displayText'	=> $this->translator->lang(strtoupper($field)),
			'children'		=> [],
		];
	}

	/**
	 * @param string $tpl
	 * @return string
	 */
	protected function get_template($item_tpl)
	{
		$item_tpl = html_entity_decode(trim($item_tpl));
		return "<ul class=\"sm-list\">
			{% for item in items %}
			<li>
				$item_tpl
			</li>
			{% endfor %}
		</ul>";
	}

	/**
	 * @param mixed $feeds
	 * @return array
	 */
	protected function get_feeds_array($feeds)
	{
		return array_map('trim', array_filter((array) $feeds));
	}
}
