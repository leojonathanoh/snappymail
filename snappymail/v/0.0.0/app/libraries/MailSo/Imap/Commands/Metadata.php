<?php

/*
 * This file is part of MailSo.
 *
 * (c) 2021 DJMaze
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * https://datatracker.ietf.org/doc/html/rfc5464
 */

namespace MailSo\Imap\Commands;

/**
 * @category MailSo
 * @package Imap
 */
trait Metadata
{

	/**
	 * Dovecot 2.2+ supports fetching all METADATA at once (wildcard).
	 * RFC 5464 doesn't specify this, but its earlier draft did, and Kolab uses it.
	 */
	public function getAllMetadata() : array
	{
		$aReturn = array();
		try {
			$arguments = [
				'(DEPTH infinity)',
				$this->EscapeString('*')
			];
			$arguments[] = '(' . \implode(' ', \array_map([$this, 'EscapeString'], ['/shared', '/private'])) . ')';
			$oResult = $this->SendRequestGetResponse('GETMETADATA', $arguments);
			foreach ($oResult as $oResponse) {
				if (\MailSo\Imap\Enumerations\ResponseType::UNTAGGED === $oResponse->ResponseType
					&& 4 === \count($oResponse->ResponseList)
					&& 'METADATA' === $oResponse->ResponseList[1]
					&& \is_array($oResponse->ResponseList[3]))
				{
					$aMetadata = array();
					$c = \count($oResponse->ResponseList[3]);
					for ($i = 0; $i < $c; $i += 2) {
						$aMetadata[$oResponse->ResponseList[3][$i]] = $oResponse->ResponseList[3][$i+1];
					}
					$aReturn[$this->toUTF8($oResponse->ResponseList[2])] = $aMetadata;
				}
			}
		} catch (\Throwable $e) {
			//\SnappyMail\Log::warning('IMAP', $e->getMessage());
		}
		return $aReturn;
	}

	public function getMetadata(string $sFolderName, array $aEntries, array $aOptions = []) : array
	{
		$arguments = [];

		if ($aOptions) {
			$options = [];
			$aOptions = \array_intersect_key(
				\array_change_key_case($aOptions, CASE_UPPER),
				['MAXSIZE' => 0, 'DEPTH' => 0]
			);
			if (isset($aOptions['MAXSIZE']) && 0 < \intval($aOptions['MAXSIZE'])) {
				$options[] = 'MAXSIZE ' . \intval($aOptions['MAXSIZE']);
			}
			if (isset($aOptions['DEPTH']) && (1 == $aOptions['DEPTH'] || 'infinity' === $aOptions['DEPTH'])) {
				$options[] = "DEPTH {$aOptions['DEPTH']}";
			}
			if ($options) {
				$arguments[] = '(' . \implode(' ', $options) . ')';
			}
		}

		$arguments[] = $this->EscapeFolderName($sFolderName);

		$arguments[] = '(' . \implode(' ', \array_map([$this, 'EscapeString'], $aEntries)) . ')';

		$oResult = $this->SendRequestGetResponse('GETMETADATA', $arguments);

		$aReturn = array();
		foreach ($oResult as $oResponse) {
			if (\MailSo\Imap\Enumerations\ResponseType::UNTAGGED === $oResponse->ResponseType
				&& 4 === \count($oResponse->ResponseList)
				&& 'METADATA' === $oResponse->ResponseList[1]
				&& \is_array($oResponse->ResponseList[3]))
			{
				$c = \count($oResponse->ResponseList[3]);
				for ($i = 0; $i < $c; $i += 2) {
					$aReturn[$oResponse->ResponseList[3][$i]] = $oResponse->ResponseList[3][$i+1];
				}
			}
		}
		return $aReturn;
	}

	public function ServerGetMetadata(array $aEntries, array $aOptions = []) : array
	{
		return $this->IsSupported('METADATA-SERVER')
			? $this->getMetadata('', $aEntries, $aOptions)
			: [];
	}

	public function FolderGetMetadata(string $sFolderName, array $aEntries, array $aOptions = []) : array
	{
		return $this->IsSupported('METADATA')
			? $this->getMetadata($sFolderName, $aEntries, $aOptions)
			: [];
	}

	public function FolderSetMetadata(string $sFolderName, array $aEntries) : void
	{
		if ($this->IsSupported('METADATA')) {
			if (!$aEntries) {
				throw new \MailSo\Base\Exceptions\InvalidArgumentException("Wrong argument for SETMETADATA command");
			}

			$arguments = [$this->EscapeFolderName($sFolderName)];

			\array_walk($aEntries, function(&$v, $k){
				$v = $this->EscapeString($k) . ' ' . $this->EscapeString($v);
			});
			$arguments[] = '(' . \implode(' ', $aEntries) . ')';

			$this->SendRequestGetResponse('SETMETADATA', $arguments);
		}
	}

}
