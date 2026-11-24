<?php

namespace Gurbetseli\SMS\Service\API;

use XF\Service\AbstractService;

class HizliSMS extends AbstractService
{
	public function sendCode($phoneNumber, $smsCode)
	{
	    
	$customerName = $this->app->options()->gurbetseli_sms_api['hizlismscname'];
	$customerPass = $this->app->options()->gurbetseli_sms_api['hizlismscpass'];
	$senderName = $this->app->options()->gurbetseli_sms_api['hizlismssname'];
	$smsPrefix = $this->app->options()->gurbetseli_sms_api['hizlismssprefix'];
	    
	            $postUrl='https://hizlisms.com.tr/api-v3/xml_api.php';
        
        $mesaj= $smsPrefix . ' ' . $smsCode;
        $numara= $phoneNumber;
        
        $xmlString='<SMS>
                        <oturum>
                            <kullanici>'. $this->xmlEscape($customerName) .'</kullanici>
                            <sifre>'. $this->xmlEscape($customerPass) .'</sifre>
                        </oturum>
                        <mesaj>
                            <baslik>'. $this->xmlEscape($senderName) .'</baslik>
                            <metin>'. $this->xmlEscape($mesaj) .'</metin>
                            <alicilar>'. $this->xmlEscape($numara) .'</alicilar>
                            <tarih></tarih>
                        </mesaj>
                        <karaliste></karaliste>
                        <izin_link></izin_link>
                        <izin_telefon></izin_telefon>
                    </SMS>';
                
        $Veriler =  $xmlString;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $Veriler);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml; charset=UTF-8']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
		if ($response === false || $status < 200 || $status >= 300)
		{
			\XF::logError('Gurbetseli SMS HizliSMS bağlantı hatası (' . $status . '): ' . $error);
			return false;
		}
		if (trim((string)$response) === '')
		{
			\XF::logError('Gurbetseli SMS HizliSMS boş yanıt döndürdü (' . $status . ').');
			return false;
		}

		return true;
	}

	protected function xmlEscape($value)
	{
		return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
	}


}
