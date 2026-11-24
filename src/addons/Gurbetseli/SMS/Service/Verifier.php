<?php

namespace Gurbetseli\SMS\Service;

use XF\Service\AbstractService;

class Verifier extends AbstractService
{
	public function verifyCode($userId)
	{
		\XF::app()->service('XF:User\UserGroupChange')
					->addUserGroupChange($userId, 'gurbetselismsverified', $this->app->options()->gurbetseli_sms_usergroup);
	}
}
