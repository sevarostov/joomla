<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Component\Crmstages\Helper\StageHelper;

?>

<div class="company-card container mt-4">
	<h2 class="mb-4"><?php echo Text::_('COM_CRMSTAGES_COMPANY_CARD'); ?></h2>


	<!-- Current Stage -->
	<div class="card mb-4">
		<div class="card-header bg-primary text-white">
			<h5><?php echo Text::_('COM_CRMSTAGES_CURRENT_STAGE'); ?></h5>
		</div>
		<div class="card-body">
			<?php if ($this->data['current_stage']): ?>
				<p><strong><?php echo Text::_('COM_CRMSTAGES_STAGE_NAME'); ?>:</strong>
					<?php echo $this->data['current_stage']['name']; ?></p>
				<p><strong><?php echo Text::_('COM_CRMSTAGES_STAGE_CODE'); ?>:</strong>
					<code><?php echo $this->data['current_stage']['code']; ?></code></p>
				<p><strong><?php echo Text::_('COM_CRMSTAGES_ENTERED_ON'); ?>:</strong>
					<?php
					echo (new \DateTime($this->data['current_stage']['created']))
						->format('F j, Y \a\t g:i a');
					?>
				</p>
			<?php else: ?>
				<p class="text-muted"><?php echo Text::_('COM_CRMSTAGES_NO_STAGE_ASSIGNED'); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<!-- Available Actions -->
	<?php if (!empty($this->data['actions'])): ?>
		<div class="card mb-4">
			<div class="card-header bg-success text-white">
				<h5><?php echo Text::_('COM_CRMSTAGES_AVAILABLE_ACTIONS'); ?></h5>
			</div>
			<div class="card-body">
				<ul class="list-group">
					<?php foreach ($this->data['actions'] as $action): ?>
						<li class="list-group-item d-flex justify-content-between align-items-center">
							<div>
								<strong><?php echo Text::_($action['title']); ?></strong>
								<?php if (isset($action['description'])): ?>
									<div class="mt-1 text-muted small">
										<?php echo Text::_($action['description']); ?>
									</div>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	<?php else: ?>
		<p class="alert alert-info">
			<?php echo Text::_('COM_CRMSTAGES_NO_ACTIONS_AVAILABLE'); ?>
		</p>
	<?php endif; ?>



	<!-- Manager Instructions -->
	<div class="card mb-4">
		<div class="card-header bg-info text-white">
			<h5><?php echo Text::_('COM_CRMSTAGES_MANAGER_INSTRUCTIONS'); ?></h5>
		</div>
		<div class="card-body">
			<?php if ($this->data['instructions']): ?>
				<?php echo $this->data['instructions']; // Already HTML-formatted ?>
			<?php else: ?>
				<p class="text-muted">
					<?php echo Text::_('COM_CRMSTAGES_NO_INSTRUCTIONS_FOR_STAGE'); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>

	<!-- Event History -->
	<?php if (!empty($this->data['logs'])): ?>
		<div class="card">
			<div class="card-header bg-secondary text-white">
				<h5><?php echo Text::_('COM_CRMSTAGES_EVENT_HISTORY'); ?></h5>
			</div>
			<div class="card-body p-0">
				<div class="list-group list-group-flush">
					<?php foreach ($this->data['logs'] as $event): ?>
						<div class="list-group-item">
							<small class="text-muted d-block mb-1">
								<?php
								echo (new \DateTime($event->created))
									->format('M j, Y \a\t H:i');
								?>
							</small>
							<h6 class="mb-1"><?php echo htmlspecialchars($event->stage_name ?? ''); ?></h6>
							<p class="mb-0 small">
								<strong><?php echo Text::_('COM_CRMSTAGES_EVENT'); ?>:</strong>
								<?php echo htmlspecialchars($event->event_code); ?>
							</p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php else: ?>
		<p class="alert alert-warning">
			<?php echo Text::_('COM_CRMSTAGES_NO_HISTORY_RECORDED'); ?>
		</p>
	<?php endif; ?>
</div>
