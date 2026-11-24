<?php

namespace Gurbetseli\SMS\XF\Pub\Controller;

use XF\Mvc\ParameterBag;

class Forum extends XFCP_Forum
{
	public function actionPostThread(ParameterBag $params)
	{
		$visitor = \XF::visitor();
        
        if($visitor['user_id'] == 0)
            return parent::actionPostThread($params);
        
        $options = $this->app->options();
        $limitThread = $options->gurbetseli_sms_limit_createthread;
        $restrictedForums = (array)$options->gurbetseli_sms_limit_replythread_forums;
        $nodeId = (int)$params->node_id;
        $isRestrictedForum = empty($restrictedForums)
            || in_array($nodeId, $restrictedForums)
            || (!empty($restrictedForums) && in_array('0', $restrictedForums));
        
        if ($limitThread && $isRestrictedForum && !$this->alreadyGroup($visitor))
            return $this->error(\XF::phrase('gurbetseli_sms_need_verification', ['link' => $this->buildLink('account/sms')]));
        
        return parent::actionPostThread($params);

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

}
