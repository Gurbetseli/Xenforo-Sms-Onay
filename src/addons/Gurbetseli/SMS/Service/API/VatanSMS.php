<?php

namespace Gurbetseli\SMS\Service\API;

use XF\Service\AbstractService;

class VatanSMS extends AbstractService
{
	public function sendCode($phoneNumber, $smsCode)
	{
	    
        	$customerNumber = $this->app->options()->gurbetseli_sms_api['vatansmscnumber'];
        	$customerName = $this->app->options()->gurbetseli_sms_api['vatansmscname'];
        	$customerPass = $this->app->options()->gurbetseli_sms_api['vatansmscpass'];
        	$senderName = $this->app->options()->gurbetseli_sms_api['vatansmssname'];
        	$smsPrefix = $this->app->options()->gurbetseli_sms_api['vatansmssprefix'];
        	    
        $postUrl='https://panel.vatansms.com/panel/smsgonder1Npost.php';
                
                $TUR='Normal';  // Normal veya Türkçe
                
                $mesaj= $smsPrefix . ' ' . $smsCode;

                
                $xml = '<sms>
                <kno>'. $this->xmlEscape($customerNumber) .'</kno>
                <kulad>'. $this->xmlEscape($customerName) .'</kulad>
                <sifre>'.$this->xmlEscape($customerPass) .'</sifre>
                <gonderen>'. $this->xmlEscape($senderName) .'</gonderen>
                <mesaj>'. $this->xmlEscape($mesaj) .'</mesaj>
                <numaralar>'. $this->xmlEscape($phoneNumber) .'</numaralar>
                <tur>'. $this->xmlEscape($TUR) .'</tur>
                </sms>';
                $postData = http_build_query(['data' => $xml], '', '&', PHP_QUERY_RFC3986);
                        
                $Veriler =  $xmlString;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $postUrl);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded; charset=UTF-8']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                $response = curl_exec($ch);
                $error = curl_error($ch);
                $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
				if ($response === false || $status < 200 || $status >= 300)
				{
					\XF::logError('Gurbetseli SMS VatanSMS bağlantı hatası (' . $status . '): ' . $error);
					return false;
				}

				$response = trim((string)$response);
				if (!preg_match('/^1:/', $response))
				{
					\XF::logError('Gurbetseli SMS VatanSMS gönderim hatası: ' . substr($response, 0, 160));
					return false;
				}

				return true;
	}

	protected function xmlEscape($value)
	{
		return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
	}


}
