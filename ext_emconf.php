<?php

########################################################################
# Extension Manager/Repository config file for ext: "caretaker_instance"
#
# Auto generated 27-08-2008 08:59
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Caretaker Integrity',
	'description' => 'Tests for checking integrity of the TYPO3-Core or Extensions on remote instances',
	'category' => 'misc',
	'author' => 'Tobias Liebig',
	'author_email' => 'liebig@networkteam.com',
	'state' => 'alpha',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'author_company' => '',
	'version' => '0.0.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2.0-7.6.99',
			'caretaker' => '0.7.0-'
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => '',
);

?>