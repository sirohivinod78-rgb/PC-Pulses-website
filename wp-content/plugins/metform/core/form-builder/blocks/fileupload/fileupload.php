<?php
namespace MetForm\Core\FormBuilder\Blocks\Fileupload;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Icons\Block_Icon;
use MetForm\Core\FormBuilder\Rendering\Render_Context;

defined( 'ABSPATH' ) || exit;


final class Fileupload extends Block_Base {
	public function get_name(): string {
		return 'fileupload'; }
	public function get_title(): string {
		return __( 'File Upload', 'metform' ); }
	public function get_icon(): string {
		return 'UploadCloud'; }
	public function get_category(): string {
		return 'Basic'; }

	public function get_group(): string {
		return 'file-verify'; }

	public function get_capabilities(): array {
		return array(
			'acceptsChildren' => false,
			'isField'         => true,
		);
	}

	public function get_default_settings(): array {
		return array(
			'label'           => __( 'File Upload', 'metform' ),
			'labelPosition'   => 'top',
			'name'            => '',
			'helpText'        => '',
			'uploadStyle'     => 'dropzone',
			'buttonIcon'      => '',
			'multiple'        => false,
			'allowedTypes'    => array(),
			'maxSize'         => 128,
			'required'        => false,
			'requiredMessage' => '',
			'showLabel'       => true,
			'__className'     => '',
			'styles'          => array(),
		);
	}

	public function get_property_schema(): array {
		return array(
			'showLabel'       => array( 'type' => 'boolean' ),
			'label'           => array( 'type' => 'string' ),
			'labelPosition'   => array(
				'type'       => 'string',
				'enum'       => array( 'top', 'left' ),
				'responsive' => true,
			),
			'name'            => array( 'type' => 'field_name' ),
			'helpText'        => array( 'type' => 'string' ),
			'uploadStyle'     => array(
				'type' => 'string',
				'enum' => array( 'dropzone', 'button' ),
			),
			// Only rendered by the `button` upload style — the dropzone has no
			// visible browse button (the whole zone is the click target).
			'buttonIcon'      => array( 'type' => 'icon_value' ),
			'maxSize'         => array( 'type' => 'number' ),
			'allowedTypes'    => array( 'type' => 'array' ),
			'multiple'        => array( 'type' => 'boolean' ),
			'required'        => array( 'type' => 'boolean' ),
			'requiredMessage' => array( 'type' => 'string' ),
			'__className'     => array( 'type' => 'css_class' ),
		);
	}

