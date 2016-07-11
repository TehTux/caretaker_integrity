<?php

use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use \TYPO3\CMS\Core\Utility\VersionNumberUtility;

if (!defined ('TYPO3_MODE')) {
	die('Access denied.');
}

if (VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) >= 4001000) {
	$TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys']['caretaker_integrity'] = array(
		'EXT:caretaker_integrity/classes/class.tx_caretakerintegrity_Cli.php',
		'_CLI_caretaker'
	);
}

// Register caretaker services
if (ExtensionManagementUtility::isLoaded('caretaker')) {
	tx_caretaker_ServiceHelper::registerCaretakerTestService(
		$_EXTKEY,
		'services',
		'tx_caretakerintegrity_CheckCoreIntegrity',
		'Integrity -> Check TYPO3-core source integrity',
		'Find changed files in remote TYPO3-Core'
	);
	tx_caretaker_ServiceHelper::registerCaretakerTestService(
		$_EXTKEY, 'services',
		'tx_caretakerintegrity_CheckFolderIntegrity',
		'Integrity -> Check integrity of a folder or file',
		'Find changed files in remote folder');
}
