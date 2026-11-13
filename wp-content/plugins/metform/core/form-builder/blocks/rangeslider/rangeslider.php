<?php
namespace MetForm\Core\FormBuilder\Blocks\Rangeslider;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;

defined( 'ABSPATH' ) || exit;

final class Rangeslider extends Block_Base {
	public function get_name(): string {
		return 'rangeslider'; }
	public function get_title(): string {
		return __( 'Range Slider', 'metform' ); }
	public function get_icon(): string {
		return 'SlidersHorizontal'; }
	public function get_category(): string {
		return 'Basic'; }

	public function get_group(): string {
		return 'rating-range'; }

	public function get_capabilities(): array {
		return array(
			'acceptsChildren' => false,
			'isField'         => true,
		);
	}

	public function get_default_settings(): array {
		return array(
			'label'         => __( 'Range Slider', 'metform' ),
			'labelPosition' => 'top',
			'name'          => '',
			'helpText'      => '',
			'required'      => false,
			'showLabel'     => true,
			'min'           => 0,
			'max'           => 100,
			'step'          => 1,
			'defaultValue'  => '50',
			'isRange'       => false,
			'showValue'     => true,
			'styles'        => array(),
		);
	}

	public function get_property_schema(): array {
		return array(
			'label'         => array( 'type' => 'string' ),
			'labelPosition' => array(
				'type'       => 'string',
				'enum'       => array( 'top', 'left' ),
				'responsive' => true,
			),
			'name'          => array( 'type' => 'field_name' ),
			'helpText'      => array( 'type' => 'string' ),
			'required'      => array( 'type' => 'boolean' ),
			'showLabel'     => array( 'type' => 'boolean' ),
			'min'           => array( 'type' => 'number' ),
			'max'           => array( 'type' => 'number' ),
			'step'          => array( 'type' => 'number' ),
			'defaultValue'  => array( 'type' => 'string' ),
			'isRange'       => array( 'type' => 'boolean' ),
			'showValue'     => array( 'type' => 'boolean' ),
		);
	}

	private function typography_selector( string $el ): string {
		return "{{WRAPPER}} {$el} { font-family: {{FONT_FAMILY}}; font-size: {{FONT_SIZE}}; font-weight: {{FONT_WEIGHT}}; font-style: {{FONT_STYLE}}; text-transform: {{TEXT_TRANSFORM}}; text-decoration: {{TEXT_DECORATION}}; line-height: {{LINE_HEIGHT}}; letter-spacing: {{LETTER_SPACING}}; word-spacing: {{WORD_SPACING}}; }";
	}

