<?php

namespace App\Tests\Unit\Helper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Joomla\Component\Crmstages\Administrator\Helper\StageHelper;
use Joomla\Component\Crmstages\Administrator\Helper\Constants;

#[CoversClass(StageHelper::class)]
#[CoversMethod(StageHelper::class, 'getStagesConfig')]
class StageHelperTest extends TestCase
{
	public function testGetStagesConfig()
	{
		$config = StageHelper::getStagesConfig();

		$this->assertArrayHasKey(Constants::STAGE_ICE, $config);
		$this->assertContains(Constants::STAGE_TOUCHED, $config[Constants::STAGE_ICE]['allowed_transitions']);
		$this->assertEmpty($config[Constants::STAGE_ICE]['required_events']);

		$this->assertArrayHasKey(Constants::STAGE_ACTIVATED, $config);
		$this->assertEmpty($config[Constants::STAGE_ACTIVATED]['allowed_transitions']);
	}

	public function testGetAvailableActions()
	{
		$actions = StageHelper::getAvailableActions(Constants::STAGE_ICE);
		$this->assertCount(1, $actions);
		$this->assertEquals(Constants::ACTION_ATTEMPT_CONTACT, $actions[0]['code']);

		$actions = StageHelper::getAvailableActions(Constants::STAGE_ACTIVATED);
		$this->assertCount(1, $actions);
		$this->assertEquals('', $actions[0]['code']);
	}
}
