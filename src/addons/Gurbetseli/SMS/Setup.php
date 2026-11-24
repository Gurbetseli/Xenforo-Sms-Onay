<?php

namespace Gurbetseli\SMS;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
	use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

	public function installStep1()
	{
		$this->schemaManager()->createTable('gurbetseli_sms', function (Create $table) {
			$table->addColumn('user_id', 'int')->primaryKey();
			$table->addColumn('phone_number', 'varchar', 120)->setDefault('');
			$table->addColumn('sms_date', 'int')->setDefault(0);
			$table->addColumn('sms_code', 'varchar', 120)->setDefault('');
			$table->addColumn('verify_date', 'int')->setDefault(0);
			$table->addColumn('sms_attempts', 'int')->setDefault(0);
		});
	}

	public function upgrade00003Step1()
	{
		if ($this->fetchRow("SELECT * FROM xf_addon WHERE addon_id = 'smactivation'"))
		{
			$this->query("UPDATE xf_addon SET active = 0 WHERE addon_id = 'smactivation'");
			$this->query("INSERT INTO gurbetseli_sms (user_id, phone_number) SELECT user_id, phone FROM xf_user WHERE phone > 0");
		}

		$this->schemaManager()->alterTable('gurbetseli_sms', function (Alter $table) {
			$table->addColumn('verify_date', 'int')->setDefault(0);
		});
	}

	public function upgrade2000031Step1()
	{
		// Not: Gerekli yükseltme adımları burada tanımlanır.
	}

	public function upgrade2000071Step1()
	{
		$this->schemaManager()->alterTable('gurbetseli_sms', function (Alter $table) {
			$table->addColumn('sms_attempts', 'int')->setDefault(0);
		});
	}

	public function uninstall(array $stepParams = [])
	{
		$this->schemaManager()->dropTable('gurbetseli_sms');

		$this->query("DELETE
                    FROM xf_user_group_change
                    WHERE change_key = 'gurbetselismsverified'");
	}
}
