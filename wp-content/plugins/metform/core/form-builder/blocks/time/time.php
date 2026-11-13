<?php
namespace MetForm\Core\FormBuilder\Blocks\Time;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Input_Controls;

defined( 'ABSPATH' ) || exit;

final class Time extends Block_Base {
	public function get_name(): string {
		return 'time'; }
	public function get_title(): string {
		return __( 'Time', 'metform' ); }
	public function get_icon(): string {
		return 'Clock'; }
	public function get_category(): string {
		return 'Basic'; }

	public function get_group(): string {
		return 'datetime'; }

	public function get_capabilities(): array {
		return array(
			'acceptsChildren' => false,
			'isField'         => true,
		);
	}

	public function get_default_settings(): array {
		return array(
			'label'           => __( 'Time', 'metform' ),
			'labelPosition'   => 'top',
			'name'            => '',
			'placeholder'     => __( 'Select a time', 'metform' ),
			'helpText'        => '',
			'required'        => false,
			'showLabel'       => true,
			'getValueFromUrl' => false,
			'use24h'          => false,
			'minuteStep'      => 5,
			'styles'          => array(),
		);
	}

	public function get_property_schema(): array {
		return array(
			'label'           => array( 'type' => 'string' ),
			'labelPosition'   => array(
				'type'       => 'string',
				'enum'       => array( 'top', 'left' ),
				'responsive' => true,
			),
			'name'            => array( 'type' => 'field_name' ),
			'placeholder'     => array( 'type' => 'string' ),
			'helpText'        => array( 'type' => 'string' ),
			'required'        => array( 'type' => 'boolean' ),
			'showLabel'       => array( 'type' => 'boolean' ),
			'getValueFromUrl' => array( 'type' => 'boolean' ),
			'use24h'          => array( 'type' => 'boolean' ),
			'minuteStep'      => array(
				'type' => 'number',
				'enum' => array( 1, 5, 10, 15, 30 ),
			),
		);
	}

	public function register_controls(): array {
		return array(
			'content' => array(
				array(
					'type'        => 'section',
					'key'         => 'content',
					'label'       => __( 'Content', 'metform' ),
					'defaultOpen' => true,
				),
				array(
					'type'    => 'toggle',
					'key'     => 'showLabel',
					'label'   => __( 'Show Label', 'metform' ),
					'default' => true,
				),
				array(
					'type'      => 'text',
					'key'       => 'label',
					'label'     => __( 'Label', 'metform' ),
					'default'   => __( 'Time', 'metform' ),
					'condition' => array( 'showLabel!' => false ),
				),
				array(
					'type'       => 'select',
					'key'        => 'labelPosition',
					'label'      => __( 'Label Position', 'metform' ),
					'default'    => 'top',
					'options'    => array(
						array(
							'label' => __( 'Top', 'metform' ),
							'value' => 'top',
						),
						array(
							'label' => __( 'Left', 'metform' ),
							'value' => 'left',
						),
					),
					'condition'  => array( 'showLabel!' => false ),
					'responsive' => true,
				),
				array(
					'type'  => 'text',
					'key'   => 'name',
					'label' => __( 'Name', 'metform' ),
					'help'  => __( 'Name is must required. Enter name without space or any special character. use only underscore/ hyphen (_/-) for multiple word. Name must be different.', 'metform' ),
				),
				array(
					'type'  => 'text',
					'key'   => 'placeholder',
					'label' => __( 'Placeholder', 'metform' ),
				),
				array(
					'type'  => 'text',
					'key'   => 'helpText',
					'label' => __( 'Help Text', 'metform' ),
					'help'  => __( 'Shown below the field to guide users', 'metform' ),
				),
				array(
					'type'    => 'toggle',
					'key'     => 'use24h',
					'label'   => __( 'Use 24H Format', 'metform' ),
					'default' => false,
				),
				array(
					'type'    => 'select',
					'key'     => 'minuteStep',
					'label'   => __( 'Minute Step', 'metform' ),
					'default' => 5,
					'options' => array(
						array(
							'label' => __( '1 minute', 'metform' ),
							'value' => 1,
						),
						array(
							'label' => __( '5 minutes', 'metform' ),
							'value' => 5,
						),
						array(
							'label' => __( '10 minutes', 'metform' ),
							'value' => 10,
						),
						array(
							'label' => __( '15 minutes', 'metform' ),
							'value' => 15,
						),
						array(
							'label' => __( '30 minutes', 'metform' ),
							'value' => 30,
						),
					),
					'help'    => __( 'How much the up/down arrows move the minute value.', 'metform' ),
				),

				array(
					'type'        => 'section',
					'key'         => 'settings',
					'label'       => __( 'Settings', 'metform' ),
					'defaultOpen' => false,
				),
				array(
					'type'    => 'toggle',
					'key'     => 'required',
					'label'   => __( 'Required', 'metform' ),
					'default' => false,
				),
				array(
					'type'    => 'toggle',
					'key'     => 'getValueFromUrl',
					'label'   => __( 'Get Value From URL?', 'metform' ),
					'default' => false,
					'help'    => __( 'Prefills this field from a URL query parameter matching the Field Name.', 'metform' ),
				),
			),
			'style'   => Input_Controls::style(
				'input',
				array(
					array(
						'type'  => 'section',
						'label' => __( 'Time Picker', 'metform' ),
					),
					array(
						'type'     => 'color',
						'key'      => 'styles.timeAccentColor',
						'label'    => __( 'Accent Color', 'metform' ),
						'default'  => '#2563eb',
						'selector' => '{{WRAPPER}} { --mfb-time-accent: {{VALUE}}; }',
					),
				)
			),
		);
	}

