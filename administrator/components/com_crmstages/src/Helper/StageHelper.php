<?php
namespace Joomla\Component\Crmstages\Helper;

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class StageHelper
{
	/**
	 * Get configuration of all stages with their transition rules
	 *
	 * @return array
	 */
	public static function getStagesConfig()
	{
		return [
			'C0' => [ // Ice
				'name' => 'Ice',
				'allowed_transitions' => ['C1'],
				'required_events' => [],
				'blocked_transitions' => ['C2', 'W1', 'W2', 'W3', 'H1', 'H2', 'A1']
			],
			'C1' => [ // Touched
				'name' => 'Touched',
				'allowed_transitions' => ['C2'],
				'required_events' => ['attempt_of_contact', 'conversation_with_lpr_comment'],
				'blocked_transitions' => ['W1', 'W2', 'W3', 'H1', 'H2', 'A1']
			],
			'C2' => [ // Aware
				'name' => 'Aware',
				'allowed_transitions' => ['W1'],
				'required_events' => ['filling_out_discovery_form'],
				'blocked_transitions' => ['W2', 'W3', 'H1', 'H2', 'A1']
			],
			'W1' => [ // Interested
				'name' => 'Interested',
				'allowed_transitions' => ['W2'],
				'required_events' => ['planning_demo'],
				'blocked_transitions' => ['W3', 'H1', 'H2', 'A1']
			],
			'W2' => [ // demo_planned
				'name' => 'Demo Planned',
				'allowed_transitions' => ['W3'],
				'required_events' => ['demo_conducted'],
				'blocked_transitions' => ['H1', 'H2', 'A1']
			],
			'W3' => [ // Demo_done
				'name' => 'Demo Done',
				'allowed_transitions' => ['H1'],
				'required_events' => ['invoice_issued'],
				'blocked_transitions' => ['H2', 'A1']
			],
			'H1' => [ // Committed
				'name' => 'Committed',
				'allowed_transitions' => ['H2'],
				'required_events' => ['payment_received'],
				'blocked_transitions' => ['A1']
			],
			'H2' => [ // Customer
				'name' => 'Customer',
				'allowed_transitions' => ['A1'],
				'required_events' => ['first_id_card_issued'],
				'blocked_transitions' => []
			],
			'A1' => [ // Activated
				'name' => 'Activated',
				'allowed_transitions' => [],
				'required_events' => [],
				'blocked_transitions' => ['C0', 'C1', 'C2', 'W1', 'W2', 'W3', 'H1', 'H2']
			]
		];
	}

	/**
	 * Get available actions for current stage
	 *
	 * @param string $currentStageCode
	 *
	 * @return array
	 */
	public static function getAvailableActions($currentStageCode)
	{
		$actions = [];
		$config = self::getStagesConfig();

		if (!isset($config[$currentStageCode])) {
			return $actions;
		}

		switch ($currentStageCode) {
		case 'C0': // Ice
			$actions[] = [
				'code' => 'call',
				'title' => 'COM_CRMSTAGES_ACTION_CALL',
				'description' => 'Make initial contact attempt'
			];
			break;

		case 'C1': // Touched
			$actions[] = [
				'code' => 'conversation_lpr',
				'title' => 'COM_CRMSTAGES_ACTION_CONVERSATION_LPR',
				'description' => 'Have a conversation with the decision maker'
			];
			$actions[] = [
				'code' => 'discovery_form',
				'title' => 'COM_CRMSTAGES_ACTION_DISCOVERY_FORM',
				'description' => 'Fill out the discovery form'
			];
			break;

		case 'C2': // Aware
			$actions[] = [
				'code' => 'schedule_demo',
				'title' => 'COM_CRMSTAGES_ACTION_SCHEDULE_DEMO',
				'description' => 'Plan and schedule a demo presentation'
			];
			break;

		case 'W1': // Interested
			$actions[] = [
				'code' => 'confirm_demo',
				'title' => 'COM_CRMSTAGES_ACTION_CONFIRM_DEMO',
				'description' => 'Confirm demo date and time'
			];
			break;

		case 'W2': // demo_planned
			$actions[] = [
				'code' => 'conduct_demo',
				'title' => 'COM_CRMSTAGES_ACTION_CONDUCT_DEMO',
				'description' => 'Conduct the scheduled demo'
			];
			break;

		case 'W3': // Demo_done
			$actions[] = [
				'code' => 'issue_invoice',
				'title' => 'COM_CRMSTAGES_ACTION_ISSUE_INVOICE',
				'description' => 'Issue invoice for the service'
			];
			break;

		case 'H1': // Committed
			$actions[] = [
				'code' => 'receive_payment',
				'title' => 'COM_CRMSTAGES_ACTION_RECEIVE_PAYMENT',
				'description' => 'Process received payment'
			];
			break;

		case 'H2': // Customer
			$actions[] = [
				'code' => 'issue_id_card',
				'title' => 'COM_CRMSTAGES_ACTION_ISSUE_ID_CARD',
				'description' => 'Issue first ID card'
			];
			break;

			// Activated stage has no further actions
		case 'A1':
			break;
		}

		return $actions;
	}

	/**
	 * Get instructions/script for manager based on current stage
	 *
	 * @param string $stageCode
	 *
	 * @return string
	 */
	public static function getInstructions($stageCode)
	{
		$instructions = [
			'C0' => '<p>' . Text::_('COM_CRMSTAGES_INSTRUCTIONS_ICE') . '</p>' .
				'<p>' . Text::_('COM_CRMSTAGES_INSTRUCTION_ICE_1') . '</p>' .
				'<ul>' .
				'<li>' . Text::_('COM_CRMSTAGES_INSTRUCTION_ICE_2') . '</li>' .
				'<li>' . Text::_('COM_CRMSTAGES_INSTRUCTION_ICE_3') . '</li>' .
				'</ul>',

			'C1' => '<p>' . Text::_('COM_CRMSTAGES_INSTRUCTIONS_TOUCHED') . '</p>' .
				'<ol>' .
				'<li>' . Text::_('COM_CRMSTAGES_INSTRUCTION_TOUCHED_1') . '</li>' .
				'<li>' . Text::_('COM_CRMSTAGES_INSTRUCTION_TOUCHED_2') . '</li>' .
				'<li>' . Text::_('COM_CRMSTAGES_INSTRUCTION_TOUCHED_3') . '</li>' .
				'</ol>' .
				'<p><strong>' . Text::_('COM_CRMSTAGES_TIP') . ':</strong> ' .
				Text::_('COM_CRMSTAGES_TIP_TOUCHED') . '</p>',

			'C2' => '<p>' . Text::_('COM_CRMSTAGES_INSTRUCTIONS_AWARE') . '</p>' .
				'<ul>' .
				'<li>' . Text::_('COM_CRMSTAGES_INSTRUCTION_AWARE_1') . '</li>' .
				'<li>' . Text::_('COM_CRMSTAGES_INSTRUCTION_AWARE_2') . '</li>' .
				'<li>' . Text::_('COM_CRMSTAGES_INSTRUCTION_AWARE_3') . '</li>' .
				'</ul>' .
				'<p><em>' . Text::_('COM_CRMSTAGES_NOTE_AWARE') . '</em></p>',

			'W1' => '<p>' . Text::_('COM_CRMSTAGES_INSTRUCTIONS_INTERESTED') . '</p>' .
				'<p>' . Text::_('COM_CRMSTAGES_INSTRUCTION_INTERESTED_1') . '</p>' .
				'<p>' . Text::_('COM_CRMSTAGES_INSTRUCTION_INTERESTED_2') . '</p>' .
				'<blockquote>' . Text::_('COM_CRMSTAGES_QUOTE_INTERESTED') . '</blockquote>',

			'W2' => '<p>' . Text::_('COM_CRMSTAGES_INSTRUCTIONS_DEMO_PLANNED') . '</p>' .
				'<p>' . Text::_('COM_CRMSTAGES_INSTRUCTION_DEMO_PLANNED_1') . '</p>' .
				'<p>' . Text::_('COM_CRMSTAGES_INSTRUCTION_DEMO_PLANNED_2') . '</p>' .
				'<details>' .
				'<summary>' . Text::_('COM_CRMSTAGES_SUMMARY_PREPARE_DEMO') . '</summary>' .
				'<ul>' .
				'<li>' . Text::_('COM_CRMSTAGES_PREP_DEMO_1') . '</li>' .
				'<li>' . Text::_('COM_CRMSTAGES_PREP_DEMO_2') . '</li>' .
				'<li>' . Text::_('COM_CRMSTAGES_PREP_DEMO_3') . '</li>' .
				'</ul>' .
				'</details>',

			'W3' => '<p>' . Text::_('COM_CRMSTAGES_INSTRUCTIONS_DEMO_DONE') . '</p>' .
				'<p>' . Text::_('COM_CRMSTAGES_INSTRUCTION_DEMO_DONE_1') . '</p>' .
				'<p>' . Text::_('COM_CRMSTAGES_INSTRUCTION_DEMO_DONE_2') . '</p>' .
				'<hr>' .
				'<h5>' . Text::_('COM_CRMSTAGES_FOLLOWUP_HEADING') . '</h5>' .
				'<ul>' .
				'<li>' . Text::_('COM_CRMSTAGES_FOLLOWUP_1') . '</li>' .
				'<li>' . Text::_('COM_CRMSTAGES_FOLLOWUP_2') . '</li>' .
				'</ul>',

			'H1' => '<p>' . Text::_('COM_CRMSTAGES_INSTRUCTIONS_COMMITTED') . '</p>' .
				'<p>' . Text::_('COM_CRMSTAGES_INSTRUCTION_COMMITTED_1') . '</p>' .
				'<strong>' . Text::_('COM_CRMSTAGES_IMPORTANT') . ':</strong>' .
				'<ul>' .
				'<li>' . Text::_('COM_CRMSTAGES_IMPORTANT_1') . '</li>' .
				'<li>' . Text::_('COM_CRMSTAGES_IMPORTANT_2') . '</li>' .
				'</ul>',

			'H2' => '<p>' . Text::_('COM_CRMSTAGES_INSTRUCTIONS_CUSTOMER') . '</p>' .
				'<p>' . Text::_('COM_CRMSTAGES_INSTRUCTION_CUSTOMER_1') . '</p>' .
				'<div class="alert alert-info">' .
				Text::_('COM_CRMSTAGES_ALERT_CUSTOMER') .
				'</div>' .
				'<p>' . Text::_('COM_CRMSTAGES_INSTRUCTION_CUSTOMER_2') . '</p>',


			'A1' => '<p>' . Text::_('COM_CRMSTAGES_INSTRUCTIONS_ACTIVATED') . '</p>' .
				'<p>' . Text::_('COM_CRMSTAGES_INSTRUCTION_ACTIVATED_1') . '</p>' .
				'<ul>' .
				'<li>' . Text::_('COM_CRMSTAGES_INSTRUCTION_ACTIVATED_2') . '</li>' .
				'<li>' . Text::_('COM_CRMSTAGES_INSTRUCTION_ACTIVATED_3') . '</li>' .
				'</ul>' .
				'<p class="text-muted">' . Text::_('COM_CRMSTAGES_FINAL_NOTE') . '</p>'
		];

		// Return instructions for the given stage, or default message if stage not found
		return $instructions[$stageCode] ?? '<p>' . Text::_('COM_CRMSTAGES_INSTRUCTIONS_NOT_AVAILABLE') . '</p>';
	}
}
