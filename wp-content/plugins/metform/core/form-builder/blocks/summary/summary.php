<?php
namespace MetForm\Core\FormBuilder\Blocks\Summary;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;

defined( 'ABSPATH' ) || exit;


final class Summary extends Block_Base {
	private const INPUT_TYPES = array(
		'input',
		'email',
		'password',
		'select',
		'textarea',
		'message',
		'checkbox',
		'radio',
		'date',
		'time',
		'telephone',
		'number',
		'switch',
		'rangeslider',
		'urlinput',
		'rating',
		'fileupload',
		'gdprconsent',
		'optin',
		'mobile',
		'colorpicker',
		'imageselect',
		'likedislike',
		'toggleselect',
		'firstnamelisting',
		'lastnamelisting',
		'calculation',
		'signature',
	);

	public function get_name(): string {
		return 'summary'; }
	public function get_title(): string {
		return __( 'Summary', 'metform' ); }
	public function get_icon(): string {
		return 'ClipboardList'; }
	public function get_category(): string {
		return 'Basic'; }

	public function get_group(): string {
		return 'content'; }

	public function get_default_settings(): array {
		return array(
			'showTitle'       => true,
			'title'           => __( 'Summary', 'metform' ),
			'showEmptyFields' => false,
			'emptyText'       => '—',
			'maskPasswords'   => true,
			'excludeFields'   => '',
			'styles'          => array(),
		);
	}

	public function get_property_schema(): array {
		return array(
			'showTitle'       => array( 'type' => 'boolean' ),
			'title'           => array( 'type' => 'string' ),
			'showEmptyFields' => array( 'type' => 'boolean' ),
			'emptyText'       => array( 'type' => 'string' ),
			'maskPasswords'   => array( 'type' => 'boolean' ),
			'excludeFields'   => array( 'type' => 'string' ),
		);
	}

