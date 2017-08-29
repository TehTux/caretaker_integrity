<?php

use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$extensionPath = ExtensionManagementUtility::extPath('caretaker_integrity');
$extensionClassesPath = $extensionPath . 'classes/';
$extensionServicesPath = $extensionPath . 'services/';

return array(
	'tx_caretakerintegrity_Cli' => $extensionClassesPath . 'class.tx_caretakerintegrity_Cli.php',
	'tx_caretakerintegrity_CheckCoreIntegrityTestService' => $extensionServicesPath . 'class.tx_caretakerintegrity_CheckCoreIntegrityTestService.php',
	'tx_caretakerintegrity_CheckFolderIntegrityTestService' => $extensionServicesPath . 'class.tx_caretakerintegrity_CheckFolderIntegrityTestService.php'
);
