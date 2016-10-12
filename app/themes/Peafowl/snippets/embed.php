<?php if(!defined('access') or !access) die('This file cannot be directly accessed.');
global $embed_tpl;
$embed_tpl = [
	'links' => [
		'label' => _s('Links'),
		'options' => [
			'viewer-links' => [
				'label'		=> _s('Viewer links'),
				'template'	=> '%URL_VIEWER%',
				'size'		=> 'viewer'
			],
			'direct-links'	=> [
				'label'		=> _s('Direct links'),
				'template'	=> '%URL%',
				'size'		=> 'full'
			]
		]
	],
	'html-codes' => [
		'label' => _s('HTML Codes'),
		'options' => [
			'html-embed'	=> [
				'label'		=> _s('HTML image'),
				'template'	=> '<img src="%URL%" alt="%FILENAME%" border="0">',
				'size'		=> 'full'
			],
			'html-embed-full'	=> [
				'label'		=> _s('HTML full linked'),
				'template'	=> '<a href="%URL_VIEWER%"><img src="%URL%" alt="%FILENAME%" border="0"></a>',
				'size'		=> 'full'
			],
			'html-embed-medium'		=> [
				'label'		=> _s('HTML medium linked'),
				'template'	=> '<a href="%URL_VIEWER%"><img src="%MEDIUM_URL%" alt="%MEDIUM_FILENAME%" border="0"></a>',
				'size'		=> 'medium'
			],
			'html-embed-thumbnail'	=> [
				'label'		=> _s('HTML thumbnail linked'),
				'template'	=> '<a href="%URL_VIEWER%"><img src="%THUMB_URL%" alt="%THUMB_FILENAME%" border="0"></a>',
				'size'		=> 'thumb'
			]
		]
	],
	'bbcodes' => [
		'label' => _s('BBCodes'),
		'options' => [
			'bbcode-embed'	=> [
				'label'		=> _s('BBCode full'),
				'template'	=> '[img]%URL%[/img]',
				'size'		=> 'full'
			],
			'bbcode-embed-full' => [
				'label'		=> _s('BBCode full linked'),
				'template'	=> '[url=%URL_VIEWER%][img]%URL%[/img][/url]',
				'size'		=> 'full'
			],
			'bbcode-embed-medium'	=> [
				'label'		=> _s('BBCode medium linked'),
				'template'	=> '[url=%URL_VIEWER%][img]%MEDIUM_URL%[/img][/url]',
				'size'		=> 'medium'
			],
			'bbcode-embed-thumbnail' => [
				'label'		=> _s('BBCode thumbnail linked'),
				'template'	=> '[url=%URL_VIEWER%][img]%THUMB_URL%[/img][/url]',
				'size'		=> 'thumb'
			]
		]
	],
	'markdown' => [
		'label'	=> 'Markdown',
		'options' => [
			'markdown-embed'	=> [
				'label'		=> _s('Markdown full'),
				'template'	=> '![%FILENAME%](%URL%)',
				'size'		=> 'full'
			],
			'markdown-embed-full' => [
				'label'		=> _s('Markdown full linked'),
				'template'	=> '[![%FILENAME%](%URL%)](%URL_VIEWER%)',
				'size'		=> 'full'
			],
			'markdown-embed-medium'	=> [
				'label'		=> _s('Markdown medium linked'),
				'template'	=> '[![%MEDIUM_FILENAME%](%MEDIUM_URL%)](%URL_VIEWER%)',
				'size'		=> 'medium'
			],
			'markdown-embed-thumbnail' => [
				'label'		=> _s('Markdown thumbnail linked'),
				'template'	=> '[![%THUMB_FILENAME%](%THUMB_URL%)](%URL_VIEWER%)',
				'size'		=> 'thumb'
			]
		]
	]
];