	private static function typography_selector( string $el ): string {
		return "{{WRAPPER}} {$el} { font-family: {{FONT_FAMILY}}; font-size: {{FONT_SIZE}}; font-weight: {{FONT_WEIGHT}}; font-style: {{FONT_STYLE}}; text-transform: {{TEXT_TRANSFORM}}; text-decoration: {{TEXT_DECORATION}}; line-height: {{LINE_HEIGHT}}; letter-spacing: {{LETTER_SPACING}}; word-spacing: {{WORD_SPACING}}; }";
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
					'default'   => __( 'File Upload', 'metform' ),
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
					'type'    => 'select',
					'key'     => 'uploadStyle',
					'label'   => __( 'Upload Style', 'metform' ),
					'default' => 'dropzone',
					'options' => array(
						array(
							'label' => __( 'Dropzone', 'metform' ),
							'value' => 'dropzone',
						),
						array(
							'label' => __( 'Button', 'metform' ),
							'value' => 'button',
						),
					),
				),
				array(
					'type'      => 'icon',
					'key'       => 'buttonIcon',
					'label'     => __( 'Upload Button Icon', 'metform' ),
					'help'      => __( 'Optional icon shown inside the browse button.', 'metform' ),
					'condition' => array( 'uploadStyle' => 'button' ),
				),
				array(
					'type'    => 'number',
					'key'     => 'maxSize',
					'label'   => __( 'File Size Limit (KB)', 'metform' ),
					'default' => 128,
					'help'    => __( 'Maximum allowed file size in kilobytes. 0 = no limit.', 'metform' ),
				),
				array(
					'type'    => 'select2',
					'key'     => 'allowedTypes',
					'label'   => __( 'Allowed File Types', 'metform' ),
					'help'    => __( 'Leave empty to allow all file types.', 'metform' ),
					'options' => array(
						array(
							'label' => 'JPG / JPEG',
							'value' => '.jpg',
						),
						array(
							'label' => 'PNG',
							'value' => '.png',
						),
						array(
							'label' => 'GIF',
							'value' => '.gif',
						),
						array(
							'label' => 'WebP',
							'value' => '.webp',
						),
						array(
							'label' => 'PDF',
							'value' => '.pdf',
						),
						array(
							'label' => 'DOC',
							'value' => '.doc',
						),
						array(
							'label' => 'DOCX',
							'value' => '.docx',
						),
						array(
							'label' => 'XLS',
							'value' => '.xls',
						),
						array(
							'label' => 'XLSX',
							'value' => '.xlsx',
						),
						array(
							'label' => 'ZIP',
							'value' => '.zip',
						),
						array(
							'label' => 'MP3',
							'value' => '.mp3',
						),
						array(
							'label' => 'MP4',
							'value' => '.mp4',
						),
					),
				),
				array(
					'type'    => 'toggle',
					'key'     => 'multiple',
					'label'   => __( 'Allow Multiple Files', 'metform' ),
					'default' => false,
					'help'    => __( 'If enabled, users can select and upload multiple files at once.', 'metform' ),
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
					'type'      => 'text',
					'key'       => 'requiredMessage',
					'label'     => __( 'Required Message', 'metform' ),
					'help'      => __( 'Shown when no file is uploaded.', 'metform' ),
					'condition' => array( 'required' => true ),
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
					'selector'       => self::typography_selector( '.mfb-label' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
					'condition'      => array( 'showLabel!' => false ),
				),
				array(
					'type'  => 'section',
					'label' => __( 'Help Text & Error', 'metform' ),
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
					'selector'       => self::typography_selector( '.mfb-field__help' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.errorTypography',
					'label'          => __( 'Error Typography', 'metform' ),
					'selector'       => self::typography_selector( '.mfb-field__error' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),

				array(
					'type'      => 'section',
					'label'     => __( 'Upload Zone', 'metform' ),
					'condition' => array( 'uploadStyle!' => 'button' ),
				),
				array(
					'type'     => 'background',
					'key'      => 'styles.zoneBackgroundColor',
					'label'    => __( 'Background', 'metform' ),
					'default'  => array(
						'type'  => 'classic',
						'color' => '#f9fafb',
					),
					'selector' => '{{WRAPPER}} .mfb-upload__zone { {{VALUE}} }',
				),
				array(
					'type'           => 'border',
					'key'            => 'styles.zoneBorder',
					'label'          => __( 'Border', 'metform' ),
					'default'        => array(
						'borderType' => 'dashed',
						'width'      => array(
							'top'    => '2px',
							'right'  => '2px',
							'bottom' => '2px',
							'left'   => '2px',
						),
						'color'      => '#d1d5db',
						'radius'     => array(
							'top'    => '8px',
							'right'  => '8px',
							'bottom' => '8px',
							'left'   => '8px',
						),
					),
					'selector'       => '{{WRAPPER}} .mfb-upload__zone { {{VALUE}} }',
					'responsive'     => true,
					'responsiveKeys' => array( 'width', 'radius' ),
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.zoneTextColor',
					'label'    => __( 'Text Color', 'metform' ),
					'default'  => '#374151',
					'selector' => '{{WRAPPER}} .mfb-upload__text { color: {{VALUE}}; }',
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.iconColor',
					'label'    => __( 'Icon Color', 'metform' ),
					'default'  => '#9ca3af',
					'selector' => '{{WRAPPER}} .mfb-upload__icon { color: {{VALUE}}; }',
				),
				array(
					'type'      => 'section',
					'label'     => __( 'Button', 'metform' ),
					'condition' => array( 'uploadStyle' => 'button' ),
				),
				array(
					'type'     => 'background',
					'key'      => 'styles.buttonBackgroundColor',
					'label'    => __( 'Button Background', 'metform' ),
					'default'  => array(
						'type'  => 'classic',
						'color' => '#2563eb',
					),
					'selector' => '{{WRAPPER}} .mfb-upload__button { {{VALUE}} }',
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.buttonTextColor',
					'label'    => __( 'Button Text Color', 'metform' ),
					'default'  => '#ffffff',
					'selector' => '{{WRAPPER}} .mfb-upload__button { color: {{VALUE}}; }',
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.buttonTypography',
					'label'          => __( 'Button Typography', 'metform' ),
					'selector'       => self::typography_selector( '.mfb-upload__button' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),
				array(
					'type'           => 'border',
					'key'            => 'styles.buttonBorder',
					'label'          => __( 'Button Border', 'metform' ),
					'default'        => array(
						'borderType' => 'solid',
						'width'      => array(
							'top'    => '0px',
							'right'  => '0px',
							'bottom' => '0px',
							'left'   => '0px',
						),
						'color'      => '#2563eb',
						'radius'     => array(
							'top'    => '6px',
							'right'  => '6px',
							'bottom' => '6px',
							'left'   => '6px',
						),
					),
					'selector'       => '{{WRAPPER}} .mfb-upload__button { border-style: {{BORDER_TYPE}}; border-width: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; border-color: {{COLOR}}; border-radius: {{RADIUS_TOP}} {{RADIUS_RIGHT}} {{RADIUS_BOTTOM}} {{RADIUS_LEFT}}; }',
					'responsive'     => true,
					'responsiveKeys' => array( 'width', 'radius' ),
				),
				array(
					'type'       => 'dimensions',
					'key'        => 'styles.buttonPadding',
					'label'      => __( 'Button Padding', 'metform' ),
					'default'    => array(
						'top'    => '8px',
						'right'  => '16px',
						'bottom' => '8px',
						'left'   => '16px',
					),
					'selector'   => '{{WRAPPER}} .mfb-upload__button { padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
					'responsive' => true,
				),
				array(
					'type'       => 'dimensions',
					'key'        => 'styles.margin',
					'label'      => __( 'Margin', 'metform' ),
					'default'    => array(
						'top'    => '0px',
						'right'  => '0px',
						'bottom' => '0px',
						'left'   => '0px',
					),
					'selector'   => '{{WRAPPER}} { margin: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
					'responsive' => true,
				),
			),
		);
	}

	public function render_markup( array $settings, Render_Context $context ): string {
		$id            = $context->get_element_id();
		$multiple      = ! empty( $settings['multiple'] );
		$required      = ! empty( $settings['required'] );
		$max_size      = (int) ( $settings['maxSize'] ?? 0 );
		$allowed_types = is_array( $settings['allowedTypes'] ?? null )
			? array_values( array_filter( $settings['allowedTypes'], 'is_scalar' ) )
			: array();
		$accept        = $allowed_types ? implode( ',', $allowed_types ) : '';

		$subtext_parts = array_filter(
			array(
				$multiple ? __( 'Multiple files allowed', 'metform' ) : __( 'One file only', 'metform' ),
				$allowed_types ? implode( ', ', $allowed_types ) : '',
				$max_size ? sprintf( __( 'up to %d KB', 'metform' ), $max_size ) : '',
			)
		);

		ob_start();
		?>
			<input
				id="<?php echo esc_attr( $id ); ?>"
				type="file"
				name="<?php echo esc_attr( $settings['name'] ) . ( $multiple ? '[]' : '' ); ?>"
				class="mfb-upload__input-native"
				<?php echo $accept ? 'accept="' . esc_attr( $accept ) . '"' : ''; ?>
				<?php echo $multiple ? 'multiple' : ''; ?>
				<?php echo $required ? 'required' : ''; ?>
				<?php echo ( $required && ! empty( $settings['requiredMessage'] ) ) ? 'data-mf-required-message="' . esc_attr( $settings['requiredMessage'] ) . '"' : ''; ?>
			>
		<?php
		$input_html = (string) ob_get_clean();

		ob_start();
		if ( 'button' === ( $settings['uploadStyle'] ?? 'dropzone' ) ) :
			$icon_html = Block_Icon::icon( $settings['buttonIcon'] ?? '', 'mfb-upload__button-icon', array(), 'span' );
			?>
		<div class="mfb-upload__control" data-mf-max-size="<?php echo esc_attr( $max_size ); ?>">
			<span class="mfb-upload__picker">
				<span class="mfb-upload__button" aria-hidden="true">
					<?php echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by Icon_Resolver. ?>
					<span class="mfb-upload__button-text"><?php echo esc_html( $multiple ? __( 'Choose files', 'metform' ) : __( 'Choose file', 'metform' ) ); ?></span>
				</span>
				<?php echo $input_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built and escaped above. ?>
			</span>
			<?php
			if ( $subtext_parts ) :
				?>
				<span class="mfb-upload__subtext"><?php echo esc_html( implode( ' · ', $subtext_parts ) ); ?></span><?php endif; ?>
		</div>
		<?php else : ?>
		<div class="mfb-upload__zone" data-mf-max-size="<?php echo esc_attr( $max_size ); ?>">
			<svg class="mfb-upload__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 14.9A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"></path><path d="M12 12v9"></path><path d="m16 16-4-4-4 4"></path></svg>
			<p class="mfb-upload__text"><?php esc_html_e( 'Click to upload or drag and drop', 'metform' ); ?></p>
			<?php
			if ( $subtext_parts ) :
				?>
				<p class="mfb-upload__subtext"><?php echo esc_html( implode( ' · ', $subtext_parts ) ); ?></p><?php endif; ?>
			<?php /* No visible "Choose File" button by design (see class doc above) — the invisible input below covers the whole zone and is itself the click target. */ ?>
			<?php echo $input_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built and escaped above. ?>
		</div>
		<?php endif; ?>
		<ul class="mfb-upload__file-list" hidden></ul>
		<?php
		return (string) ob_get_clean();
	}
}
