<?php
namespace Joomla\Component\Crmstages\Administrator\View\CompanyCard;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
	protected $data;

	/**
	 * Constructor
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since 6.0.0
	 */
	public function __construct(array $config)
	{
		if (empty($config['option'])) {
			$config['option'] = 'com_crmstages';
		}

		parent::__construct($config);
	}

	public function display($tpl = null)
	{
		$this->data = $this->get('data');
		parent::display($tpl);
	}



}
