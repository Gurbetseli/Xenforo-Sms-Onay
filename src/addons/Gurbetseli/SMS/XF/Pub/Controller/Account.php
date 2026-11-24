<?php

namespace Gurbetseli\SMS\XF\Pub\Controller;

class Account extends XFCP_Account
{
	const SMS_CODE_MAX_ATTEMPTS = 5;

	public function actionSignature()
	{
        $visitor = \XF::visitor(); 
        
        if($visitor['user_id'] == 0)
            return parent::actionSignature();
        
       $limitSignature = $this->app->options()->gurbetseli_sms_limit_signature;
        
        if ($limitSignature && !$this->alreadyGroup($visitor)) 
            return $this->error(\XF::phrase('gurbetseli_sms_need_verification', ['link' => $this->buildLink('account/sms')]));
        
        return parent::actionSignature();

	}

	public function actionSMS()
	{
		$visitor = \XF::visitor();
		if(!$this->alreadyGroup($visitor))
		{
			$userId = $visitor['user_id'];
			
			if($this->alreadyCodeRequested($userId))
			{
				return $this->redirect($this->buildLink('account/sms/checkcode'));
			}
			else
			{
				return $this->redirect($this->buildLink('account/sms/sendcode'));
			}
		}

		$view = $this->view('Gurbetseli\SMS:SMS', 'gurbetseli_sms_alreadyverified');
		return $this->addAccountWrapperParams($view, 'gurbetseli_sms');
	}

	public function actionSmsSendCode()
	{

		$visitor = \XF::visitor();

		if($this->alreadyGroup($visitor))
		{
			$view = $this->view('Gurbetseli\SMS:SMS', 'gurbetseli_sms_alreadyverified');
			return $this->addAccountWrapperParams($view, 'gurbetseli_sms');
		}

		$userId = $visitor['user_id'];

		if ($this->canSendSMS($userId))
		{
			$view = $this->view('Gurbetseli\SMS:SMS', 'gurbetseli_sms_sendcode');
			return $this->addAccountWrapperParams($view, 'gurbetseli_sms');
		}
		else
		{
			return $this->redirect($this->buildLink('account/sms/checkcode'));
		}

	}

