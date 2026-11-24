<?php

namespace Gurbetseli\SMS\XF\Pub\Controller;

use XF\Mvc\ParameterBag;

class Thread extends XFCP_Thread
{
    public function actionIndex(ParameterBag $params)
    {
        $response = parent::actionIndex($params);

        $visitor = \XF::visitor();
        
        $response->setParam('smsLimitThreadReply', $this->checkLimitThreadReply($visitor, $params));

        return $response;
    }

	public function actionAddReply(ParameterBag $params)
    {
        $visitor = \XF::visitor(); 
        
        if($visitor['user_id'] == 0)
            return parent::actionAddReply($params);
        
        if ($this->checkLimitThreadReply($visitor, $params))
            return $this->error(\XF::phrase('gurbetseli_sms_need_verification', ['link' => $this->buildLink('account/sms')]));
        
		return parent::actionAddReply($params);
    }

    public function actionReply(ParameterBag $params)
    {
        $visitor = \XF::visitor(); 
        
        if($visitor['user_id'] == 0)
            return parent::actionReply($params);

        if ($this->checkLimitThreadReply($visitor, $params))
            return $this->error(\XF::phrase('gurbetseli_sms_need_verification', ['link' => $this->buildLink('account/sms')]));
        
		return parent::actionReply($params);
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

    public function checkLimitThreadReply($visitor, $params)
    {
        $options = $this->app->options();
        $limitCreateThread = $options->gurbetseli_sms_limit_createthread;
        $limitReply = $options->gurbetseli_sms_limit_replythread;
        
        if (($limitCreateThread || $limitReply) && !$this->alreadyGroup($visitor)) 
        {
            $limitForums = (array)$options->gurbetseli_sms_limit_replythread_forums;
            $db = \XF::db();

            $nodeId = $db->fetchOne('SELECT node_id
                                        FROM xf_thread
                                        WHERE thread_id = ?
                                     ', $params->thread_id);
            if (in_array($nodeId, $limitForums)
                || (!empty($limitForums) && in_array('0', $limitForums))
                || empty($limitForums))
                return true;
        }
   
        return false;
    }

}
