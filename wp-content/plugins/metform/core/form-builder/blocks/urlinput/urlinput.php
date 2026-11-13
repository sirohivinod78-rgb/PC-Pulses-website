<?php
namespace MetForm\Core\FormBuilder\Blocks\Urlinput;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Input_Controls;

defined( 'ABSPATH' ) || exit;

final class Urlinput extends Block_Base {
	public function get_name(): string {
		return 'urlinput'; }
	public function get_title(): string {
		return __( 'URL Input', 'metform' ); }
	public function get_icon(): string {
		return 'Link'; }
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
		return array(
			'label'           => __( 'URL Input', 'metform' ),
			'labelPosition'   => 'top',
			'name'            => '',
			'placeholder'     => 'https://example.com',
			'defaultLink'     => array(
				'url'               => '',
				'is_external'       => false,
				'nofollow'          => false,
				'custom_attributes' => '',
			),
			'required'        => false,
			'showLabel'       => true,
			'getValueFromUrl' => false,
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
			'placeholder'     => array( 'type' => 'string' ),
			'helpText'        => array( 'type' => 'string' ),
			'defaultLink'     => array( 'type' => 'object' ),
			'required'        => array( 'type' => 'boolean' ),
			'requiredMessage' => array( 'type' => 'string' ),
			'getValueFromUrl' => array( 'type' => 'boolean' ),
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
					'default'   => __( 'URL Input', 'metform' ),
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
					'type'    => 'text',
					'key'     => 'placeholder',
					'label'   => __( 'Placeholder', 'metform' ),
					'default' => 'https://example.com',
				),
				array(
					'type'  => 'text',
					'key'   => 'helpText',
					'label' => __( 'Help Text', 'metform' ),
					'help'  => __( 'Shown below the field to guide users', 'metform' ),
				),
				array(
					'type'                 => 'url',
					'key'                  => 'defaultLink',
					'label'                => __( 'Default Link', 'metform' ),
					'showCustomAttributes' => true,
					'help'                 => __( 'The URL pre-fills the field when the form loads; custom attributes are applied to the input.', 'metform' ),
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
					'help'      => __( 'Shown when the field is left empty.', 'metform' ),
					'condition' => array( 'required' => true ),
				),
				array(
					'type'    => 'toggle',
					'key'     => 'getValueFromUrl',
					'label'   => __( 'Get Value From URL?', 'metform' ),
					'default' => false,
					'help'    => __( 'Prefills this field from a URL query parameter matching the Field Name.', 'metform' ),
				),
			),
			'style'   => Input_Controls::style( 'input' ),
		);
	}

	protected function render_markup( array $settings, Render_Context $context ): string {
		$id    = $context->get_element_id();
		$link  = is_array( $settings['defaultLink'] ?? null ) ? $settings['defaultLink'] : array();
		$value = (string) ( $link['url'] ?? '' );
		// custom_attributes is deliberately not applied yet — arbitrary raw
		// attribute injection needs its own escaping pass, tracked as a
		// Wave 1 follow-up rather than guessed at here.

		ob_start();
		?>
		<input id="<?php echo esc_attr( $id ); ?>" type="url" name="<?php echo esc_attr( $settings['name'] ); ?>" class="mfb-field__input" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $settings['placeholder'] ); ?>" <?php echo ! empty( $settings['required'] ) ? 'required' : ''; ?>>
		<?php
		return (string) ob_get_clean();
	}
}
