<?php

namespace Joomla\Component\Crmstages\Administrator\Helper;

class Constants {
	// Stage Codes
	public const STAGE_ICE          = 'C0';
	public const STAGE_TOUCHED       = 'C1';
	public const STAGE_AWARE         = 'C2';
	public const STAGE_INTERESTED    = 'W1';
	public const STAGE_DEMO_PLANNED  = 'W2';
	public const STAGE_DEMO_DONE     = 'W3';
	public const STAGE_COMMITTED     = 'H1';
	public const STAGE_CUSTOMER     = 'H2';
	public const STAGE_ACTIVATED    = 'A1';
	public const STAGE_NULL         = 'N0';

	// Action Codes (for reference)
	public const ACTION_ATTEMPT_CONTACT        = 'attempt_of_contact';
	public const ACTION_CONVO_LPR_COMMENT     = 'conversation_with_lpr_comment';
	public const ACTION_DISCOVERY_FORM        = 'filling_out_discovery_form';
	public const ACTION_PLANNING_DEMO         = 'planning_demo';
	public const ACTION_DEMO_CONDUCTED        = 'demo_conducted';
	public const ACTION_INVOICE_ISSUED       = 'invoice_issued';
	public const ACTION_PAYMENT_RECEIVED      = 'payment_received';
	public const ACTION_ID_CARD_ISSUED      = 'first_id_card_issued';
}