	public function actionSmsRequestCode()
	{
		$this->assertPostOnly();

		$input = $this->filter([
			'gurbetseli_sms' => [
				'phone_number' => 'str'
			]
		]);
		
		$phoneNumber = $input['gurbetseli_sms']['phone_number'];
				
		$visitor = \XF::visitor();
		$userId = $visitor['user_id'];
		if (!$userId)
		{
			return $this->error(\XF::phrase('no_permission'));
		}

		if ($this->alreadyGroup($visitor))
		{
			return $this->redirect($this->buildLink('account/sms'));
		}

		$phoneNumber = preg_replace('/\D+/', '', (string)$phoneNumber);
		if (!preg_match('/^5[0-9]{9}$/D', $phoneNumber))
		{
			return $this->error(\XF::phrase('gurbetseli_sms_phone_invalid'));
		}

		if (!$this->canSendSMS($userId))
		{
			return $this->redirect($this->buildLink('account/sms/checkcode'));
		}
		
		$smsDate = \XF::$time;
		$smsCode = random_int(10000000, 99999999);
		if ($this->alreadyUsedPhone($phoneNumber, $userId))
		{
		    return $this->redirect($this->buildLink('account/sms/sendcode'));
		}
		
		$apiService = null;
		if (!empty($this->app->options()->gurbetseli_sms_api['vatansmscnumber']))
		{
		    $apiService = $this->service('Gurbetseli\SMS:API\VatanSMS');
		}
		    
		if (!empty($this->app->options()->gurbetseli_sms_api['netgsmcname']))
		{
		    $apiService = $this->service('Gurbetseli\SMS:API\NetGSM');
		}
		
		if (!empty($this->app->options()->gurbetseli_sms_api['hizlismscname']))
		{
		    $apiService = $this->service('Gurbetseli\SMS:API\HizliSMS');
		}
		
		if (!empty($this->app->options()->gurbetseli_sms_api['mutlucellcname']))
		{
		    $apiService = $this->service('Gurbetseli\SMS:API\Mutlucell');
		}

		if (!empty($this->app->options()->gurbetseli_sms_api['masgsmcname']))
		{
		    $apiService = $this->service('Gurbetseli\SMS:API\MasGSM');
		}

		if (!$apiService)
		{
			return $this->error(\XF::phrase('gurbetseli_sms_api_not_configured'));
		}

		$db = \XF::db();
		$codeHash = password_hash((string)$smsCode, PASSWORD_DEFAULT);
		$db->query('
			INSERT INTO gurbetseli_sms
				(user_id, phone_number, sms_date, sms_code, verify_date, sms_attempts)
			VALUES
				(?, ?, ?, ?, ?, ?)
			ON DUPLICATE KEY UPDATE phone_number = VALUES(phone_number), sms_date = VALUES(sms_date), sms_code = VALUES(sms_code), verify_date = VALUES(verify_date), sms_attempts = VALUES(sms_attempts)
		', [$userId, $phoneNumber, $smsDate, $codeHash, 0, 0]);

		if (!$apiService->sendCode($phoneNumber, $smsCode))
		{
			return $this->error(\XF::phrase('gurbetseli_sms_send_failed'));
		}

		return $this->redirect($this->buildLink('account/sms/checkcode'));



	}


	public function actionSmsCheckCode()
	{
		$visitor = \XF::visitor();
		if($this->alreadyGroup($visitor))
		{
			$view = $this->view('Gurbetseli\SMS:SMS', 'gurbetseli_sms_alreadyverified');
			return $this->addAccountWrapperParams($view, 'gurbetseli_sms');
		}

		$visitor = \XF::visitor();
		$userId = $visitor['user_id'];
		if(!$this->alreadyCodeRequested($userId) || $this->canSendSMS($userId))
		{
			return $this->redirect($this->buildLink('account/sms/sendcode'));
		}

		$view = $this->view('Gurbetseli\SMS:SMS\SMSCheckCode', 'gurbetseli_sms_checkcode');

		return $this->addAccountWrapperParams($view, 'gurbetseli_sms_checkcode');
	}



	public function actionSmsVerifyCode()
	{
		$this->assertPostOnly();

		$input = $this->filter([
			'gurbetseli_sms' => [
				'code' => 'str'
			]
		]);
		
		$inputCode = $input['gurbetseli_sms']['code'];
				
		$visitor = \XF::visitor();
		$userId = $visitor['user_id'];
		

		
		
		$db = \XF::db();

		$codeRow = $db->fetchRow('
			SELECT sms_code, sms_date, verify_date, sms_attempts
			FROM gurbetseli_sms
			WHERE user_id = ?
		', $userId);

		if (!$codeRow || $codeRow['verify_date'] || !$codeRow['sms_code'])
		{
			return $this->redirect($this->buildLink('account/sms/sendcode'));
		}

		$codeLifetime = max(600, (int)$this->app->options()->gurbetseli_sms_smsinterval * 3600);
		if ((int)$codeRow['sms_date'] + $codeLifetime < \XF::$time
			|| (int)$codeRow['sms_attempts'] >= self::SMS_CODE_MAX_ATTEMPTS)
		{
			return $this->error(\XF::phrase('gurbetseli_sms_code_expired'));
		}

		if (!preg_match('/^[0-9]{8}$/D', $inputCode))
		{
			$inputCode = '';
		}

		$updated = $db->query('
			UPDATE gurbetseli_sms
			SET sms_attempts = sms_attempts + 1
			WHERE user_id = ? AND sms_attempts < ? AND verify_date = 0
		', [$userId, self::SMS_CODE_MAX_ATTEMPTS]);
		if (!$updated->rowsAffected())
		{
			return $this->error(\XF::phrase('gurbetseli_sms_code_expired'));
		}

		$isValidCode = password_verify($inputCode, $codeRow['sms_code']);
		// Eski sürümde bekleyen kodlar düz metindi; mevcut, süre içindeki kodların güncelleme sonrası çalışmasını sağlar.
		if (!$isValidCode && preg_match('/^[0-9]{8}$/D', $codeRow['sms_code']))
		{
			$isValidCode = hash_equals((string)$codeRow['sms_code'], $inputCode);
		}

		if ($isValidCode)
		{

			$verifyService = $this->service('Gurbetseli\SMS:Verifier');

			$verifyDate = \XF::$time;



			$db->query('UPDATE gurbetseli_sms
	                    SET verify_date = ?
	                    WHERE user_id = ?
			            ', [$verifyDate, $userId]);

			$verifyService->verifyCode($userId);
			return $this->redirect($this->buildLink('account/sms'));

		}
		
		return $this->redirect($this->buildLink('account/sms/checkcode'));

	}

	// Kullanıcının SMS ile doğrulanmış grupta olup olmadığını kontrol eder.
	public function alreadyGroup($visitor)
    {
    	$verifiedUserGroup = $this->app->options()->gurbetseli_sms_usergroup;
    	$visitorPrimary = $visitor['user_group_id'];
    	

    	if ($verifiedUserGroup == $visitor['user_group_id'] || in_array($verifiedUserGroup, $visitor['secondary_group_ids'])) {
    		return true;
    	}
   
        return false;
    }

    // Kullanıcının daha önce kod isteyip istemediğini kontrol eder.
    public function alreadyCodeRequested($userId)
    {    	
    	$db = \XF::db();

		if ($db->fetchOne('
			SELECT user_id
			FROM gurbetseli_sms
			WHERE user_id = ?
			', $userId))
    	{
    		return true;
    	}
   
        return false;
    }

    // Kullanıcının son kod isteğinden bu yana belirlenen sürenin geçip geçmediğini kontrol eder.
    public function canSendSMS($userId)
    {
    	if($this->alreadyCodeRequested($userId))
    	{    	
	    	$db = \XF::db();

	    	$smsDate = $db->fetchOne('
				SELECT sms_date
				FROM gurbetseli_sms
				WHERE user_id = ?
				', $userId);

	    	$currentDate = \XF::$time;

			$minimumInterval = max(600, (int)$this->app->options()->gurbetseli_sms_smsinterval * 3600);
			if ($minimumInterval > ($currentDate - (int)$smsDate))
	            return false;
        }

        return true;

    }

    // Girilen telefon numarasının başka bir kullanıcı tarafından kullanılıp kullanılmadığını kontrol eder.
    public function alreadyUsedPhone($phoneNumber, $userId)
    {
    	$db = \XF::db();

    	if ($db->fetchOne('
			SELECT user_id
			FROM gurbetseli_sms
			WHERE phone_number = ? AND user_id != ?
			', array($phoneNumber, $userId)))
    	{
    		return true;
    	}

        return false;
    }

}
