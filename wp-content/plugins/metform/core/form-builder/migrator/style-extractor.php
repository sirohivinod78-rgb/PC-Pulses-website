<?php
/**
 * Style extractor facade for converting Elementor styles to Form Builder format.
 *
 * @package MetForm\Core\FormBuilder\Migrator
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator;

use MetForm\Core\FormBuilder\Migrator\Extractors\Advanced_Style_Extractor;
use MetForm\Core\FormBuilder\Migrator\Extractors\Background_Extractor;
use MetForm\Core\FormBuilder\Migrator\Extractors\Border_Extractor;
use MetForm\Core\FormBuilder\Migrator\Extractors\Box_Shadow_Extractor;
use MetForm\Core\FormBuilder\Migrator\Extractors\Button_Style_Mapper;
use MetForm\Core\FormBuilder\Migrator\Extractors\Dimension_Extractor;
use MetForm\Core\FormBuilder\Migrator\Extractors\Field_Style_Mapper;
use MetForm\Core\FormBuilder\Migrator\Extractors\Typography_Extractor;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Facade coordinating extraction of typography, borders, box-shadows, padding,
 * margins, backgrounds, and widget styles from Elementor settings.
 *
 * @since 4.1.9
 */
class Style_Extractor {
	use Singleton;

	/**
	 * Extracts dimension values from an Elementor setting into FormBuilder format.
	 * e.g. [ 'unit' => 'px', 'top' => '10', 'right' => '20', ... ] -> [ 'top' => '10px', 'right' => '20px', ... ]
	 *
	 * @param mixed $setting Dimension setting.
	 * @return array|null
	 */
	public function extract_dimensions( $setting ): ?array {
		return Dimension_Extractor::instance()->extract_dimensions( $setting );
	}

	/**
	 * Extracts Advanced-tab layout & spacing styles from Elementor widget settings.
	 *
	 * @param array $settings Elementor settings array.
	 * @return array
	 */
	public function extract_advanced_styles( array $settings ): array {
		return Advanced_Style_Extractor::instance()->extract( $settings );
	}

	/**
	 * Extracts a CSS size string (e.g. '21px', '1.5em') from an Elementor range/size setting.
	 *
	 * @param mixed $setting Size setting (array with size & unit, numeric, or string).
	 * @return string|null
	 */
	public function extract_size_value( $setting ): ?string {
		return Dimension_Extractor::instance()->extract_size_value( $setting );
	}

	/**
	 * Extracts typography properties from Elementor typography controls.
	 *
	 * @param array  $settings Elementor settings array.
	 * @param string $prefix   Key prefix (e.g. 'mf_input_label_typography_' or 'typography_').
	 * @return array|null
	 */
	public function extract_typography( array $settings, string $prefix ): ?array {
		return Typography_Extractor::instance()->extract( $settings, $prefix );
	}

	/**
	 * Extracts border properties from Elementor border controls.
	 *
	 * @param array  $settings Elementor settings array.
	 * @param string $prefix   Key prefix (e.g. 'mf_input_border_' or 'border_').
	 * @return array|null
	 */
	public function extract_border( array $settings, string $prefix ): ?array {
		return Border_Extractor::instance()->extract( $settings, $prefix );
	}

	/**
	 * Extracts box shadow from Elementor box_shadow_box_shadow setting as an array
	 * compatible with both Css_Value_Formatter and Form Builder BoxShadowControl.
	 *
	 * @param mixed $shadow Shadow settings map.
	 * @return array|null
	 */
	public function extract_box_shadow( $shadow ): ?array {
		return Box_Shadow_Extractor::instance()->extract( $shadow );
	}

	/**
	 * Extracts an opacity float value (0.0 - 1.0) from Elementor slider/numeric setting.
	 *
	 * @param mixed $setting Opacity setting value.
	 * @return float|null
	 */
	public function extract_opacity_value( $setting ): ?float {
		return Dimension_Extractor::instance()->extract_opacity_value( $setting );
	}

	/**
	 * Extracts a transition duration in milliseconds (e.g. '300ms') from Elementor transition setting.
	 *
	 * @param mixed $setting Transition setting (e.g. 0.3, '0.3s', 300, ['size' => 0.3, 'unit' => 's']).
	 * @return string|null
	 */
	public function extract_transition_duration( $setting ): ?string {
		return Dimension_Extractor::instance()->extract_transition_duration( $setting );
	}

	/**
	 * Extracts background properties (Classic: color, image with position/size/repeat/attachment;
	 * Gradient: type, angle, position, colorStart, locationStart, colorEnd, locationEnd;
	 * and optional overlay opacity) from Elementor Group_Control_Background settings.
	 *
	 * @param array  $settings Elementor settings array.
	 * @param string $prefix   Key prefix (e.g. 'background_', 'background_hover_', '_background_', '_background_hover_', 'background_overlay_').
	 * @return array|null Form Builder background control value array, or null if not active.
	 */
	public function extract_background( array $settings, string $prefix = 'background_' ): ?array {
		return Background_Extractor::instance()->extract( $settings, $prefix );
	}

	/**
	 * Maps visual styles for standard input fields into props['styles'].
	 *
	 * @param array $settings Widget settings.
	 * @return array
	 */
	public function map_field_styles( array $settings ): array {
		return Field_Style_Mapper::instance()->map( $settings );
	}

	/**
	 * Maps visual styles for buttons into props['styles'].
	 *
	 * @param array $settings Widget settings.
	 * @return array
	 */
	public function map_button_styles( array $settings ): array {
		return Button_Style_Mapper::instance()->map( $settings );
	}

	/**
	 * Wraps a non-empty styles array in ['styles' => $styles] for merging into block props.
	 *
	 * @param array $styles Styles dictionary.
	 * @return array
	 */
	public function wrap( array $styles ): array {
		return ! empty( $styles ) ? array( 'styles' => $styles ) : array();
	}
}
