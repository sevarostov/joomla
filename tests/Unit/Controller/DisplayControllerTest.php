<?php

namespace App\Tests\Unit\Controller;

use Couchbase\View;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Component\Crmstages\Administrator\Controller\DisplayController;
use Joomla\Component\Crmstages\Administrator\Model\CompanyModel;
use Joomla\Input\Input;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(DisplayController::class)]
#[CoversMethod(DisplayController::class, 'display')]
class DisplayControllerTest extends TestCase
{
	protected $controller;
	protected $app;
	protected $input;
	protected $factory;

	/**
	 * @throws Exception
	 */
	protected function setUp(): void
	{
		$this->app = $this->createMock(AdministratorApplication::class);
		$this->input = $this->createMock(Input::class);
		$this->factory = $this->createMock(MVCFactory::class);

		$this->controller = new DisplayController([
			'app' => $this->app,
			'factory' => $this->factory,
		]);
	}

	public function testDisplayRequiresId(): void
	{
		$response = $this->controller->display();
		$this->assertInstanceOf(JsonResponse::class, $response);
		$this->assertEquals(400, $response->data);
		$this->assertEquals('Company ID is required', $response->message);
	}

	public function testDisplayReturns404IfCompanyNotFound(): void
	{
		$jinput = Factory::getApplication()->getInput();
		$jinput->set('id', 100);
		$response = $this->controller->display();
		$this->assertInstanceOf(JsonResponse::class, $response);
		$this->assertEquals(404, $response->data);
		$this->assertEquals('Company not found', $response->message);
	}

	public function testDisplayHandlesException(): void
	{
		$jinput = Factory::getApplication()->getInput();
		$jinput->set('id', 1);
		$response = $this->controller->display();
		$this->assertInstanceOf(JsonResponse::class, $response);
		$this->assertEquals(500, $response->data);
		$this->assertEquals('An error occured', $response->message);
	}

	public function testDisplayRendersViewWithData(): void
	{
		$data = [
			'company_id' => 1,
			'current_stage' => ['id' => 3, 'code' => 'C2', 'name' => 'Aware', 'ordering' => 3],
			'actions' => [[
				'code' => 'filling_out_discovery_form',
				'title' => 'COM_CRMSTAGES_ACTION_DISCOVERY_FORM',
				'description' => 'Fill out the discovery form']],
			'instructions' => '<p>**Instructions for Aware Stage**</p>
			<ul>
			<li>**Instructions for Aware Stage 1**</li><li>**Instructions for Aware Stage 2**</li>
			<li>**Instructions for Aware Stage 3**</li></ul>
			<p>
			<em>**Note For Aware Stage**
			</em>
			</p>',
			'logs' => [['created'=>'2026-02-17 13:27:21','stage_name'=>'Aware']],
			'last_stage' => false,
		];

		$view = $this->controller
			->getView(
				'CompanyCard',
				'html',
				'administrator',
				['template_path' => '/var/www/html/administrator/components/com_crmstages/tmpl/companycard/', 'layout' => 'default'],
			);
		$view->set('data', $data);
		ob_start();
		$view->display();
		$result = ob_get_contents();
		ob_end_clean();

		$this->assertStringContainsString('COM_CRMSTAGES_COMPANY_CARD', $result);
	}
}