	public function register_controls(): array {
		$typography_selector = static fn( string $el ) =>
			"{{WRAPPER}} {$el} { font-family: {{FONT_FAMILY}}; font-size: {{FONT_SIZE}}; font-weight: {{FONT_WEIGHT}}; font-style: {{FONT_STYLE}}; text-transform: {{TEXT_TRANSFORM}}; text-decoration: {{TEXT_DECORATION}}; line-height: {{LINE_HEIGHT}}; letter-spacing: {{LETTER_SPACING}}; word-spacing: {{WORD_SPACING}}; }";

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
					'key'     => 'showTitle',
					'label'   => __( 'Show Title', 'metform' ),
					'default' => true,
				),
				array(
					'type'      => 'text',
					'key'       => 'title',
					'label'     => __( 'Title', 'metform' ),
					'default'   => __( 'Summary', 'metform' ),
					'condition' => array( 'showTitle' => true ),
				),
				array(
					'type'    => 'toggle',
					'key'     => 'showEmptyFields',
					'label'   => __( 'Show Empty Fields', 'metform' ),
					'default' => false,
					'help'    => __( 'Include rows for fields the user has not filled in.', 'metform' ),
				),
				array(
					'type'      => 'text',
					'key'       => 'emptyText',
					'label'     => __( 'Empty Placeholder', 'metform' ),
					'default'   => '—',
					'help'      => __( 'Text shown for an empty field when "Show Empty Fields" is on.', 'metform' ),
					'condition' => array( 'showEmptyFields' => true ),
				),
				array(
					'type'    => 'toggle',
					'key'     => 'maskPasswords',
					'label'   => __( 'Mask Passwords', 'metform' ),
					'default' => true,
					'help'    => __( 'Replace password values with ••••••.', 'metform' ),
				),
				array(
					'type'    => 'text',
					'key'     => 'excludeFields',
					'label'   => __( 'Exclude Fields', 'metform' ),
					'default' => '',
					'help'    => __( 'Comma-separated field names to hide. E.g. token, utm_source', 'metform' ),
				),
			),
			'style'   => array(
				array(
					'type'        => 'section',
					'label'       => __( 'Title', 'metform' ),
					'defaultOpen' => true,
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.titleColor',
					'label'    => __( 'Title Color', 'metform' ),
					'default'  => '#111827',
					'selector' => '{{WRAPPER}} .mfb-summary__title { color: {{VALUE}}; }',
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.titleTypography',
					'label'          => __( 'Title Typography', 'metform' ),
					'selector'       => $typography_selector( '.mfb-summary__title' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),
				array(
					'type'       => 'dimensions',
					'key'        => 'styles.titleMargin',
					'label'      => __( 'Title Margin', 'metform' ),
					'default'    => array(
						'top'    => '0px',
						'right'  => '0px',
						'bottom' => '12px',
						'left'   => '0px',
					),
					'selector'   => '{{WRAPPER}} .mfb-summary__title { margin: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
					'responsive' => true,
				),

				array(
					'type'  => 'section',
					'label' => __( 'Rows', 'metform' ),
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.rowBorderColor',
					'label'    => __( 'Row Border Color', 'metform' ),
					'default'  => '#e5e7eb',
					'selector' => '{{WRAPPER}} .mfb-summary__row, {{WRAPPER}} .mfb-summary__row:first-child { border-color: {{VALUE}}; }',
				),
				array(
					'type'     => 'background',
					'key'      => 'styles.rowBackground',
					'label'    => __( 'Row Background', 'metform' ),
					'default'  => array(
						'type'  => 'classic',
						'color' => 'transparent',
					),
					'selector' => '{{WRAPPER}} .mfb-summary__row { {{VALUE}} }',
				),
				array(
					'type'       => 'dimensions',
					'key'        => 'styles.rowPadding',
					'label'      => __( 'Row Padding', 'metform' ),
					'default'    => array(
						'top'    => '10px',
						'right'  => '0px',
						'bottom' => '10px',
						'left'   => '0px',
					),
					'selector'   => '{{WRAPPER}} .mfb-summary__row { padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
					'responsive' => true,
				),

				array(
					'type'  => 'section',
					'label' => __( 'Label', 'metform' ),
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.labelColor',
					'label'    => __( 'Label Color', 'metform' ),
					'default'  => '#374151',
					'selector' => '{{WRAPPER}} .mfb-summary__label { color: {{VALUE}}; }',
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.labelTypography',
					'label'          => __( 'Label Typography', 'metform' ),
					'selector'       => $typography_selector( '.mfb-summary__label' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),

				array(
					'type'  => 'section',
					'label' => __( 'Value', 'metform' ),
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.valueColor',
					'label'    => __( 'Value Color', 'metform' ),
					'default'  => '#111827',
					'selector' => '{{WRAPPER}} .mfb-summary__value { color: {{VALUE}}; }',
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.valueTypography',
					'label'          => __( 'Value Typography', 'metform' ),
					'selector'       => $typography_selector( '.mfb-summary__value' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),
			),
		);
	}

	private static function format_value( string $type, $raw, bool $mask_password ): ?string {
		if ( null === $raw || '' === $raw ) {
			return null;
		}
		if ( 'password' === $type ) {
			return $mask_password ? '••••••' : (string) $raw;
		}
		if ( in_array( $type, array( 'switch', 'optin', 'gdprconsent' ), true ) ) {
			return $raw ? __( 'Yes', 'metform' ) : null;
		}
		if ( 'signature' === $type ) {
			return __( 'Signed', 'metform' );
		}
		if ( 'rating' === $type ) {
			return $raw ? "{$raw} / 5" : null;
		}
		if ( 'rangeslider' === $type ) {
			return is_array( $raw ) ? ( ( $raw[0] ?? '' ) . ' – ' . ( $raw[1] ?? '' ) ) : (string) $raw;
		}
		if ( is_array( $raw ) ) {
			$joined = implode( ', ', array_filter( $raw ) );
			return '' !== $joined ? $joined : null;
		}
		if ( is_bool( $raw ) ) {
			return $raw ? __( 'Yes', 'metform' ) : __( 'No', 'metform' );
		}
		return (string) $raw;
	}

	public function render( array $settings, Render_Context $context ): string {
		$class = sanitize_html_class( (string) ( $settings['__className'] ?? '' ) ) ?: $context->get_element_id();
		return $context->is_editor()
			? $this->render_editor_badge( $settings, $class, $context )
			: $this->render_live( $settings, $class, $context );
	}

	private function render_editor_badge( array $settings, string $class, Render_Context $context ): string {
		$show_title = false !== $settings['showTitle'];
		$title      = (string) ( $settings['title'] ?: __( 'Summary', 'metform' ) );

		// Editor canvas only: the visitor's answers don't exist yet, so show a
		// simple notice (like the classic MetForm Summary widget) instead of a
		// table of made-up sample data. Preview and the live form render the
		// real summary (render_live()).
		ob_start();
		?>
		<div class="mfb-summary__wrap mfb-summary__wrap--editor <?php echo esc_attr( $class ); ?>" data-mf-block-id="<?php echo esc_attr( $context->get_block_id() ); ?>">
			<?php if ( $show_title && '' !== $title ) : ?>
			<h3 class="mfb-summary__title"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>
			<p class="mfb-summary__editor-notice"><?php esc_html_e( 'Summary will be shown on preview.', 'metform' ); ?></p>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private function render_live( array $settings, string $class, Render_Context $context ): string {
		$show_title    = false !== $settings['showTitle'];
		$title         = (string) ( $settings['title'] ?: __( 'Summary', 'metform' ) );
		$show_empty    = ! empty( $settings['showEmptyFields'] );
		$empty_text    = (string) ( $settings['emptyText'] ?: '—' );
		$mask_password = false !== $settings['maskPasswords'];

		$excluded = array_filter( array_map( 'trim', explode( ',', (string) ( $settings['excludeFields'] ?? '' ) ) ) );

		$rows = array();
		foreach ( $context->get_all_elements() as $element ) {
			if ( ! in_array( $element['type'], self::INPUT_TYPES, true ) ) {
				continue;
			}
			$name = (string) ( $element['props']['name'] ?? '' );
			if ( '' === $name || in_array( $name, $excluded, true ) ) {
				continue;
			}
			$label  = (string) ( $element['props']['label'] ?? $name );
			$rows[] = array(
				'key'   => $name,
				'label' => $label,
				'type'  => $element['type'],
			);
		}

		ob_start();
		?>
		<div class="mfb-summary__wrap <?php echo esc_attr( $class ); ?>" data-mf-block-id="<?php echo esc_attr( $context->get_block_id() ); ?>" data-mfb-summary="1" data-mfb-summary-mask-passwords="<?php echo $mask_password ? '1' : '0'; ?>" data-mfb-summary-show-empty="<?php echo $show_empty ? '1' : '0'; ?>" data-mfb-summary-empty-text="<?php echo esc_attr( $empty_text ); ?>" data-mfb-summary-yes-text="<?php echo esc_attr__( 'Yes', 'metform' ); ?>" data-mfb-summary-no-text="<?php echo esc_attr__( 'No', 'metform' ); ?>" data-mfb-summary-signed-text="<?php echo esc_attr__( 'Signed', 'metform' ); ?>">
			<?php if ( $show_title && '' !== $title ) : ?>
			<h3 class="mfb-summary__title"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>

			<dl class="mfb-summary__table" data-mfb-summary-table <?php echo ( empty( $rows ) || ! $show_empty ) ? 'hidden' : ''; ?>>
				<?php foreach ( $rows as $row ) : ?>
				<div class="mfb-summary__row" data-mfb-summary-key="<?php echo esc_attr( $row['key'] ); ?>" data-mfb-summary-type="<?php echo esc_attr( $row['type'] ); ?>" <?php echo $show_empty ? '' : 'hidden'; ?>>
					<dt class="mfb-summary__label"><?php echo esc_html( $row['label'] ); ?></dt>
					<dd class="mfb-summary__value<?php echo ( 'password' === $row['type'] && $mask_password ) ? ' mfb-summary__value--masked' : ''; ?>"><?php echo esc_html( $empty_text ); ?></dd>
				</div>
				<?php endforeach; ?>
			</dl>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
