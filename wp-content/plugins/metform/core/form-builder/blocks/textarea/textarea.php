<?php
namespace MetForm\Core\FormBuilder\Blocks\Textarea;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Input_Controls;

defined( 'ABSPATH' ) || exit;

/**
 * Multi-line text field. Not final: the Message block
 * (blocks/message/message.php) extends it, reusing its controls, schema,
 * validation and markup and only changing name/title/icon/default label —
 * so a fix here applies to both.
 */
class Textarea extends Block_Base {
	protected const RESIZE_OPTIONS = array( 'vertical', 'horizontal', 'both', 'none' );

	public function get_name(): string {
		return 'textarea'; }
	public function get_title(): string {
		return __( 'Textarea', 'metform' ); }

	/**
	 * Default field label (new field's Label, and the Label control's
	 * default). Overridden by subclasses such as Message.
	 */
	protected function get_default_label(): string {
		return __( 'Textarea', 'metform' );
	}
	public function get_icon(): string {
		return 'FileText'; }
	public function get_category(): string {
		return 'Basic'; }

	public function get_group(): string {
		return 'input'; }

	public function get_capabilities(): array {
		return array(
			'acceptsChildren' => false,
			'isField'         => true,
		);
	}

	public function get_default_settings(): array {
		return array_merge(
			Input_Controls::default_props( $this->get_default_label(), __( 'Enter your message here', 'metform' ) ),
			array(
				'rows'   => 4,
				'resize' => 'vertical',
			)
		);
	}

	public function get_property_schema(): array {
		return array_merge(
			Input_Controls::schema(),
			array(
				'rows'   => array( 'type' => 'number' ),
				'resize' => array(
					'type' => 'string',
					'enum' => self::RESIZE_OPTIONS,
				),
			)
		);
	}

	public function register_controls(): array {
		return array(
			'content' => Input_Controls::content(
				$this->get_default_label(),
				array(
					array(
						'type'    => 'number',
						'key'     => 'rows',
						'label'   => __( 'Rows', 'metform' ),
						'default' => 4,
						'min'     => 2,
						'max'     => 20,
					),
					array(
						'type'    => 'select',
						'key'     => 'resize',
						'label'   => __( 'Resize', 'metform' ),
						'default' => 'vertical',
						'inline'  => true,
						'options' => array(
							array(
								'label' => __( 'Vertical', 'metform' ),
								'value' => 'vertical',
							),
							array(
								'label' => __( 'Horizontal', 'metform' ),
								'value' => 'horizontal',
							),
							array(
								'label' => __( 'Both', 'metform' ),
								'value' => 'both',
							),
							array(
								'label' => __( 'None', 'metform' ),
								'value' => 'none',
							),
						),
					),
				)
			),
			'style'   => Input_Controls::style(
				'textarea',
				array(
					array(
						'type'  => 'section',
						'label' => __( 'Size', 'metform' ),
					),
					array(
						'type'       => 'range',
						'key'        => 'styles.height',
						'label'      => __( 'Height', 'metform' ),
						'default'    => '100px',
						'units'      => array( 'px', '%', 'em', 'rem' ),
						'selector'   => '{{WRAPPER}} textarea { height: {{VALUE}}; }',
						'responsive' => true,
					),
				)
			),
		);
	}

	protected function render_markup( array $settings, Render_Context $context ): string {
		$id               = $context->get_element_id();
		$rows             = max( 1, (int) ( $settings['rows'] ?? 4 ) );
		$resize           = in_array( $settings['resize'] ?? 'vertical', self::RESIZE_OPTIONS, true ) ? $settings['resize'] : 'vertical';
		$validation_attrs = Input_Controls::validation_attributes( $settings );

		ob_start();
		?>
		<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $settings['name'] ); ?>" class="mfb-field__textarea" rows="<?php echo esc_attr( $rows ); ?>" placeholder="<?php echo esc_attr( $settings['placeholder'] ); ?>" style="resize: <?php echo esc_attr( $resize ); ?>;" <?php echo $validation_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_textarea( (string) ( $settings['defaultValue'] ?? '' ) ); ?></textarea>
		<?php
		return (string) ob_get_clean();
	}
}
