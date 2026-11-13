<?php
namespace MetForm\Core\FormBuilder\Blocks\Date;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Input_Controls;

defined( 'ABSPATH' ) || exit;


final class Date extends Block_Base {
	public function get_name(): string {
		return 'date'; }
	public function get_title(): string {
		return __( 'Date', 'metform' ); }
	public function get_icon(): string {
		return 'Calendar'; }
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
			'label'           => __( 'Date', 'metform' ),
			'labelPosition'   => 'top',
			'name'            => '',
			'placeholder'     => __( 'Select a date', 'metform' ),
			'helpText'        => '',
			'required'        => false,
			'showLabel'       => true,
			'getValueFromUrl' => false,
			'todayAsMin'      => false,
			'minDate'         => '',
			'maxDate'         => '',
			'disableDates'    => '',
			'isRange'         => false,
			'dateFormat'      => 'DD-MM-YYYY',
			'includeTime'     => false,
			'time24h'         => false,
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
			'todayAsMin'      => array( 'type' => 'boolean' ),
			'minDate'         => array( 'type' => 'string' ),
			'maxDate'         => array( 'type' => 'string' ),
			'disableDates'    => array( 'type' => 'string' ),
			'isRange'         => array( 'type' => 'boolean' ),
			'dateFormat'      => array(
				'type' => 'string',
				'enum' => array( 'DD-MM-YYYY', 'MM-DD-YYYY', 'YYYY-MM-DD' ),
			),
			'includeTime'     => array( 'type' => 'boolean' ),
			'time24h'         => array( 'type' => 'boolean' ),
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
					'default'   => __( 'Date', 'metform' ),
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
					'type'        => 'section',
					'key'         => 'range',
					'label'       => __( 'Range', 'metform' ),
					'defaultOpen' => true,
				),
				array(
					'type'    => 'toggle',
					'key'     => 'todayAsMin',
					'label'   => __( 'Today as Minimum Date', 'metform' ),
					'default' => false,
					'help'    => __( 'Disallow picking any date before today.', 'metform' ),
				),
				array(
					'type'      => 'date',
					'key'       => 'minDate',
					'label'     => __( 'Minimum Date', 'metform' ),
					'condition' => array( 'todayAsMin' => false ),
				),
				array(
					'type'  => 'date',
					'key'   => 'maxDate',
					'label' => __( 'Maximum Date', 'metform' ),
				),
				array(
					'type'  => 'text',
					'key'   => 'disableDates',
					'label' => __( 'Disable Dates', 'metform' ),
					'help'  => __( 'Comma-separated ISO dates, e.g. 2026-07-04,2026-12-25', 'metform' ),
				),
				array(
					'type'    => 'toggle',
					'key'     => 'isRange',
					'label'   => __( 'Enable Date Range', 'metform' ),
					'default' => false,
					'help'    => __( 'Lets visitors pick a start and end date.', 'metform' ),
				),

				array(
					'type'  => 'section',
					'key'   => 'format',
					'label' => __( 'Format & Time', 'metform' ),
				),
				array(
					'type'    => 'select',
					'key'     => 'dateFormat',
					'label'   => __( 'Date Format', 'metform' ),
					'default' => 'DD-MM-YYYY',
					'options' => array(
						array(
							'label' => 'DD-MM-YYYY',
							'value' => 'DD-MM-YYYY',
						),
						array(
							'label' => 'MM-DD-YYYY',
							'value' => 'MM-DD-YYYY',
						),
						array(
							'label' => 'YYYY-MM-DD',
							'value' => 'YYYY-MM-DD',
						),
					),
				),
				array(
					'type'    => 'toggle',
					'key'     => 'includeTime',
					'label'   => __( 'Include Time', 'metform' ),
					'default' => false,
				),
				array(
					'type'      => 'toggle',
					'key'       => 'time24h',
					'label'     => __( '24 Hour Time', 'metform' ),
					'default'   => false,
					'condition' => array( 'includeTime' => true ),
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
						'label' => __( 'Calendar', 'metform' ),
					),
					array(
						'type'     => 'color',
						'key'      => 'styles.calendarAccentColor',
						'label'    => __( 'Accent Color', 'metform' ),
						'default'  => '#2563eb',
						'selector' => '{{WRAPPER}} { --mfb-date-accent: {{VALUE}}; }',
					),
					array(
						'type'     => 'background',
						'key'      => 'styles.calendarBg',
						'label'    => __( 'Calendar Background', 'metform' ),
						'default'  => array(
							'type'  => 'classic',
							'color' => '#ffffff',
						),
						'selector' => '{{WRAPPER}} .mfb-date__popup { {{VALUE}} }',
					),
					array(
						'type'     => 'color',
						'key'      => 'styles.calendarTextColor',
						'label'    => __( 'Day Text Color', 'metform' ),
						'default'  => '#374151',
						'selector' => '{{WRAPPER}} { --mfb-date-text: {{VALUE}}; }',
					),
				)
			),
		);
	}

	/** DD-MM-YYYY (control's display token) → the picker's own `d-m-Y` token syntax (see index.js's formatDate()). */
	private function to_picker_format( string $display_format, bool $include_time, bool $time_24h ): string {
		$map    = array(
			'DD-MM-YYYY' => 'd-m-Y',
			'MM-DD-YYYY' => 'm-d-Y',
			'YYYY-MM-DD' => 'Y-m-d',
		);
		$format = $map[ $display_format ] ?? 'd-m-Y';
		if ( $include_time ) {
			$format .= $time_24h ? ' H:i' : ' h:i K';
		}
		return $format;
	}

	/** `disableDates`'s comma-separated ISO string → a JSON array the picker's `disable` list accepts directly. */
	private function disabled_dates_json( string $raw ): string {
		$dates = array_values( array_filter( array_map( 'trim', explode( ',', $raw ) ), static fn ( string $d ): bool => '' !== $d ) );
		return $dates ? (string) wp_json_encode( $dates ) : '';
	}

	protected function render_markup( array $settings, Render_Context $context ): string {
		$id            = $context->get_element_id();
		$include_time  = ! empty( $settings['includeTime'] );
		$time_24h      = ! empty( $settings['time24h'] );
		$today_as_min  = ! empty( $settings['todayAsMin'] );
		$min           = $today_as_min ? '' : (string) ( $settings['minDate'] ?? '' );
		$max           = (string) ( $settings['maxDate'] ?? '' );
		$format        = $this->to_picker_format( (string) ( $settings['dateFormat'] ?? 'DD-MM-YYYY' ), $include_time, $time_24h );
		$disabled_json = $this->disabled_dates_json( (string) ( $settings['disableDates'] ?? '' ) );

		ob_start();
		?>
		<input
			id="<?php echo esc_attr( $id ); ?>"
			type="text"
			name="<?php echo esc_attr( $settings['name'] ); ?>"
			class="mfb-field__input mfb-date__input"
			placeholder="<?php echo esc_attr( $settings['placeholder'] ?? '' ); ?>"
			autocomplete="off"
			data-mf-date="1"
			data-mf-format="<?php echo esc_attr( $format ); ?>"
			data-mf-mode="<?php echo ! empty( $settings['isRange'] ) ? 'range' : 'single'; ?>"
			data-mf-enable-time="<?php echo $include_time ? 'true' : 'false'; ?>"
			data-mf-time24hr="<?php echo $time_24h ? 'true' : 'false'; ?>"
			data-mf-today-min="<?php echo $today_as_min ? 'true' : 'false'; ?>"
			<?php echo $min ? 'data-mf-min="' . esc_attr( $min ) . '"' : ''; ?>
			<?php echo $max ? 'data-mf-max="' . esc_attr( $max ) . '"' : ''; ?>
			<?php echo $disabled_json ? 'data-mf-disable="' . esc_attr( $disabled_json ) . '"' : ''; ?>
			<?php echo ! empty( $settings['required'] ) ? 'required' : ''; ?>
		>
		<?php
		return (string) ob_get_clean();
	}
}
