<?php
return array(
	//数据库配置
	'db'                 => array(
    	'host'           =>    'localhost',
    	'port'           =>    '3306',
		'user'           =>    'hbsql',
		'pwd'            =>    'n7yJkdCaz3ssFS87',
		'dbname'         =>    'hbsql',
		'charset'        =>    'utf8',
		'pre'            =>    ''
	),
	//支持的缓存类型
	'allowCacheType'     => array('file', 'memcache', 'redis'),
	//缓存设置
	'cache'             => array(
		'type'          => 'file',
		'pre'           => 'grace'
	)
);
