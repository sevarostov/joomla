<?php

namespace Joomla\Component\Crmstages\Administrator\Helper;

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class StageHelper
{
	/**
	 * Get configuration of all stages with their transition rules
	 *
	 * @return array
	 */
	public static function getStagesConfig(): array
	{
		return [
			Constants::STAGE_ICE => [ // Ice
				'name' => 'Ice',
				'allowed_transitions' => [Constants::STAGE_TOUCHED],
				'required_events' => [],
				'blocked_transitions' => [
					Constants::STAGE_AWARE,
					Constants::STAGE_INTERESTED,
					Constants::STAGE_DEMO_PLANNED,
					Constants::STAGE_DEMO_DONE,
					Constants::STAGE_COMMITTED,
					Constants::STAGE_CUSTOMER,
					Constants::STAGE_ACTIVATED
				]
			],
			Constants::STAGE_TOUCHED => [ // Touched
				'name' => 'Touched',
				'allowed_transitions' => [Constants::STAGE_AWARE],
				'required_events' => [],
				'blocked_transitions' => [
					Constants::STAGE_INTERESTED,
					Constants::STAGE_DEMO_PLANNED,
					Constants::STAGE_DEMO_DONE,
					Constants::STAGE_COMMITTED,
					Constants::STAGE_CUSTOMER,
					Constants::STAGE_ACTIVATED
				]
			],
			Constants::STAGE_AWARE => [ // Aware
				'name' => 'Aware',
				'allowed_transitions' => [Constants::STAGE_INTERESTED],
				'required_events' => [Constants::ACTION_CONVO_LPR_COMMENT],
				'blocked_transitions' => [
					Constants::STAGE_DEMO_PLANNED,
					Constants::STAGE_DEMO_DONE,
					Constants::STAGE_COMMITTED,
					Constants::STAGE_CUSTOMER,
					Constants::STAGE_ACTIVATED
				]
			],
			Constants::STAGE_INTERESTED => [ // Interested
				'name' => 'Interested',
				'allowed_transitions' => [Constants::STAGE_DEMO_PLANNED],
				'required_events' => [
					Constants::ACTION_DISCOVERY_FORM,
					Constants::ACTION_CONVO_LPR_COMMENT
				],
				'blocked_transitions' => [
					Constants::STAGE_DEMO_DONE,
					Constants::STAGE_COMMITTED,
					Constants::STAGE_CUSTOMER,
					Constants::STAGE_ACTIVATED
				]
			],
			Constants::STAGE_DEMO_PLANNED => [ // demo_planned
				'name' => 'Demo Planned',
				'allowed_transitions' => [Constants::STAGE_DEMO_DONE],
				'required_events' => [
					Constants::ACTION_PLANNING_DEMO,
					Constants::ACTION_DISCOVERY_FORM,
					Constants::ACTION_CONVO_LPR_COMMENT
				],
				'blocked_transitions' => [
					Constants::STAGE_COMMITTED,
					Constants::STAGE_CUSTOMER,
					Constants::STAGE_ACTIVATED
				]
			],
			Constants::STAGE_DEMO_DONE => [ // Demo_done
				'name' => 'Demo Done',
				'allowed_transitions' => [Constants::STAGE_COMMITTED],
				'required_events' => [
					Constants::ACTION_DEMO_CONDUCTED,
					Constants::ACTION_PLANNING_DEMO,
					Constants::ACTION_DISCOVERY_FORM,
					Constants::ACTION_CONVO_LPR_COMMENT
				],
				'blocked_transitions' => [
					Constants::STAGE_CUSTOMER,
					Constants::STAGE_ACTIVATED
				]
			],
			Constants::STAGE_COMMITTED => [ // Committed
				'name' => 'Committed',
				'allowed_transitions' => [Constants::STAGE_CUSTOMER],
				'required_events' => [
					Constants::ACTION_INVOICE_ISSUED,
					Constants::ACTION_DEMO_CONDUCTED,
					Constants::ACTION_PLANNING_DEMO,
					Constants::ACTION_DISCOVERY_FORM,
					Constants::ACTION_CONVO_LPR_COMMENT
				],
				'blocked_transitions' => [Constants::STAGE_ACTIVATED]
			],
			Constants::STAGE_CUSTOMER => [ // Customer
				'name' => 'Customer',
				'allowed_transitions' => [Constants::STAGE_ACTIVATED],
				'required_events' => [
					Constants::ACTION_PAYMENT_RECEIVED,
					Constants::ACTION_INVOICE_ISSUED,
					Constants::ACTION_DEMO_CONDUCTED,
					Constants::ACTION_PLANNING_DEMO,
					Constants::ACTION_DISCOVERY_FORM,
					Constants::ACTION_CONVO_LPR_COMMENT],
				'blocked_transitions' => []
			],
			Constants::STAGE_ACTIVATED => [ // Activated
				'name' => 'Activated',
				'allowed_transitions' => [],
				'required_events' => [
					Constants::ACTION_ID_CARD_ISSUED,
					Constants::ACTION_PAYMENT_RECEIVED,
					Constants::ACTION_INVOICE_ISSUED,
					Constants::ACTION_DEMO_CONDUCTED,
					Constants::ACTION_PLANNING_DEMO,
					Constants::ACTION_DISCOVERY_FORM,
					Constants::ACTION_CONVO_LPR_COMMENT],
				'blocked_transitions' => [
					Constants::STAGE_ICE,
					Constants::STAGE_TOUCHED,
					Constants::STAGE_AWARE,
					Constants::STAGE_INTERESTED,
					Constants::STAGE_DEMO_PLANNED,
					Constants::STAGE_DEMO_DONE,
					Constants::STAGE_COMMITTED,
					Constants::STAGE_CUSTOMER,]
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
	public static function getAvailableActions(string $currentStageCode): array
	{
		$actions = [];
		$config = self::getStagesConfig();

		if (!isset($config[$currentStageCode])) {
			return $actions;
		}

		switch ($currentStageCode) {
		case Constants::STAGE_ICE: // Ice
			$actions[] = [
				'code' => Constants::ACTION_ATTEMPT_CONTACT,
				'title' => 'COM_CRMSTAGES_ACTION_CALL',
				'description' => 'Make initial contact attempt'
			];
			break;

		case Constants::STAGE_TOUCHED: // Touched
			$actions[] = [
				'code' => Constants::ACTION_CONVO_LPR_COMMENT,
				'title' => 'COM_CRMSTAGES_ACTION_CONVERSATION_LPR',
				'description' => 'Have a conversation with the decision maker'
			];
			break;

		case Constants::STAGE_AWARE: // Aware
			$actions[] = [
				'code' => Constants::ACTION_DISCOVERY_FORM,
				'title' => 'COM_CRMSTAGES_ACTION_DISCOVERY_FORM',
				'description' => 'Fill out the discovery form'
			];
			break;

		case Constants::STAGE_INTERESTED: // Interested
			$actions[] = [
				'code' => Constants::ACTION_PLANNING_DEMO,
				'title' => 'COM_CRMSTAGES_ACTION_SCHEDULE_DEMO',
				'description' => 'Plan and schedule a demo presentation'
			];
			break;

		case Constants::STAGE_DEMO_PLANNED: // demo_planned
			$actions[] = [
				'code' => Constants::ACTION_DEMO_CONDUCTED,
				'title' => 'COM_CRMSTAGES_ACTION_CONFIRM_DEMO',
				'description' => 'Confirm demo date and time'
			];
			break;

		case Constants::STAGE_DEMO_DONE: // Demo_done
			$actions[] = [
				'code' => Constants::ACTION_INVOICE_ISSUED,
				'title' => 'COM_CRMSTAGES_ACTION_ISSUE_INVOICE',
				'description' => 'Issue invoice for the service'
			];
			break;

		case Constants::STAGE_COMMITTED: // Committed
			$actions[] = [
				'code' => Constants::ACTION_PAYMENT_RECEIVED,
				'title' => 'COM_CRMSTAGES_ACTION_RECEIVE_PAYMENT',
				'description' => 'Process received payment'
			];
			break;

		case Constants::STAGE_CUSTOMER: // Customer
			$actions[] = [
				'code' => Constants::ACTION_ID_CARD_ISSUED,
				'title' => 'COM_CRMSTAGES_ACTION_ISSUE_ID_CARD',
				'description' => 'Issue ID Card'

			];
			break;
			// Activated stage has no further actions
		case Constants::STAGE_ACTIVATED:
			$actions[] = [
				'code' => "",
				'title' => '',
				'description' => ''
			];
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
	public static function getInstructions(string $stageCode): string
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
