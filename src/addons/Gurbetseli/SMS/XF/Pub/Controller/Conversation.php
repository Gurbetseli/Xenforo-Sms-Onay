<?php

namespace Gurbetseli\SMS\XF\Pub\Controller;

use XF\Mvc\ParameterBag;

class Conversation extends XFCP_Conversation
{

    public function actionView(ParameterBag $params)
    {
        $response = parent::actionView($params);

        $visitor = \XF::visitor();

        $smsLimitConversation = false;

        $limitConversation = $this->app->options()->gurbetseli_sms_limit_conversation;

        if ($limitConversation && !$this->alreadyGroup($visitor)) 
            $smsLimitConversation = true;
        
        $response->setParam('smsLimitConversation', $smsLimitConversation);

        return $response;
    }



    public function actionAdd()
    {
		if ($this->requiresSmsVerification())
            return $this->error(\XF::phrase('gurbetseli_sms_need_verification', ['link' => $this->buildLink('account/sms')]));
		return parent::actionAdd();
    }

    public function actionAddReply(ParameterBag $params)
    {
		if ($this->requiresSmsVerification())
            return $this->error(\XF::phrase('gurbetseli_sms_need_verification', ['link' => $this->buildLink('account/sms')]));
		return parent::actionAddReply($params);
    }

    public function actionReply(ParameterBag $params)
    {
		if ($this->requiresSmsVerification())
            return $this->error(\XF::phrase('gurbetseli_sms_need_verification', ['link' => $this->buildLink('account/sms')]));
		return parent::actionReply($params);
    }

    public function actionInvite(ParameterBag $params)
    {
		if ($this->requiresSmsVerification())
            return $this->error(\XF::phrase('gurbetseli_sms_need_verification', ['link' => $this->buildLink('account/sms')]));
		return parent::actionInvite($params);
    }

    public function actionEdit(ParameterBag $params)
    {
		if ($this->requiresSmsVerification())
            return $this->error(\XF::phrase('gurbetseli_sms_need_verification', ['link' => $this->buildLink('account/sms')]));
		return parent::actionEdit($params);
    }

    public function actionLeave(ParameterBag $params)
    {
		if ($this->requiresSmsVerification())
            return $this->error(\XF::phrase('gurbetseli_sms_need_verification', ['link' => $this->buildLink('account/sms')]));
		return parent::actionLeave($params);
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

    protected function requiresSmsVerification()
    {
        $visitor = \XF::visitor();
        return $visitor['user_id']
            && $this->app->options()->gurbetseli_sms_limit_conversation
            && !$this->alreadyGroup($visitor);
    }

}
