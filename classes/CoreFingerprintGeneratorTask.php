<?php
namespace Caretaker\CaretakerIntegrity;

class CoreFingerprintGeneratorTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

    public function execute() {
        $generator = new CoreFingerprintGenerator();
        $generator->start();
        return true;
    }

}