	/**
	 * Labels the frontend script builds at runtime, passed through
	 * `data-mf-i18n` (see src/frontend/form-builder/blocks/time/index.js's
	 * LABELS, which holds the same keys as English fallbacks). Every other
	 * picker label is rendered straight into the markup below.
	 *
	 * @param bool $use24h Whether the field uses 24h format.
	 * @return array<string,string>
	 */
	private function picker_labels( bool $use24h ): array {
		return array(
			'amPm'    => __( 'AM or PM', 'metform' ),
			'notSet'  => __( 'Not set', 'metform' ),
			'invalid' => $use24h
				? __( 'Enter a time like 21:30', 'metform' )
				: __( 'Enter a time like 9:30 PM', 'metform' ),
		);
	}

	/**
	 * Small ▲/▼ arrow on an hour/minute segment (mouse only: shown on hover,
	 * hidden on touch, skipped by Tab — the segment itself takes ↑/↓).
	 *
	 * @param string $target 'hour' or 'minute'.
	 * @param int    $dir    1 or -1.
	 * @param string $label  Accessible name.
	 * @return string
	 */
	private function step_button_markup( string $target, int $dir, string $label ): string {
		$path = $dir > 0 ? 'M1 4l3-3 3 3' : 'M1 1l3 3 3-3';

		ob_start();
		?>
		<button type="button" class="mfb-time__step mfb-time__step--<?php echo $dir > 0 ? 'up' : 'down'; ?>" data-target="<?php echo esc_attr( $target ); ?>" data-dir="<?php echo esc_attr( (string) $dir ); ?>" tabindex="-1" aria-label="<?php echo esc_attr( $label ); ?>"><svg width="8" height="5" viewBox="0 0 8 5" aria-hidden="true" focusable="false"><path d="<?php echo esc_attr( $path ); ?>" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One hour/minute segment of the one-row picker. Its text and
	 * aria-valuenow/valuetext are filled in by the frontend script.
	 *
	 * @param string $target   'hour' or 'minute'.
	 * @param string $label    Segment's accessible name.
	 * @param string $increase "Increase …" label.
	 * @param string $decrease "Decrease …" label.
	 * @param int    $min      aria-valuemin.
	 * @param int    $max      aria-valuemax.
	 * @return string
	 */
	private function segment_markup( string $target, string $label, string $increase, string $decrease, int $min, int $max ): string {
		ob_start();
		?>
		<div class="mfb-time__seg">
			<div class="mfb-time__value" data-target="<?php echo esc_attr( $target ); ?>" tabindex="0" role="spinbutton" aria-label="<?php echo esc_attr( $label ); ?>" aria-valuemin="<?php echo esc_attr( (string) $min ); ?>" aria-valuemax="<?php echo esc_attr( (string) $max ); ?>"></div>
			<?php
			echo $this->step_button_markup( $target, 1, $increase ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->step_button_markup( $target, -1, $decrease ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public function render_markup( array $settings, Render_Context $context ): string {
		$id       = $context->get_element_id();
		$popup_id = $id . '-picker';
		$use24h   = ! empty( $settings['use24h'] );
		$step     = (int) ( $settings['minuteStep'] ?? 5 );
		if ( $step < 1 ) {
			$step = 5;
		}
		// An empty Placeholder setting shows the expected format instead.
		// Only the rendered attribute changes; the saved setting stays empty.
		$placeholder = (string) ( $settings['placeholder'] ?? '' );
		if ( '' === trim( $placeholder ) ) {
			/* translators: time format hint shown as the field placeholder (24h / 12h clock). */
			$placeholder = $use24h ? __( 'HH:mm', 'metform' ) : __( 'hh:mm AM', 'metform' );
		}

		// The ✕ (clear) button sits inside the field, so the input gets a
		// positioned wrapper of its own — the surrounding .mfb-field__control
		// also holds the help text and error message. The popup is
		// position: fixed, so where it sits in the DOM doesn't affect layout.
		ob_start();
		?>
		<span class="mfb-time__wrap">
			<input
				id="<?php echo esc_attr( $id ); ?>"
				type="text"
				name="<?php echo esc_attr( $settings['name'] ); ?>"
				class="mfb-field__input mfb-time__input"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				autocomplete="off"
				aria-haspopup="dialog"
				aria-controls="<?php echo esc_attr( $popup_id ); ?>"
				aria-expanded="false"
				data-mf-time="1"
				data-mf-use24h="<?php echo $use24h ? 'true' : 'false'; ?>"
				data-mf-step="<?php echo esc_attr( (string) $step ); ?>"
				data-mf-i18n="<?php echo esc_attr( (string) wp_json_encode( $this->picker_labels( $use24h ) ) ); ?>"
				<?php echo ! empty( $settings['required'] ) ? 'required' : ''; ?>
			>
			<button type="button" class="mfb-time__clear" aria-label="<?php esc_attr_e( 'Clear', 'metform' ); ?>"><svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M18 6L6 18M6 6l12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
		</span>
		<div id="<?php echo esc_attr( $popup_id ); ?>" class="mfb-time__popup" role="dialog" aria-label="<?php esc_attr_e( 'Set time', 'metform' ); ?>" hidden>
			<div class="mfb-time__row">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside segment_markup().
				echo $this->segment_markup( 'hour', __( 'Hour', 'metform' ), __( 'Increase hour', 'metform' ), __( 'Decrease hour', 'metform' ), $use24h ? 0 : 1, $use24h ? 23 : 12 );
				?>
				<span class="mfb-time__sep" aria-hidden="true">:</span>
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside segment_markup().
				echo $this->segment_markup( 'minute', __( 'Minute', 'metform' ), __( 'Increase minute', 'metform' ), __( 'Decrease minute', 'metform' ), 0, 59 );
				?>
				<?php if ( ! $use24h ) : ?>
				<button type="button" class="mfb-time__ampm"></button>
				<?php endif; ?>
			</div>
			<div class="mfb-time__list" role="group" hidden></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
