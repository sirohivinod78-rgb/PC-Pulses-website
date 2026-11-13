<?php
/**
 * Advanced widget mapper for specialized input and form controls.
 *
 * @package MetForm\Core\FormBuilder\Migrator\Mapper\Widget
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator\Mapper\Widget;

use MetForm\Core\FormBuilder\Block_Registry;
use MetForm\Core\FormBuilder\Migrator\Style_Extractor;
use MetForm\Core\FormBuilder\Migrator\Value_Unwrapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Maps specialized MetForm widgets (calculation, switches, sliders, buttons, captchas, etc.).
 *
 * @since 4.1.9
 */
class Advanced_Widget_Mapper {
	use Singleton;

	/**
	 * Maps advanced or specialized widget types.
	 *
	 * @param string      $type      Normalized widget type.
	 * @param array       $settings  Widget settings.
	 * @param string      $node_id   Node ID.
	 * @param string      $block_id  Block ID.
	 * @param string|null $parent_id Parent container ID.
	 * @return array|null Mapped block array or null if not handled.
	 */
	public function map( string $type, array $settings, string $node_id, string $block_id, ?string $parent_id ): ?array {
		$property_mapper    = Input_Property_Mapper::instance();
		$choice_mapper      = Choice_Widget_Mapper::instance();
		$conditional_mapper = Conditional_Logic_Mapper::instance();
		$style_extractor    = Style_Extractor::instance();

		switch ( $type ) {
			case 'number':
			case 'numeric':
				$props = $property_mapper->map( $settings, $node_id, esc_html__( 'Number', 'metform' ) );
				if ( isset( $settings['mf_input_min_value'] ) && '' !== (string) $settings['mf_input_min_value'] ) {
					$props['min'] = (float) Value_Unwrapper::unwrap( $settings['mf_input_min_value'] );
				}
				if ( isset( $settings['mf_input_max_value'] ) && '' !== (string) $settings['mf_input_max_value'] ) {
					$props['max'] = (float) Value_Unwrapper::unwrap( $settings['mf_input_max_value'] );
				}
				if ( isset( $settings['mf_input_step_value'] ) && '' !== (string) $settings['mf_input_step_value'] ) {
					$props['step'] = (float) Value_Unwrapper::unwrap( $settings['mf_input_step_value'] );
				}
				return array(
					'id'       => $block_id,
					'type'     => 'number',
					'parentId' => $parent_id,
					'props'    => $props,
				);

			case 'date':
			case 'datepicker':
				$props               = $property_mapper->map( $settings, $node_id, esc_html__( 'Date', 'metform' ) );
				$props['dateFormat'] = (string) Value_Unwrapper::unwrap( $settings['mf_input_date_format'] ?? 'Y-m-d' );
				return array(
					'id'       => $block_id,
					'type'     => 'date',
					'parentId' => $parent_id,
					'props'    => $props,
				);

			case 'time':
			case 'timepicker':
				$props               = $property_mapper->map( $settings, $node_id, esc_html__( 'Time', 'metform' ) );
				$props['timeFormat'] = (string) Value_Unwrapper::unwrap( $settings['mf_input_time_format'] ?? 'H:i' );
				return array(
					'id'       => $block_id,
					'type'     => 'time',
					'parentId' => $parent_id,
					'props'    => $props,
				);

			case 'textarea':
			case 'multiline':
			case 'message':
			case 'text-editor':
				if ( isset( $settings['editor'] ) && ! isset( $settings['mf_input_name'] ) ) {
					return Content_Widget_Mapper::instance()->map_paragraph( $settings, $node_id, $block_id, $parent_id );
				}
				$is_pro        = 'text-editor' === $type && null !== Block_Registry::instance()->get( 'text-editor' );
				$props         = $property_mapper->map( $settings, $node_id, esc_html__( 'text-editor' === $type ? 'Text Editor' : 'Textarea', 'metform' ) );
				$props['rows'] = isset( $settings['mf_input_textarea_rows'] ) ? (int) Value_Unwrapper::unwrap( $settings['mf_input_textarea_rows'] ) : 4;
				return array(
					'id'       => $block_id,
					'type'     => $is_pro ? 'text-editor' : 'textarea',
					'parentId' => $parent_id,
					'props'    => $props,
				);

			case 'calculation':
				$is_pro = null !== Block_Registry::instance()->get( 'calculation' );
				$props  = $property_mapper->map( $settings, $node_id, esc_html__( 'Calculation', 'metform' ) );
				if ( isset( $settings['mf_input_calculation_equation'] ) ) {
					$equation            = (string) Value_Unwrapper::unwrap( $settings['mf_input_calculation_equation'] );
					$props['expression'] = $equation;
					$props['equation']   = $equation;
				}
				if ( isset( $settings['mf_input_calculation_equation_prefix'] ) ) {
					$props['prefix'] = (string) Value_Unwrapper::unwrap( $settings['mf_input_calculation_equation_prefix'] );
				}
				if ( isset( $settings['mf_input_calculation_equation_suffix'] ) ) {
					$props['suffix'] = (string) Value_Unwrapper::unwrap( $settings['mf_input_calculation_equation_suffix'] );
				}
				if ( ( (string) Value_Unwrapper::unwrap( $settings['disable_fraction_dight'] ?? 'no' ) ) === 'yes' ) {
					$props['decimals'] = 0;
				} elseif ( isset( $settings['max_fraction_dight'] ) && '' !== (string) $settings['max_fraction_dight'] ) {
					$props['decimals'] = (int) Value_Unwrapper::unwrap( $settings['max_fraction_dight'] );
				} else {
					$props['decimals'] = 2;
				}
				if ( isset( $props['styles'] ) && is_array( $props['styles'] ) ) {
					if ( isset( $props['styles']['typography'] ) ) {
						$props['styles']['resultTypography'] = $props['styles']['typography'];
					}
					if ( isset( $props['styles']['color'] ) ) {
						$props['styles']['resultColor'] = $props['styles']['color'];
					}
					if ( isset( $props['styles']['border'] ) ) {
						$props['styles']['resultBorder'] = $props['styles']['border'];
					}
					if ( isset( $props['styles']['background'] ) ) {
						$props['styles']['resultBackground'] = $props['styles']['background'];
					}
					if ( isset( $props['styles']['padding'] ) ) {
						$props['styles']['resultPadding'] = $props['styles']['padding'];
					}
				}
				return array(
					'id'       => $block_id,
					'type'     => $is_pro ? 'calculation' : 'number',
					'parentId' => $parent_id,
					'props'    => $props,
				);

			case 'switch':
			case 'switch-field':
			case 'toggle':
				return array(
					'id'       => $block_id,
					'type'     => 'switch',
					'parentId' => $parent_id,
					'props'    => array_merge(
						array(
							'label'        => (string) Value_Unwrapper::unwrap( $settings['mf_input_label'] ?? esc_html__( 'Switch', 'metform' ) ),
							'name'         => $property_mapper->resolve_name( $settings, 'mf-switch-' . $node_id ),
							'showLabel'    => ( (string) Value_Unwrapper::unwrap( $settings['mf_input_label_status'] ?? 'yes' ) ) === 'yes',
							'activeText'   => (string) Value_Unwrapper::unwrap( $settings['mf_swtich_enable_text'] ?? ( $settings['mf_switch_active_text'] ?? esc_html__( 'Yes', 'metform' ) ) ),
							'inactiveText' => (string) Value_Unwrapper::unwrap( $settings['mf_swtich_disable_text'] ?? ( $settings['mf_switch_inactive_text'] ?? esc_html__( 'No', 'metform' ) ) ),
						),
						$this->extract_styles_wrapper( $style_extractor->map_field_styles( $settings ) ),
						$conditional_mapper->extract( $settings )
					),
				);

			case 'range':
			case 'rangeslider':
			case 'slider':
				return array(
					'id'       => $block_id,
					'type'     => 'rangeslider',
					'parentId' => $parent_id,
					'props'    => array_merge(
						array(
							'label'        => (string) Value_Unwrapper::unwrap( $settings['mf_input_label'] ?? esc_html__( 'Range', 'metform' ) ),
							'name'         => $property_mapper->resolve_name( $settings, 'mf-range-' . $node_id ),
							'showLabel'    => ( (string) Value_Unwrapper::unwrap( $settings['mf_input_label_status'] ?? 'yes' ) ) === 'yes',
							'min'          => isset( $settings['mf_input_range_min'] ) ? (float) Value_Unwrapper::unwrap( $settings['mf_input_range_min'] ) : 0,
							'max'          => isset( $settings['mf_input_range_max'] ) ? (float) Value_Unwrapper::unwrap( $settings['mf_input_range_max'] ) : 100,
							'step'         => isset( $settings['mf_input_range_step'] ) ? (float) Value_Unwrapper::unwrap( $settings['mf_input_range_step'] ) : 1,
							'defaultValue' => isset( $settings['mf_input_range_default'] ) ? (string) Value_Unwrapper::unwrap( $settings['mf_input_range_default'] ) : '0',
						),
						$this->extract_styles_wrapper( $style_extractor->map_field_styles( $settings ) ),
						$conditional_mapper->extract( $settings )
					),
				);

			case 'listing-optin':
			case 'optin':
				return array(
					'id'       => $block_id,
					'type'     => 'optin',
					'parentId' => $parent_id,
					'props'    => array_merge(
						array(
							'label'     => (string) Value_Unwrapper::unwrap( $settings['mf_input_label'] ?? esc_html__( 'Opt-in', 'metform' ) ),
							'name'      => $property_mapper->resolve_name( $settings, 'mf-optin-' . $node_id ),
							'termsText' => (string) Value_Unwrapper::unwrap( $settings['mf_listing_optin_option_text'] ?? ( $settings['mf_input_optin_text'] ?? '' ) ),
							'required'  => ( (string) Value_Unwrapper::unwrap( $settings['mf_input_required'] ?? 'no' ) ) === 'yes',
						),
						$this->extract_styles_wrapper( $style_extractor->map_field_styles( $settings ) ),
						$conditional_mapper->extract( $settings )
					),
				);

			case 'gdpr-consent':
			case 'gdpr':
			case 'consent':
				return array(
					'id'       => $block_id,
					'type'     => 'gdprconsent',
					'parentId' => $parent_id,
					'props'    => array_merge(
						array(
							'label'       => (string) Value_Unwrapper::unwrap( $settings['mf_input_label'] ?? esc_html__( 'GDPR Consent', 'metform' ) ),
							'name'        => $property_mapper->resolve_name( $settings, 'mf-gdpr-' . $node_id ),
							'consentText' => (string) Value_Unwrapper::unwrap( $settings['mf_gdpr_consent_option_text'] ?? ( $settings['mf_input_gdpr_text'] ?? '' ) ),
							'required'    => true,
						),
						$this->extract_styles_wrapper( $style_extractor->map_field_styles( $settings ) ),
						$conditional_mapper->extract( $settings )
					),
				);

			case 'rating':
			case 'star-rating':
				$icon_shape = (string) Value_Unwrapper::unwrap( $settings['mf_input_rating_star_type'] ?? 'star' );
				$shape      = in_array( $icon_shape, array( 'star', 'heart', 'thumb', 'smiley' ), true ) ? $icon_shape : 'star';
				return array(
					'id'       => $block_id,
					'type'     => 'rating',
					'parentId' => $parent_id,
					'props'    => array_merge(
						array(
							'label'     => (string) Value_Unwrapper::unwrap( $settings['mf_input_label'] ?? esc_html__( 'Rating', 'metform' ) ),
							'name'      => $property_mapper->resolve_name( $settings, 'mf-rating-' . $node_id ),
							'showLabel' => ( (string) Value_Unwrapper::unwrap( $settings['mf_input_label_status'] ?? 'yes' ) ) === 'yes',
							'maxStars'  => isset( $settings['mf_input_rating_stars'] ) ? (int) Value_Unwrapper::unwrap( $settings['mf_input_rating_stars'] ) : 5,
							'iconShape' => $shape,
							'required'  => ( (string) Value_Unwrapper::unwrap( $settings['mf_input_required'] ?? 'no' ) ) === 'yes',
						),
						$this->extract_styles_wrapper( $style_extractor->map_field_styles( $settings ) ),
						$conditional_mapper->extract( $settings )
					),
				);

			case 'file-upload':
			case 'fileupload':
			case 'file':
			case 'upload':
				return array(
					'id'       => $block_id,
					'type'     => 'fileupload',
					'parentId' => $parent_id,
					'props'    => array_merge(
						array(
							'label'             => (string) Value_Unwrapper::unwrap( $settings['mf_input_label'] ?? esc_html__( 'File Upload', 'metform' ) ),
							'name'              => $property_mapper->resolve_name( $settings, 'mf-file-' . $node_id ),
							'showLabel'         => ( (string) Value_Unwrapper::unwrap( $settings['mf_input_label_status'] ?? 'yes' ) ) === 'yes',
							'helpText'          => (string) Value_Unwrapper::unwrap( $settings['mf_input_help_text'] ?? '' ),
							'required'          => ( (string) Value_Unwrapper::unwrap( $settings['mf_input_required'] ?? 'no' ) ) === 'yes',
							'allowedExtensions' => (string) Value_Unwrapper::unwrap( $settings['mf_input_file_types'] ?? 'jpg,jpeg,png,pdf' ),
							'maxFileSize'       => isset( $settings['mf_input_file_size'] ) ? (int) Value_Unwrapper::unwrap( $settings['mf_input_file_size'] ) : 5,
						),
						$this->extract_styles_wrapper( $style_extractor->map_field_styles( $settings ) ),
						$conditional_mapper->extract( $settings )
					),
				);

			case 'summary':
				return array(
					'id'       => $block_id,
					'type'     => 'summary',
					'parentId' => $parent_id,
					'props'    => array(
						'title'     => (string) Value_Unwrapper::unwrap( $settings['mf_input_label'] ?? esc_html__( 'Summary', 'metform' ) ),
						'showTitle' => ( (string) Value_Unwrapper::unwrap( $settings['mf_input_label_status'] ?? 'yes' ) ) === 'yes',
					),
				);

			case 'recaptcha':
				return array(
					'id'       => $block_id,
					'type'     => 'recaptcha',
					'parentId' => $parent_id,
					'props'    => array_merge(
						array(
							'name'  => $property_mapper->resolve_name( $settings, 'mf-recaptcha' ),
							'label' => (string) Value_Unwrapper::unwrap( $settings['mf_input_label'] ?? '' ),
						),
						$this->extract_styles_wrapper( $style_extractor->map_field_styles( $settings ) )
					),
				);

			case 'simple-captcha':
			case 'simplecaptcha':
			case 'captcha':
				return array(
					'id'       => $block_id,
					'type'     => 'simplecaptcha',
					'parentId' => $parent_id,
					'props'    => array(
						'label' => (string) Value_Unwrapper::unwrap( $settings['mf_input_label'] ?? esc_html__( 'Captcha', 'metform' ) ),
						'name'  => $property_mapper->resolve_name( $settings, 'mf-captcha-' . $node_id ),
					),
				);

			case 'button':
			case 'submit':
			case 'form-button':
				$align_raw = (string) Value_Unwrapper::unwrap( $settings['mf_btn_align'] ?? ( $settings['alignment'] ?? ( $settings['align'] ?? 'left' ) ) );
				$alignment = in_array( $align_raw, array( 'left', 'center', 'right', 'full' ), true ) ? $align_raw : ( 'justify' === $align_raw ? 'full' : 'left' );
				return array(
					'id'       => $block_id,
					'type'     => 'button',
					'parentId' => $parent_id,
					'props'    => array_merge(
						array(
							'text'       => (string) Value_Unwrapper::unwrap( $settings['mf_btn_text'] ?? ( $settings['text'] ?? esc_html__( 'Submit Button', 'metform' ) ) ),
							'buttonType' => 'submit',
							'alignment'  => $alignment,
						),
						$this->extract_styles_wrapper( $style_extractor->map_button_styles( $settings ) ),
						$conditional_mapper->extract( $settings )
					),
				);

			case 'map-location':
			case 'maplocation':
				$is_pro = null !== Block_Registry::instance()->get( 'maplocation' );
				$props  = $property_mapper->map( $settings, $node_id, esc_html__( 'Google Map Location', 'metform' ) );
				if ( $is_pro ) {
					$props['defaultLat'] = (string) Value_Unwrapper::unwrap( $settings['mf_map_default_lat'] ?? '23.8103' );
					$props['defaultLng'] = (string) Value_Unwrapper::unwrap( $settings['mf_map_default_lng'] ?? '90.4125' );
				}
				return array(
					'id'       => $block_id,
					'type'     => $is_pro ? 'maplocation' : 'input',
					'parentId' => $parent_id,
					'props'    => $props,
				);

			case 'payment-method':
			case 'paymentmethod':
				$is_pro = null !== Block_Registry::instance()->get( 'paymentmethod' );
				$props  = $choice_mapper->map_option_props( $settings, $node_id, esc_html__( 'Payment Method', 'metform' ) );
				if ( $is_pro && isset( $settings['mf_input_payment_method_options'] ) ) {
					$props['paymentMethods'] = (array) Value_Unwrapper::unwrap( $settings['mf_input_payment_method_options'] );
				}
				return array(
					'id'       => $block_id,
					'type'     => $is_pro ? 'paymentmethod' : 'radio',
					'parentId' => $parent_id,
					'props'    => $props,
				);

			case 'simple-repeater':
			case 'simplerepeater':
				$is_pro = null !== Block_Registry::instance()->get( 'simplerepeater' );
				$props  = $property_mapper->map( $settings, $node_id, esc_html__( 'Simple Repeater', 'metform' ) );
				if ( $is_pro ) {
					$props['addButtonText']    = (string) Value_Unwrapper::unwrap( $settings['mf_input_repeater_add_btn_txt'] ?? esc_html__( 'Add', 'metform' ) );
					$props['removeButtonText'] = (string) Value_Unwrapper::unwrap( $settings['mf_input_repeater_remove_btn_txt'] ?? esc_html__( 'Delete', 'metform' ) );
				}
				return array(
					'id'       => $block_id,
					'type'     => $is_pro ? 'simplerepeater' : 'input',
					'parentId' => $parent_id,
					'props'    => $props,
				);

			default:
				return null;
		}
	}

	/**
	 * Wraps a non-empty styles array in ['styles' => $styles] for merging into props.
	 *
	 * @param array $styles Styles dictionary.
	 * @return array
	 */
	public function extract_styles_wrapper( array $styles ): array {
		return ! empty( $styles ) ? array( 'styles' => $styles ) : array();
	}
}