	public function register_controls(): array {
		$thumb_selector = static fn( $decl ) => "{{WRAPPER}} .mfb-range__input::-webkit-slider-thumb { {$decl} } {{WRAPPER}} .mfb-range__input::-moz-range-thumb { {$decl} }";
		$track_selector = static fn( $decl ) => "{{WRAPPER}} .mfb-range__input::-webkit-slider-runnable-track { {$decl} } {{WRAPPER}} .mfb-range__input::-moz-range-track { {$decl} }";

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
					'default'   => __( 'Range Slider', 'metform' ),
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
					'key'   => 'helpText',
					'label' => __( 'Help Text', 'metform' ),
					'help'  => __( 'Shown below the field to guide users', 'metform' ),
				),
				array(
					'type'    => 'number',
					'key'     => 'min',
					'label'   => __( 'Min', 'metform' ),
					'default' => 0,
				),
				array(
					'type'    => 'number',
					'key'     => 'max',
					'label'   => __( 'Max', 'metform' ),
					'default' => 100,
				),
				array(
					'type'    => 'number',
					'key'     => 'step',
					'label'   => __( 'Step', 'metform' ),
					'default' => 1,
				),
				array(
					'type'    => 'text',
					'key'     => 'defaultValue',
					'label'   => __( 'Default Value', 'metform' ),
					'default' => '50',
					'help'    => __( 'A single number, or "min,max" when Dual Range is enabled.', 'metform' ),
				),
				array(
					'type'    => 'toggle',
					'key'     => 'isRange',
					'label'   => __( 'Enable Dual Range', 'metform' ),
					'default' => false,
				),
				array(
					'type'    => 'toggle',
					'key'     => 'showValue',
					'label'   => __( 'Show Current Value', 'metform' ),
					'default' => true,
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
			),
			'style'   => array(
				array(
					'type'        => 'section',
					'label'       => __( 'Label', 'metform' ),
					'defaultOpen' => true,
					'condition'   => array( 'showLabel!' => false ),
				),
				array(
					'type'      => 'color',
					'key'       => 'styles.labelColor',
					'label'     => __( 'Label Color', 'metform' ),
					'default'   => '#374151',
					'selector'  => '{{WRAPPER}} .mfb-label { color: {{VALUE}}; }',
					'condition' => array( 'showLabel!' => false ),
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.labelTypography',
					'label'          => __( 'Label Typography', 'metform' ),
					'selector'       => $this->typography_selector( '.mfb-label' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
					'condition'      => array( 'showLabel!' => false ),
				),
				array(
					'type'  => 'section',
					'label' => __( 'Help Text', 'metform' ),
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.helpTextColor',
					'label'    => __( 'Help Text Color', 'metform' ),
					'default'  => '#6b7280',
					'selector' => '{{WRAPPER}} .mfb-field__help { color: {{VALUE}}; }',
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.errorColor',
					'label'    => __( 'Error Color', 'metform' ),
					'default'  => '#ef4444',
					'selector' => '{{WRAPPER}} .mfb-field__error { color: {{VALUE}}; }',
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.helpTextTypography',
					'label'          => __( 'Help Text Typography', 'metform' ),
					'selector'       => $this->typography_selector( '.mfb-field__help' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.errorTypography',
					'label'          => __( 'Error Typography', 'metform' ),
					'selector'       => $this->typography_selector( '.mfb-field__error' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),

				array(
					'type'  => 'section',
					'label' => __( 'Track', 'metform' ),
				),
				array(
					'type'          => 'color',
					'key'           => 'styles.trackColor',
					'rendersMarkup' => true,
					'label'         => __( 'Track Color (inactive)', 'metform' ),
					'default'       => '#d1d5db',
					'selector'      => '{{WRAPPER}} .mfb-range__input::-moz-range-track { background-color: {{VALUE}}; }',
				),
				array(
					'type'          => 'color',
					'key'           => 'styles.fillColor',
					'rendersMarkup' => true,
					'label'         => __( 'Fill Color (active)', 'metform' ),
					'default'       => '#2563eb',
					'selector'      => '{{WRAPPER}} .mfb-range__input::-moz-range-progress { background-color: {{VALUE}}; }',
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.trackHeight',
					'label'      => __( 'Track Height', 'metform' ),
					'default'    => '6px',
					'units'      => array( 'px' ),
					'min'        => 2,
					'max'        => 24,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} { --mfb-track-h: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.trackRadius',
					'label'      => __( 'Track Border Radius', 'metform' ),
					'default'    => '12px',
					'units'      => array( 'px' ),
					'min'        => 0,
					'max'        => 24,
					'step'       => 1,
					'selector'   => $track_selector( 'border-radius: {{VALUE}};' ),
					'responsive' => true,
				),

				array(
					'type'  => 'section',
					'label' => __( 'Thumb', 'metform' ),
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.thumbColor',
					'label'    => __( 'Thumb Color', 'metform' ),
					'default'  => '#ffffff',
					'selector' => $thumb_selector( 'background-color: {{VALUE}};' ),
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.thumbSize',
					'label'      => __( 'Thumb Size', 'metform' ),
					'default'    => '18px',
					'units'      => array( 'px' ),
					'min'        => 10,
					'max'        => 40,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} { --mfb-thumb-s: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'           => 'border',
					'key'            => 'styles.thumbBorder',
					'label'          => __( 'Thumb Border', 'metform' ),
					'default'        => array(
						'borderType' => 'solid',
						'width'      => array(
							'top'    => '2px',
							'right'  => '2px',
							'bottom' => '2px',
							'left'   => '2px',
						),
						'color'      => '#2563eb',
					),
					'selector'       => $thumb_selector( '{{VALUE}}' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'width', 'radius' ),
				),

				array(
					'type'  => 'section',
					'label' => __( 'Value', 'metform' ),
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.valueTextColor',
					'label'    => __( 'Value Text Color', 'metform' ),
					'default'  => '#111827',
					'selector' => '{{WRAPPER}} .mfb-range__value { color: {{VALUE}}; }',
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.valueTypography',
					'label'          => __( 'Value Typography', 'metform' ),
					'selector'       => $this->typography_selector( '.mfb-range__value' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),
				array(
					'type'     => 'background',
					'key'      => 'styles.valueBackground',
					'label'    => __( 'Value Background', 'metform' ),
					'default'  => array(
						'type'  => 'classic',
						'color' => '#f3f4f6',
					),
					'selector' => '{{WRAPPER}} .mfb-range__value { {{VALUE}} }',
				),
			),
		);
	}

	private function clamp( float $n, float $min, float $max ): float {
		return min( max( $n, $min ), $max );
	}

	/** Parses a trimmed numeric string (or number) to float, or null when it isn't numeric. */
	private function parse_number( $value ): ?float {
		$value = is_string( $value ) ? trim( $value ) : $value;
		return is_numeric( $value ) ? (float) $value : null;
	}

	/** Decimal places in a number's plain representation (capped at 10), used to strip float noise after snapping. */
	private function decimals_of( float $n ): int {
		$plain = rtrim( rtrim( sprintf( '%.10F', abs( $n ) ), '0' ), '.' );
		$dot   = strpos( $plain, '.' );
		return false === $dot ? 0 : strlen( $plain ) - $dot - 1;
	}

	/**
	 * Clamps to min/max and rounds onto the step grid the same way a browser
	 * sanitizes a range input's value (step base = min, ties round up, and a
	 * result past max falls back to the highest step value <= max), so the
	 * value PHP prints matches where the handle sits and what gets submitted.
	 */
	private function snap( float $n, float $min, float $max, float $step ): float {
		$n         = $this->clamp( $n, $min, $max );
		$precision = max( $this->decimals_of( $step ), $this->decimals_of( $min ) );
		$snapped   = $min + floor( ( $n - $min ) / $step + 0.5 ) * $step;
		if ( $snapped > $max + 1e-9 ) {
			$snapped = $min + floor( ( $max - $min ) / $step + 1e-9 ) * $step;
		}
		return round( $snapped, $precision );
	}


	private function fill_gradient( float $min, float $max, float $value, string $fill_color, string $track_color ): string {
		$pct = $this->percent_of( $min, $max, $value );
		return sprintf(
			'background: linear-gradient(to right, %1$s 0%%, %1$s %3$s%%, %2$s %3$s%%, %2$s 100%%);',
			$fill_color,
			$track_color,
			$pct
		);
	}

	/** Matches index.jsx's percentOf(): where `value` sits between min/max, as a 0-100 percent. */
	private function percent_of( float $min, float $max, float $value ): float {
		return $this->clamp( 100 * ( $value - $min ) / max( 0.0001, $max - $min ), 0, 100 );
	}


	/** Matches index.js's setBubblePosition(): the value bubble's position, as a 0-100 percent the stylesheet turns into an edge-clamped offset. */
	private function bubble_style( float $min, float $max, float $value ): string {
		return '--mfb-range-pct: ' . $this->percent_of( $min, $max, $value ) . ';';
	}


	private function dual_fill_gradient( float $min, float $max, float $start, float $end, string $fill_color, string $track_color ): string {
		$start_pct = $this->percent_of( $min, $max, $start );
		$end_pct   = $this->percent_of( $min, $max, $end );
		return sprintf(
			'background: linear-gradient(to right, %2$s 0%%, %2$s %3$s%%, %1$s %3$s%%, %1$s %4$s%%, %2$s %4$s%%, %2$s 100%%);',
			$fill_color,
			$track_color,
			$start_pct,
			$end_pct
		);
	}

	public function render_markup( array $settings, Render_Context $context ): string {
		$min     = $this->parse_number( $settings['min'] ?? null ) ?? 0.0;
		$max     = $this->parse_number( $settings['max'] ?? null ) ?? 100.0;
		$step    = $this->parse_number( $settings['step'] ?? null ) ?? 1.0;
		$default = (string) ( $settings['defaultValue'] ?? '' );
		if ( $min > $max ) {
			list( $min, $max ) = array( $max, $min );
		}
		if ( $step <= 0 ) {
			$step = 1.0;
		}
		$show_value = (bool) ( $settings['showValue'] ?? true );
		$name       = (string) ( $settings['name'] ?? '' );
		$id         = $context->get_element_id();

		$styles      = is_array( $settings['styles'] ?? null ) ? $settings['styles'] : array();
		$fill_color  = (string) ( $styles['fillColor'] ?? '#2563eb' );
		$track_color = (string) ( $styles['trackColor'] ?? '#d1d5db' );

		if ( ! empty( $settings['isRange'] ) ) {
			// Two numbers ("min,max"), snapped and reordered if reversed; a
			// single number starts there and runs to Max; anything else starts
			// the handles at Min and Max.
			$parts = explode( ',', $default );
			$first = $this->parse_number( $parts[0] );
			$last  = $this->parse_number( $parts[1] ?? null );
			if ( 2 === count( $parts ) && null !== $first && null !== $last ) {
				$start = $this->snap( min( $first, $last ), $min, $max, $step );
				$end   = $this->snap( max( $first, $last ), $min, $max, $step );
			} elseif ( 1 === count( $parts ) && null !== $first ) {
				$start = $this->snap( $first, $min, $max, $step );
				$end   = $this->snap( $max, $min, $max, $step );
			} else {
				$start = $min;
				$end   = $this->snap( $max, $min, $max, $step );
			}

			ob_start();
			?>
<div class="mfb-range__stage <?php echo $show_value ? 'mfb-range__stage--has-tooltip' : ''; ?>">
			<?php if ( $show_value ) : ?>
	<span class="mfb-range__value mfb-range__tooltip" data-mfb-thumb="min" style="<?php echo esc_attr( $this->bubble_style( $min, $max, $start ) ); ?>"><?php echo esc_html( (string) $start ); ?></span>
	<span class="mfb-range__value mfb-range__tooltip" data-mfb-thumb="max" style="<?php echo esc_attr( $this->bubble_style( $min, $max, $end ) ); ?>"><?php echo esc_html( (string) $end ); ?></span>
	<?php endif; ?>
	<div class="mfb-range__dual-wrap">
		<div class="mfb-range__visual-track" style="<?php echo esc_attr( $this->dual_fill_gradient( $min, $max, $start, $end, $fill_color, $track_color ) ); ?>"></div>
		<input type="range" id="<?php echo esc_attr( $id ); ?>-min" name="<?php echo esc_attr( $name ); ?>_min" class="mfb-range__input" data-mfb-thumb="min" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" step="<?php echo esc_attr( $step ); ?>" value="<?php echo esc_attr( $start ); ?>" <?php echo ! empty( $settings['required'] ) ? 'required' : ''; ?>>
		<input type="range" id="<?php echo esc_attr( $id ); ?>-max" name="<?php echo esc_attr( $name ); ?>_max" class="mfb-range__input" data-mfb-thumb="max" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" step="<?php echo esc_attr( $step ); ?>" value="<?php echo esc_attr( $end ); ?>" <?php echo ! empty( $settings['required'] ) ? 'required' : ''; ?>>
	</div>
</div>
			<?php
			return (string) ob_get_clean();
		}

		// The first number (so a leftover "min,max" from Dual Range starts at
		// its min), snapped; empty or non-numeric starts at Min.
		$first = $this->parse_number( explode( ',', $default )[0] );
		$value = null !== $first ? $this->snap( $first, $min, $max, $step ) : $min;

		ob_start();
		?>
<div class="mfb-range__stage <?php echo $show_value ? 'mfb-range__stage--has-tooltip' : ''; ?>">
		<?php if ( $show_value ) : ?>
	<span class="mfb-range__value mfb-range__tooltip" style="<?php echo esc_attr( $this->bubble_style( $min, $max, $value ) ); ?>"><?php echo esc_html( (string) $value ); ?></span>
<?php endif; ?>
	<input type="range" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" class="mfb-range__input" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" step="<?php echo esc_attr( $step ); ?>" value="<?php echo esc_attr( $value ); ?>" style="<?php echo esc_attr( $this->fill_gradient( $min, $max, $value, $fill_color, $track_color ) ); ?>" <?php echo ! empty( $settings['required'] ) ? 'required' : ''; ?>>
</div>
		<?php
		return (string) ob_get_clean();
	}
}
