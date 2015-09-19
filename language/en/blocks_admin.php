<?php
/**
*
* @package phpBB Sitemaker [English]
* @copyright (c) 2012 Daniel A. (blitze)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'ALL'										=> 'All',
	'ALLOW_WORD_COUNT'							=> 'Enable word counter',
	'ALLOW_WORD_COUNT_EXPLAIN'					=> 'Allow display the total number of words after each word, e.g. <samp>phpBB(33)</samp>',
	'AUTO_LOGIN'								=> 'Allow auto login?',

	'BLITZE_SITEMAKER_BLOCK_BIRTHDAY'			=> 'Birthday',
	'BLITZE_SITEMAKER_BLOCK_CUSTOM'				=> 'Custom Block',
	'BLITZE_SITEMAKER_BLOCK_FEATURED_MEMBER'	=> 'Featured Member',
	'BLITZE_SITEMAKER_BLOCK_FORUM_POLL'			=> 'Forum Poll',
	'BLITZE_SITEMAKER_BLOCK_FORUM_TOPICS'		=> 'Forum Topics',
	'BLITZE_SITEMAKER_BLOCK_LOGIN'				=> 'Login Box',
	'BLITZE_SITEMAKER_BLOCK_MEMBERS'			=> 'Members',
	'BLITZE_SITEMAKER_BLOCK_MEMBER_MENU'		=> 'Member Menu',
	'BLITZE_SITEMAKER_BLOCK_MENU'				=> 'Menu',
	'BLITZE_SITEMAKER_BLOCK_MYBOOKMARKS'		=> 'My Bookmarks',
	'BLITZE_SITEMAKER_BLOCK_STATS'				=> 'Statistics',
	'BLITZE_SITEMAKER_BLOCK_WHATS_NEW'			=> 'What’s New?',
	'BLITZE_SITEMAKER_BLOCK_WHOIS'				=> 'Who is online',
	'BLITZE_SITEMAKER_BLOCK_WORDGRAPH'			=> 'Wordgraph',

	'CONTEXT'									=> 'Context',
	'CUSTOM_PROFILE_FIELDS'						=> 'Custom Profile Fields',

	'DATE_RANGE'								=> 'Date Range',
	'DISPLAY_PREVIEW'							=> 'Display Preview?',

	'EDIT_ME'									=> 'Please edit me',
	'ENABLE_TOPIC_TRACKING'						=> 'Enable topic tracking?',
	'ENABLE_TOPIC_TRACKING_EXPLAIN'				=> 'If enabled, unread topics will be indicated but the block results will not be cached <strong>(Not recommended)</strong>',
	'EXCLUDE_TOO_MANY_WORDS'					=> 'You have entered too many words to exclude. The maximum number of characters possible is 255, you have entered %s.',
	'EXCLUDE_WORDS'								=> 'Exclude words',
	'EXCLUDE_WORDS_EXPLAIN'						=> 'List the words you’d like to exclude from the wordgraph separated by a comma (,). Maximum 255 characters.',
	'EXPANDED'									=> 'Expanded',

	'FEATURED_MEMBER_IDS'						=> 'User IDs',
	'FEATURED_MEMBER_IDS_EXPLAIN'				=> 'Comma separated list of users to feature (Only applies to Featured Member display mode)',
	'FIRST_POST_TIME'							=> 'First Post Time',
	'FORUMS_GET_TYPE'							=> 'Get type',
	'FORUMS_MAX_TOPICS'							=> 'Maximum topics/posts',
	'FORUMS_TITLE_MAX_CHARS'					=> 'Maximum characters per title',
	'FREQUENCY'									=> 'Frequency',
	'FULL'										=> 'Full',

	'GET_TYPE'									=> 'Display Topic/Post?',

	'LAST_POST_TIME'							=> 'Last Post Time',
	'LAST_READ_TIME'							=> 'Last Read Time',
	'LIMIT_FORUMS'								=> 'Forum Ids (optional)',
	'LIMIT_FORUMS_EXPLAIN'						=> 'Enter each forum id separated by a comma (,). If set, only topics from specified forums will be displayed.',
	'LIMIT_POST_TIME'							=> 'Limit by Post time',
	'LIMIT_POST_TIME_EXPLAIN'					=> 'If set, only topics posted within the specified period will be retrieved',

	'MAX_DEPTH'									=> 'Maximum depth',
	'MAX_MEMBERS'								=> 'Max. Members',
	'MAX_POSTS'									=> 'Maximum number of posts',
	'MAX_TOPICS'								=> 'Maximum number of topics',
	'MEMBERS_DATE'								=> 'Date',
	'MINI'										=> 'Mini',

	'NONE'										=> 'None',

	'OR'										=> '<strong>OR</strong>',
	'ORDER_BY'									=> 'Order by',

	'PIXEL'										=> 'px',
	'POLL_FROM_FORUMS'							=> 'Display polls from forums(s)',
	'POLL_FROM_FORUMS_EXPLAIN'					=> 'Only polls from the selected forums will be displayed as long as no topics are specified above',
	'POLL_FROM_GROUPS'							=> 'Display polls from groups(s)',
	'POLL_FROM_GROUPS_EXPLAIN'					=> 'Only polls from members of the selected groups will be displayed as long as no user(s) is/are specified above',
	'POLL_FROM_TOPICS'							=> 'Display polls from topic(s)',
	'POLL_FROM_TOPICS_EXPLAIN'					=> 'Id(s) of topics to retrieve polls from, separated by <strong>commas</strong>(,). Leave blank to select any topic.',
	'POLL_FROM_USERS'							=> 'Display polls from user(s)',
	'POLL_FROM_USERS_EXPLAIN'					=> 'Id(s) of user(s) whose polls you’d like to display, separated by <strong>commas</strong>(,). Leave blank to select polls from any user.',
	'POSTS'										=> 'Posts',
	'POSTS_TITLE_LIMIT'							=> 'Maximum # of characters for post title',
	'POST_ANNOUNCEMENT'							=> 'Announcement',
	'POST_GLOBAL'								=> 'Global',
	'POST_NORMAL'								=> 'Normal',
	'POST_STICKY'								=> 'Sticky',
	'PREVIEW_MAX_CHARS'							=> 'Number of characters to preview',

	'QUERY_TYPE'								=> 'Display Mode',

	'RANDOM'									=> 'Random',
	'ROTATE_DAILY'								=> 'Daily',
	'ROTATE_HOURLY'								=> 'Hourly',
	'ROTATE_MONTHLY'							=> 'Monthly',
	'ROTATE_PAGELOAD'							=> 'Page load',
	'ROTATE_WEEKLY'								=> 'Weekly',

	'SELECT_FORUMS'								=> 'Select forums',
	'SELECT_FORUMS_EXPLAIN'						=> 'Select the forums from which to display topics/posts. Leave blank to select from all forums',
	'SELECT_MENU'								=> 'Select Menu',
	'SELECT_PROFILE_FIELDS'						=> 'Select profile fields',
	'SELECT_PROFILE_FIELDS_EXPLAIN'				=> 'Only the selected profile fields will be displayed, if available.',
	'SHOW_FIRST_POST'							=> 'Yes - First Post',
	'SHOW_HIDE_ME'								=> 'Allow hide online status?',
	'SHOW_LAST_POST'							=> 'Yes - Last Post',
	'SHOW_MEMBER_MENU'							=> 'Show user menu?',
	'SHOW_MEMBER_MENU_EXPLAIN'					=> 'Replace login box with user menu if user is logged in',

	'TEMPLATE'									=> 'Template',
	'TOPICS'									=> 'Topics',
	'TOPICS_ONLY'								=> 'Topics only?',
	'TOPIC_TITLE_LIMIT'							=> 'Maximum # of characters for topic title',
	'TOPIC_TYPE'								=> 'Topic Type',
	'TOPIC_TYPE_EXPLAIN'						=> 'Select the topic types you’d like to display. Leave the boxes unchecked to select from all topic types',

	'WORDS'										=> 'Words',
	'WORD_MAX_SIZE'								=> 'Maximum font size',
	'WORD_MAX_SIZE_EXPLAIN'						=> 'Set maximum value of font size for words in wordgraph.',
	'WORD_MIN_SIZE'								=> 'Minimum font size',
	'WORD_MIN_SIZE_EXPLAIN'						=> 'Set minimum value of font size for words in wordgraph.',
	'WORD_NUMBER'								=> 'Number of words',
	'WORD_NUMBER_EXPLAIN'						=> 'Select the number of words to display in wordgraph. A higher number could make server load slower in wordgraph page.',
));