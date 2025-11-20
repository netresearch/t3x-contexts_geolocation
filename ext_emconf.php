<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "contexts_geolocation".
 *
 * Auto generated 07-03-2014 14:39
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Multi-channel contexts: Geolocation',
	'description' => 'Use the user\'s geographic location as context',
	'category' => 'misc',
	'author' => 'Andre Hähnel, Christian Opitz, Christian Weiske, Marian Pollzien, Rico Sonntag',
	'author_email' => 'typo3.org@netresearch.de',
	'shy' => '',
	'dependencies' => 'contexts,static_info_tables',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => 'typo3temp/contexts_geolocation/',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => 'Netresearch GmbH & Co.KG',
	'version' => '1.0.4',
	'constraints' => array(
		'depends' => array(
            'typo3' => '6.2.0-8.9.99',
            'php'   => '5.3.0-7.1.99',
			'contexts' => '1.0.0-',
			'static_info_tables' => '2.0.0-',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:32:{s:9:"ChangeLog";s:4:"1afe";s:16:"ext_autoload.php";s:4:"37ca";s:21:"ext_conf_template.txt";s:4:"ab52";s:16:"ext_contexts.php";s:4:"665f";s:12:"ext_icon.gif";s:4:"e433";s:17:"ext_localconf.php";s:4:"fef1";s:14:"ext_tables.php";s:4:"4aa3";s:11:"README.html";s:4:"e74c";s:10:"README.rst";s:4:"8419";s:12:"Settings.yml";s:4:"1ff9";s:19:"Classes/Adapter.php";s:4:"28ed";s:19:"Classes/Backend.php";s:4:"4795";s:21:"Classes/Exception.php";s:4:"fd4e";s:25:"Classes/Adapter/GeoIp.php";s:4:"4a63";s:28:"Classes/Adapter/NetGeoIp.php";s:4:"c4c7";s:34:"Classes/Context/Type/Continent.php";s:4:"d172";s:32:"Classes/Context/Type/Country.php";s:4:"9d2a";s:33:"Classes/Context/Type/Distance.php";s:4:"fa86";s:36:"Configuration/flexform/Continent.xml";s:4:"dcd7";s:34:"Configuration/flexform/Country.xml";s:4:"6c20";s:35:"Configuration/flexform/Distance.xml";s:4:"35af";s:39:"Resources/Private/Language/flexform.xml";s:4:"3d3b";s:43:"Resources/Private/Language/locallang_db.xml";s:4:"e31a";s:47:"Resources/Public/JavaScript/Leaflet/leaflet.css";s:4:"8ae0";s:50:"Resources/Public/JavaScript/Leaflet/leaflet.ie.css";s:4:"db9b";s:46:"Resources/Public/JavaScript/Leaflet/leaflet.js";s:4:"df34";s:53:"Resources/Public/JavaScript/Leaflet/images/layers.png";s:4:"2ba2";s:58:"Resources/Public/JavaScript/Leaflet/images/marker-icon.png";s:4:"87f6";s:61:"Resources/Public/JavaScript/Leaflet/images/marker-icon@2x.png";s:4:"1c82";s:60:"Resources/Public/JavaScript/Leaflet/images/marker-shadow.png";s:4:"e7bd";s:20:"doc/cfg-distance.png";s:4:"4945";s:44:"pi/class.tx_contextsgeolocation_position.php";s:4:"82b4";}',
	'suggests' => array(
	),
);

?>