<?php
namespace Joomla\Component\Crmstages\View\CompanyCard;


use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
	protected $data;

	function display($tpl = null)
	{
		$this->data = $this->get('data');
		parent::display($tpl);
	}
}
