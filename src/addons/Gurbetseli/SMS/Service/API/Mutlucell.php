<?php

namespace Gurbetseli\SMS\Service\API;

use XF\Service\AbstractService;

class Mutlucell extends AbstractService
{
	public function sendCode($phoneNumber, $smsCode)
	{
	    
        	$customerName = $this->app->options()->gurbetseli_sms_api['mutlucellcname'];
        	$customerPass = $this->app->options()->gurbetseli_sms_api['mutlucellcpass'];
        	$senderName = $this->app->options()->gurbetseli_sms_api['mutlucellsname'];
        	$smsPrefix = $this->app->options()->gurbetseli_sms_api['mutlucellsprefix'];
        	    
                $mesaj= $smsPrefix . ' ' . $smsCode;
                $numara= $phoneNumber;
                
                $xml_data ='<?xml version="1.0" encoding="UTF-8"?>'.
                '<smspack ka="' . $this->xmlEscape($customerName) . '" pwd="' . $this->xmlEscape($customerPass) . '" org="' . $this->xmlEscape($senderName) . '" >'.
                '<mesaj>'.
                '<metin>' . $this->xmlEscape($mesaj) . '</metin>'.
                '<nums>' . $this->xmlEscape($numara) .'</nums>'.
                '</mesaj>'.
                '</smspack>';
                
                
                $URL = "https://smsgw.mutlucell.com/smsgw-ws/sndblkex";
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $URL);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml; charset=UTF-8'));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $response = curl_exec($ch);
                $error = curl_error($ch);
                $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if ($response === false || $status < 200 || $status >= 300)
                {
                    \XF::logError('Gurbetseli SMS Mutlucell bağlantı hatası (' . $status . '): ' . $error);
                    return false;
                }

                $response = trim((string)$response);
                if (!preg_match('/^\$[0-9]+#/', $response))
                {
                    \XF::logError('Gurbetseli SMS Mutlucell gönderim hatası: ' . substr($response, 0, 160));
                    return false;
                }

                return true;
        }

        protected function xmlEscape($value)
        {
                return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        }

}
