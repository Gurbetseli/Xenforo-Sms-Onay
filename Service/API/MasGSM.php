<?php

namespace Gurbetseli\SMS\Service\API;

use XF\Service\AbstractService;

class MasGSM extends AbstractService
{
	const API_URL = 'https://api.senagsm.com.tr/api/smspost/v1';

	public function sendCode($phoneNumber, $smsCode)
	{
		$api = $this->app->options()->gurbetseli_sms_api;
		$username = trim((string) $api['masgsmcname']);
		$password = (string) $api['masgsmcpass'];
		$sender = trim((string) $api['masgsmsname']);
		$prefix = trim((string) $api['masgsmsprefix']);

		$number = preg_replace('/\D+/', '', (string) $phoneNumber);
		if (strlen($number) === 10 && $number[0] === '5')
		{
			$number = '90' . $number;
		}
		elseif (strlen($number) === 11 && $number[0] === '0' && $number[1] === '5')
		{
			$number = '90' . substr($number, 1);
		}

		$message = trim($prefix . ' ' . $smsCode);
		$xml = '<?xml version="1.0" encoding="UTF-8"?>'
			. '<sms>'
			. '<username>' . $this->xmlEscape($username) . '</username>'
			. '<password>' . $this->xmlEscape($password) . '</password>'
			. '<header>' . $this->xmlEscape($sender) . '</header>'
			. '<validity>2880</validity>'
			. '<message><gsm><no>' . $this->xmlEscape($number) . '</no></gsm>'
			. '<msg>' . $this->xmlEscape($message) . '</msg></message>'
			. '</sms>';

		$curl = curl_init(self::API_URL);
		curl_setopt_array($curl, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $xml,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				'Content-Type: text/xml; charset=UTF-8',
				'Content-Encoding: UTF-8',
				'Accept: text/plain',
			],
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
		]);

		$response = curl_exec($curl);
		$error = curl_error($curl);
		$status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		if ($response === false)
		{
			\XF::logError('Gurbetseli SMS MasGSM bağlantı hatası (' . $status . '): ' . $error);
			return;
		}

		$response = trim((string) $response);
		if (!preg_match('/^00(?:\s+\d+)?$/', $response))
		{
			\XF::logError('Gurbetseli SMS MasGSM gönderim hatası (' . $status . '): ' . substr($response, 0, 120));
			return false;
		}

		return true;
	}

	protected function xmlEscape($value)
	{
		return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
	}
}
