<?php

namespace Gurbetseli\SMS\Service\API;

use XF\Service\AbstractService;

class NetGSM extends AbstractService
{
	public function sendCode($phoneNumber, $smsCode)
	{
	    
    	$customerName = $this->app->options()->gurbetseli_sms_api['netgsmcname'];
    	$customerPass = $this->app->options()->gurbetseli_sms_api['netgsmcpass'];
    	$senderName = $this->app->options()->gurbetseli_sms_api['netgsmsname'];
    	$smsPrefix = $this->app->options()->gurbetseli_sms_api['netgsmsprefix'];
    	    
        $message = $smsPrefix . ' ' . $smsCode;
        $number = "90" . $phoneNumber;
        
	    $xmlData='<?xml version="1.0" encoding="UTF-8"?>
            <mainbody>
                <header>
                        <company>NETGSM</company>
                    <usercode>' . $this->xmlEscape($customerName) .'</usercode>
                    <password>' . $this->xmlEscape($customerPass) .'</password>
                        <startdate></startdate>
                        <stopdate></stopdate>
                    <type>1:n</type>
                    <msgheader>' . $this->xmlEscape($senderName) .'</msgheader>
                    </header>
                        <body>
                        <msg>' . $this->xmlEscape($message) . '</msg>
                        <no>' . $this->xmlEscape($number) . '</no>
                        </body>
            </mainbody>';
        
        // XML içinde aşağıdaki alanları da gönderebilirsiniz.
        //<zaman>'. $ZAMAN.'</zaman> İleri tarihli gönderim için kullanabilirsiniz.
        
        $PostAddress = "https://api.netgsm.com.tr/xmlbulkhttppost.asp";
        
        $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$PostAddress);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml; charset=UTF-8"));
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
                $response = curl_exec($ch);
                $error = curl_error($ch);
                $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $status < 200 || $status >= 300)
        {
            \XF::logError('Gurbetseli SMS NetGSM bağlantı hatası (' . $status . '): ' . $error);
            return false;
        }

        $response = trim((string)$response);
        if (!preg_match('/^00(?:\s+\S+)?$/', $response))
        {
            \XF::logError('Gurbetseli SMS NetGSM gönderim hatası: ' . substr($response, 0, 160));
            return false;
        }

        return true;
    }

    protected function xmlEscape($value)
    {
        return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
