<?php
namespace MetForm\Core\FormBuilder\Shared;

defined( 'ABSPATH' ) || exit;

/**
 * Shared `register_controls()['style']` presets, ported 1:1 from
 * src/form-builder/blocks/shared/typographyControls.js so the PHP and JS
 * sides declare the exact same style schema for the blocks that share it
 * (Title, Subtitle, Paragraph). See PHP_RENDERING_MIGRATION_PLAN.md §5.5.
 */
final class Style_Controls {
	private const TYPOGRAPHY_DECLARATIONS = '{ font-family: {{FONT_FAMILY}}; font-size: {{FONT_SIZE}}; font-weight: {{FONT_WEIGHT}}; font-style: {{FONT_STYLE}}; text-transform: {{TEXT_TRANSFORM}}; text-decoration: {{TEXT_DECORATION}}; line-height: {{LINE_HEIGHT}}; letter-spacing: {{LETTER_SPACING}}; word-spacing: {{WORD_SPACING}}; }';

	/**
	 * Matches typographyControls.js's sharedTypographyStyle({ colorDefault }).
	 *
	 * Every rule targets `$element` inside the block root: the root itself
	 * (the element carrying the block class) belongs to the Advanced tab.
	 *
	 * @param string $color_default Default text colour.
	 * @param string $element       Selector of the styled element inside the
	 *                              block root, e.g. '.mfb-title__heading'.
	 *                              Empty targets the root itself.
	 * @return array<int, array<string, mixed>> Style controls.
	 */
	public static function typography( string $color_default = '#111827', string $element = '' ): array {
		$target = '{{WRAPPER}}' . ( '' !== $element ? ' ' . $element : '' );

		return array(
			array(
				'type'        => 'section',
				'label'       => __( 'Text', 'metform' ),
				'defaultOpen' => true,
			),
			array(
				'type'     => 'color',
				'key'      => 'styles.color',
				'label'    => __( 'Text Color', 'metform' ),
				'default'  => $color_default,
				'selector' => $target . ' { color: {{VALUE}}; }',
			),
			array(
				'type'       => 'alignment',
				'key'        => 'styles.textAlign',
				'label'      => __( 'Alignment', 'metform' ),
				'default'    => 'left',
				'inline'     => true,
				'options'    => array(
					array(
						'label' => __( 'Left', 'metform' ),
						'value' => 'left',
					),
					array(
						'label' => __( 'Center', 'metform' ),
						'value' => 'center',
					),
					array(
						'label' => __( 'Right', 'metform' ),
						'value' => 'right',
					),
					array(
						'label' => __( 'Justify', 'metform' ),
						'value' => 'justify',
					),
				),
				'selector'   => $target . ' { text-align: {{VALUE}}; }',
				'responsive' => true,
			),
			array(
				'type'           => 'typography',
				'key'            => 'styles.typography',
				'label'          => __( 'Typography', 'metform' ),
				'selector'       => $target . ' ' . self::TYPOGRAPHY_DECLARATIONS,
				'responsive'     => true,
				'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
			),

			array(
				'type'  => 'section',
				'label' => __( 'Background', 'metform' ),
			),
			array(
				'type'     => 'background',
				'key'      => 'styles.background',
				'label'    => __( 'Background', 'metform' ),
				'selector' => $target . ' { {{VALUE}} }',
			),

			array(
				'type'  => 'section',
				'label' => __( 'Spacing', 'metform' ),
			),
			array(
				'type'       => 'dimensions',
				'key'        => 'styles.padding',
				'label'      => __( 'Padding', 'metform' ),
				'default'    => array(
					'top'    => '0px',
					'right'  => '0px',
					'bottom' => '0px',
					'left'   => '0px',
				),
				'selector'   => $target . ' { padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
				'responsive' => true,
			),
			array(
				'type'       => 'dimensions',
				'key'        => 'styles.margin',
				'label'      => __( 'Margin', 'metform' ),
				'default'    => array(
					'top'    => '0px',
					'right'  => '0px',
					'bottom' => '8px',
					'left'   => '0px',
				),
				'selector'   => $target . ' { margin: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
				'responsive' => true,
			),

			array(
				'type'  => 'section',
				'label' => __( 'Border', 'metform' ),
			),
			array(
				'type'           => 'border',
				'key'            => 'styles.border',
				'label'          => __( 'Border', 'metform' ),
				'selector'       => '{{WRAPPER}} { border-style: {{BORDER_TYPE}}; border-width: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; border-color: {{COLOR}}; border-radius: {{RADIUS_TOP}} {{RADIUS_RIGHT}} {{RADIUS_BOTTOM}} {{RADIUS_LEFT}}; }',
				'responsive'     => true,
				'responsiveKeys' => array( 'width', 'radius' ),
			),
		);
	}
}
