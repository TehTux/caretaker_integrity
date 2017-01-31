<?php
namespace Caretaker\CaretakerIntegrity;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Tobias Liebig <liebig@networkteam.com>
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

/**
 *
 */
class CoreFingerprintGenerator {

	/**
	 *
	 */
	protected $sourceUrl = 'git://git.typo3.org/Packages/TYPO3.CMS.git';

	/**
	 *
	 */
	protected $tempFolder;

	/**
	 *
	 */
	protected $fingerprintsFolder;

    /**
     * CoreFingerprintGenerator constructor.
     * @param $fingerprintPath
     */
    public function __construct($fingerprintPath) {
		$this->fingerprintsFolder = $fingerprintPath;
		$this->tempFolder = dirname(__FILE__) . '/../../../../typo3temp/';
	}

	/**
	 * @param string $path
	 * @return array
	 */
	protected function getFolderChecksum($path) {
		$dir = dir($this->tempFolder . $path);

		$md5s = array();
		while (FALSE !== ($entry = $dir->read())) {
			if ($entry === '.' || $entry === '..' || $entry === '.git' || $entry === '.svn') {
				continue;
			}

			if (is_dir($this->tempFolder . $path . '/' . $entry)) {
				list($checksum, $md5sOfSubfolder) = $this->getFolderChecksum($path . '/' . $entry);
				$md5s = array_merge($md5s, $md5sOfSubfolder);
			} else {
				$relPath = $path . '/' . $entry;
				$md5s[$relPath] = $this->getFileChecksum($relPath);
			}
		}

		asort($md5s);

		return array(
			md5(implode(',', $md5s)),
			$md5s
		);
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function getFileChecksum($path) {
		if (!is_file($this->tempFolder . $path)) {
			return '';
		}
		$md5 = md5_file($this->tempFolder . $path);

		return $md5;
	}

	/**
	 * @param string $file
	 * @return bool
	 */
	protected function createFingerprint($file) {
		echo '.fingerprint.' . chr(10);
		list($checksum, $md5s) = $this->getFolderChecksum('typo3_src');
		echo '.';

		if (!empty($checksum)) {
			$result = array(
				'checksum' => $checksum,
				'singleChecksums' => $md5s
			);
			$fingerprint = json_encode($result);
			file_put_contents($file, $fingerprint);

			return TRUE;
		}

		return FALSE;
	}

	/**
	 *
	 */
	public function start() {
		$this->initializeRepository();
		echo 'Get tags' . chr(10);
		ob_start();
		passthru($this->getGitCommand('tag', '--list'));
		$tagArray = array_filter(explode("\n", ob_get_clean()));
		foreach ($tagArray as $tagName) {
			preg_match('/TYPO3_\d+\-\d+\-\d+|\d+\.\d+\.\d+/', $tagName, $matches);
			if (!empty($matches)) {
				echo chr(10) . $tagName . chr(10);
				$version = str_replace('-', '.', str_replace('TYPO3_', '', $matches[0]));
				$fingerprintFile = $this->fingerprintsFolder . 'typo3_src-' . $version . '.fingerprint';

				if (file_exists($fingerprintFile)) {
					echo '[skip]' . chr(10);
					continue;
				}

				echo 'Checking out' . chr(10);;
				exec($this->getGitCommand('checkout', '-q ' . $tagName));

				if ($this->createFingerprint($fingerprintFile)) {
					echo ' [OK]' . chr(10);
				} else {
					echo ' [ERR]' . chr(10);
				}
			}
		}
		exec($this->getGitCommand('checkout', '-q master'));
	}

	/**
	 *
	 */
	protected function initializeRepository() {
		if (!is_dir($this->tempFolder . 'typo3_src')) {
			echo 'Cloning repository' . chr(10);
			exec('git clone ' . $this->sourceUrl . ' ' . $this->tempFolder . 'typo3_src');
		} else {
			echo 'Updating repository' . chr(10);
			exec($this->getGitCommand('fetch', '-q origin'));
		}
	}

	/**
	 * @param string $command
	 * @param string $options
	 * @return string
	 */
	protected function getGitCommand($command, $options) {
		return 'git --git-dir ' . $this->tempFolder . 'typo3_src/.git --work-tree ' . $this->tempFolder . 'typo3_src ' .
			$command . (!empty($options) ? ' ' . $options : '');
	}
}