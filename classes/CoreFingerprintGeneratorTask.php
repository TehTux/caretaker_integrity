<?php
namespace Caretaker\CaretakerIntegrity;

use \TYPO3\CMS\Core\Utility\GeneralUtility;

class CoreFingerprintGeneratorTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

    public function execute() {
        $fingerprintPath = 'EXT:caretaker_integrity/res/fingerprints/';
        $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['caretaker_integrity']);
        if (!empty($extensionConfiguration['path.']['fingerprints'])) {
            $fingerprintPath = rtrim(GeneralUtility::fixWindowsFilePath($extensionConfiguration['path.']['fingerprints']), '/') . '/';
        }
        $generator = new CoreFingerprintGenerator($fingerprintPath);
        $generator->start();
        return true;
    }

}
