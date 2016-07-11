<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Tobias Liebig <liebig@networkteam.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use \TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_caretakerintegrity_CheckCoreIntegrityTestService extends tx_caretakerinstance_RemoteTestServiceBase {

	const FILE_FILTER = '(^typo3_src/(\.gitignore|\.gitmodules|CVS|SVNreadme\.txt|[^/]*\.webprj|[^/]*\.orig|[^/]*~|\.travis\.yml)$|/src(/|$)|/tests(/|$))';

	public function runTest() {
		list($isSuccessful, $remoteFingerprint, $remoteTYPO3Version, $testResult) = $this->getRemoteChecksum();
		if (!$isSuccessful) {
			if ($testResult) {
				return $testResult;
			}
			return $this->createTestResult($remoteFingerprint . ' / ' . $remoteTYPO3Version, tx_caretaker_Constants::state_warning);
		} else {
			return $this->verifyFingerprint($remoteFingerprint, $remoteTYPO3Version);
		}
	}

	protected function getRemoteChecksum() {
		$operations = array();
		$operations[] = array(
			'GetFilesystemChecksum',
			array('path' => 'typo3_src')
		);
		$operations[] = array(
			'GetTYPO3Version'
		);

		$commandResult = $this->executeRemoteOperations($operations);
		if (!$this->isCommandResultSuccessful($commandResult)) {
                        return array(false, false, false, $this->getFailedCommandResultTestResult($commandResult));
                }

		$results = $commandResult->getOperationResults();
		$isSuccessful = count($results) == 2 && $results[0]->isSuccessful() && $results[1]->isSuccessful();

                if ($isSuccessful) {
			$remoteFingerprint = $results[0]->getValue();
			$remoteTYPO3Version = $results[1]->getValue();
                } else {
                        return array(false, false, false, $this->getFailedOperationResultTestResult($results[0]));
                }

		return array($isSuccessful, $remoteFingerprint, $remoteTYPO3Version, false);
	}


	protected function getRemoteSingleFileChecksums() {
		$operations = array();
		$operations[] = array(
			'GetFilesystemChecksum',
			array('path' => 'typo3_src', 'getSingleChecksums' => TRUE)
		);

		$commandResult = $this->executeRemoteOperations($operations);
		$results = $commandResult->getOperationResults();
		$remoteChecksums = $results[0]->getValue();

		return $remoteChecksums;
	}

	protected function verifyFingerprint($remoteFingerprint, $remoteTYPO3Version) {
		$path = 'EXT:caretaker_integrity/res/fingerprints/';
		$filename = 'typo3_src-' . $remoteTYPO3Version . '.fingerprint';
		$extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['caretaker_integrity']);
		if (!empty($extensionConfiguration['path.']['fingerprints'])) {
			$path = rtrim(GeneralUtility::fixWindowsFilePath($extensionConfiguration['path.']['fingerprints']), '/') . '/';
		}
		$fingerprintFile = GeneralUtility::getFileAbsFileName($path . $filename, FALSE);
		if (!file_exists($fingerprintFile)) {
			return $this->createTestResult(
				'Can\'t find local fingerprint file "' . $filename . '" in defined path "' . $path .'"',
				tx_caretaker_Constants::state_warning
			);
		} else {
			$fingerprint = json_decode(file_get_contents($fingerprintFile), true);
			if ($fingerprint['checksum'] === $remoteFingerprint['checksum']) {
				return $this->createTestResult('TYPO3 source seems to be a git repository');
			} else {
				$remote = $this->getRemoteSingleFileChecksums();
				$errornousFiles = array();
				$additionalFiles = explode("\n", $this->getConfigValue('additionalFiles'));

				foreach ($remote['singleChecksums'] as $file => $checksum) {
					if ($checksum !== $fingerprint['singleChecksums'][$file]) {
						$valid = FALSE;
						foreach ($additionalFiles as $additionalFile) {
							list($regularExpression, $definedChecksum) = explode('=', $additionalFile);
							if (preg_match('/' . str_replace('/', '\/', $regularExpression) . '/', $file)) {
								if (empty($definedChecksum) || $checksum === $definedChecksum) {
									$valid = TRUE;
								}
							}
						}
						unset($additionalFile);

						if ($valid) {
							unset($remote['singleChecksums'][$file]);
						} else {
							$errornousFiles[] = $file;
						}
					}
				}

				if (count($errornousFiles) > 0) {
					return $this->createTestResult(
						'Can\'t verify fingerprint (' . count($errornousFiles) . ' files differ) ' . chr(10) .
							implode(chr(10) . ' - ', $errornousFiles),
						tx_caretaker_Constants::state_error,
						count($errornousFiles)
					);
				} else {
					$missingFiles = '(' . implode('|', explode("\n", $this->getConfigValue('missingFiles'))) . ')';
					foreach ($fingerprint['singleChecksums'] as $file => $checksum) {
						if (!isset($remote['singleChecksums'][$file]) &&
							(preg_match('/' . str_replace('/', '\/', static::FILE_FILTER) . '/', $file) || preg_match('/' . str_replace('/', '\/', $missingFiles) . '/', $file)))
						{
							unset($fingerprint['singleChecksums'][$file]);
						}
					}
					unset($file, $checksum);

					if (md5(implode(',', $remote['singleChecksums'])) === md5(implode(',', $fingerprint['singleChecksums']))) {
						return $this->createTestResult('TYPO3 source seems to be a download');
					} else {
						return $this->createTestResult(
							'Can\'t verify fingerprint (files seems to be ok, but over-all check failed)!' . chr(10) .
								$fingerprint['checksum'] . ' !== ' . $remoteFingerprint['checksum'],
							tx_caretaker_Constants::state_warning
						);
					}
				}
			}
		}
	}

	/**
	 * @param string $message
	 * @param int $state
	 * @param int $value
	 * @return tx_caretaker_TestResult
	 */
	protected function createTestResult($message, $state = tx_caretaker_Constants::state_ok, $value = 0) {
		return tx_caretaker_TestResult::create($state, $value, $message);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/caretaker_integrity/services/class.tx_caretakerintegrity_CheckCoreIntegrityTestService.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/caretaker_integrity/services/class.tx_caretakerintegrity_CheckCoreIntegrityTestService.php']);
}
