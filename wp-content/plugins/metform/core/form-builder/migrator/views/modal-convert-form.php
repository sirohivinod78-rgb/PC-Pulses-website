<?php
/**
 * MetForm Convert to Standalone Form Builder Confirmation Modal
 *
 * @package MetForm\Core\FormBuilder\Migrator\Views
 * @since 4.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="mf-convert-modal-overlay" class="mf-convert-modal-overlay" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="mf-convert-modal-title">
	<div class="mf-convert-modal-backdrop"></div>
	<div class="mf-convert-modal-card">
		<!-- Header -->
		<div class="mf-convert-modal-header">
			<h3 id="mf-convert-modal-title" class="mf-convert-modal-title">
				<?php esc_html_e( 'Switch to MetForm Builder', 'metform' ); ?>
			</h3>
			<button type="button" class="mf-convert-modal-close" aria-label="<?php esc_attr_e( 'Close dialog', 'metform' ); ?>">
				<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M12 4L4 12M4 4L12 12" stroke="#64748B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
		</div>

		<!-- Body -->
		<div class="mf-convert-modal-body">
			<!-- Form Target Info (Dashed box) - shown in both states -->
			<div class="mf-convert-target-form">
				<span class="mf-convert-target-label"><?php esc_html_e( 'Form:', 'metform' ); ?></span>
				<strong class="mf-convert-form-title"></strong>
			</div>

			<!-- Dynamic error alert container -->
			<div class="mf-convert-modal-error" style="display: none;"></div>

			<!-- State 1: Confirmation State -->
			<div class="mf-convert-state-initial">
				<!-- Info feature cards -->
				<div class="mf-convert-features">
					<div class="mf-convert-feature-item">
						<div class="mf-convert-feature-icon">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
							</svg>
						</div>
						<div class="mf-convert-feature-text">
							<strong><?php esc_html_e( 'Everything carries over', 'metform' ); ?></strong>
							<p><?php esc_html_e( 'Your fields, layouts, colors, and typography move over automatically.', 'metform' ); ?></p>
						</div>
					</div>
					<div class="mf-convert-feature-item">
						<div class="mf-convert-feature-icon">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F97316" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
								<path d="M9 12l2 2 4-4" />
							</svg>
						</div>
						<div class="mf-convert-feature-text">
							<strong><?php esc_html_e( 'Your data stays safe', 'metform' ); ?></strong>
							<p><?php esc_html_e( 'A complete backup of your current form is saved before anything changes.', 'metform' ); ?></p>
						</div>
					</div>
					<div class="mf-convert-feature-item">
						<div class="mf-convert-feature-icon">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8B5CF6" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
								<path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
							</svg>
						</div>
						<div class="mf-convert-feature-text">
							<strong><?php esc_html_e( 'Nothing breaks', 'metform' ); ?></strong>
							<p><?php esc_html_e( 'Your form ID stays the same. Live forms and embeds keep working.', 'metform' ); ?></p>
						</div>
					</div>
				</div>

				<!-- Footer for State 1 -->
				<div class="mf-convert-modal-footer">
					<?php wp_nonce_field( 'metform_convert_action', 'mf_convert_nonce' ); ?>
					<input type="hidden" id="mf-convert-form-id" value="" />
					<button type="button" class="mf-convert-btn-cancel">
						<?php esc_html_e( 'Cancel', 'metform' ); ?>
					</button>
					<button type="button" class="mf-convert-btn-submit">
						<span class="mf-convert-btn-icon">
							<svg width="19" height="17" viewBox="0 0 19 17" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M17.9343 6.39641L14.3008 6.68327V2.15737C14.3008 1.23307 13.5677 0.5 12.6434 0.5H2.1255C1.23307 0.5 0.5 1.23307 0.5 2.15737V9.67928L5.18526 12.5478L17.9343 6.39641Z" stroke="white" stroke-linejoin="round"/>
								<path d="M5.21713 15.7031L0.5 9.64735V14.8426C0.5 15.7669 1.23307 16.4999 2.15737 16.4999H12.6753C13.5996 16.4999 14.3327 15.7669 14.3327 14.8426V10.7948L17.9661 6.42822L5.21713 15.7031Z" stroke="white" stroke-miterlimit="7.57594" stroke-linejoin="round"/>
								<path d="M11.7471 2.91461H5.74707V3.91461H11.7471V2.91461Z" fill="white"/>
								<path d="M4.03762 4.11583C4.42488 4.11583 4.73882 3.80189 4.73882 3.41464C4.73882 3.02738 4.42488 2.71344 4.03762 2.71344C3.65036 2.71344 3.33643 3.02738 3.33643 3.41464C3.33643 3.80189 3.65036 4.11583 4.03762 4.11583Z" fill="white"/>
								<path d="M11.7471 5.16803H5.74707V6.16803H11.7471V5.16803Z" fill="white"/>
								<path d="M4.03762 6.36925C4.42488 6.36925 4.73882 6.05531 4.73882 5.66805C4.73882 5.28079 4.42488 4.96686 4.03762 4.96686C3.65036 4.96686 3.33643 5.28079 3.33643 5.66805C3.33643 6.05531 3.65036 6.36925 4.03762 6.36925Z" fill="white"/>
								<path d="M9.74707 7.42145H5.74707V8.42145H9.74707V7.42145Z" fill="white"/>
								<path d="M4.03762 8.62267C4.42488 8.62267 4.73882 8.30873 4.73882 7.92147C4.73882 7.53421 4.42488 7.22028 4.03762 7.22028C3.65036 7.22028 3.33643 7.53421 3.33643 7.92147C3.33643 8.30873 3.65036 8.62267 4.03762 8.62267Z" fill="white"/>
							</svg>
						</span>
						<span class="mf-convert-btn-text"><?php esc_html_e( 'Convert Now', 'metform' ); ?></span>
						<span class="mf-convert-spinner" style="display: none;"></span>
					</button>
				</div>
			</div>

			<!-- State 2: Conversion Completed State -->
			<div class="mf-convert-state-success" style="display: none;">
				<div class="mf-convert-success-icon-wrap">
					<svg width="43" height="43" viewBox="0 0 43 43" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M41.125 21.125C41.125 10.0793 32.1706 1.125 21.125 1.125C10.0793 1.125 1.125 10.0793 1.125 21.125C1.125 32.1706 10.0793 41.125 21.125 41.125C32.1706 41.125 41.125 32.1706 41.125 21.125Z" stroke="#10B981" stroke-width="2.25"/>
						<path d="M13.125 22.125L18.125 27.125L29.125 15.125" stroke="#10B981" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</div>
				<h4 class="mf-convert-success-title"><?php esc_html_e( 'You’re all set!', 'metform' ); ?></h4>
				<p class="mf-convert-success-desc">
					<?php esc_html_e( 'This form is now using the Standalone Form Builder. You can edit it anytime using the “Edit with MetForm Builder” button in the form list.', 'metform' ); ?>
				</p>
				<div class="mf-convert-success-actions">
					<a href="#" class="mf-convert-btn-open-builder">
						<span class="mf-convert-btn-icon">
							<svg width="19" height="17" viewBox="0 0 19 17" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M17.9343 6.39641L14.3008 6.68327V2.15737C14.3008 1.23307 13.5677 0.5 12.6434 0.5H2.1255C1.23307 0.5 0.5 1.23307 0.5 2.15737V9.67928L5.18526 12.5478L17.9343 6.39641Z" stroke="white" stroke-linejoin="round"/>
								<path d="M5.21713 15.7031L0.5 9.64735V14.8426C0.5 15.7669 1.23307 16.4999 2.15737 16.4999H12.6753C13.5996 16.4999 14.3327 15.7669 14.3327 14.8426V10.7948L17.9661 6.42822L5.21713 15.7031Z" stroke="white" stroke-miterlimit="7.57594" stroke-linejoin="round"/>
								<path d="M11.7471 2.91461H5.74707V3.91461H11.7471V2.91461Z" fill="white"/>
								<path d="M4.03762 4.11583C4.42488 4.11583 4.73882 3.80189 4.73882 3.41464C4.73882 3.02738 4.42488 2.71344 4.03762 2.71344C3.65036 2.71344 3.33643 3.02738 3.33643 3.41464C3.33643 3.80189 3.65036 4.11583 4.03762 4.11583Z" fill="white"/>
								<path d="M11.7471 5.16803H5.74707V6.16803H11.7471V5.16803Z" fill="white"/>
								<path d="M4.03762 6.36925C4.42488 6.36925 4.73882 6.05531 4.73882 5.66805C4.73882 5.28079 4.42488 4.96686 4.03762 4.96686C3.65036 4.96686 3.33643 5.28079 3.33643 5.66805C3.33643 6.05531 3.65036 6.36925 4.03762 6.36925Z" fill="white"/>
								<path d="M9.74707 7.42145H5.74707V8.42145H9.74707V7.42145Z" fill="white"/>
								<path d="M4.03762 8.62267C4.42488 8.62267 4.73882 8.30873 4.73882 7.92147C4.73882 7.53421 4.42488 7.22028 4.03762 7.22028C3.65036 7.22028 3.33643 7.53421 3.33643 7.92147C3.33643 8.30873 3.65036 8.62267 4.03762 8.62267Z" fill="white"/>
							</svg>
						</span>
						<span><?php esc_html_e( 'Open in MetForm Builder', 'metform' ); ?></span>
					</a>
					<button type="button" class="mf-convert-btn-later">
						<?php esc_html_e( 'Maybe later', 'metform' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>
