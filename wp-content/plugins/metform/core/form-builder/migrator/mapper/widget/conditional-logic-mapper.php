<?php
/**
 * Conditional logic mapper for converting Elementor conditional logic rules to Form Builder format.
 *
 * @package MetForm\Core\FormBuilder\Migrator\Mapper\Widget
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator\Mapper\Widget;

use MetForm\Core\FormBuilder\Migrator\Value_Unwrapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Extracts and maps conditional logic configuration.
 *
 * @since 4.1.9
 */
class Conditional_Logic_Mapper {
	use Singleton;

	/**
	 * Extracts conditional logic configuration from Elementor settings.
	 *
	 * @param array $settings Elementor settings array.
	 * @return array ['conditional' => [...]] or empty array if no active condition.
	 */
	public function extract( array $settings ): array {
		$raw_list = $settings['mf_conditional_logic_form_list'] ?? array();
		if ( ! is_array( $raw_list ) || empty( $raw_list ) ) {
			return array();
		}

		$op_map = array(
			'=='          => 'equals',
			'='           => 'equals',
			'!='          => 'not_equals',
			'not_equal'   => 'not_equals',
			'not_equals'  => 'not_equals',
			'>'           => 'greater',
			'greater'     => 'greater',
			'<'           => 'less',
			'less'        => 'less',
			'>='          => 'greater_equal',
			'<='          => 'less_equal',
			'contain'     => 'contains',
			'contains'    => 'contains',
			'not_contain' => 'not_contains',
			'empty'       => 'empty',
			'not_empty'   => 'not_empty',
			'not-empty'   => 'not_empty',
		);

		$conditions = array();
		$action     = 'show';

		foreach ( $raw_list as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$field_raw = $item['mf_conditional_logic_form_if'] ?? '';
			$field     = sanitize_key( (string) Value_Unwrapper::unwrap( $field_raw ) );
			if ( '' === $field ) {
				continue;
			}

			$raw_operator    = (string) Value_Unwrapper::unwrap( $item['mf_conditional_logic_form_comparison_operators'] ?? 'not-empty' );
			$operator        = $op_map[ $raw_operator ] ?? 'equals';
			$raw_value       = $item['mf_conditional_logic_form_value'] ?? ( $item['mf_conditional_logic_form_set_value'] ?? '' );
			$condition_value = (string) Value_Unwrapper::unwrap( $raw_value );

			$conditions[] = array(
				'field'    => $field,
				'operator' => $operator,
				'value'    => $condition_value,
			);

			if ( isset( $item['mf_conditional_logic_form_action'] ) ) {
				$raw_action = Value_Unwrapper::unwrap( $item['mf_conditional_logic_form_action'] );
				if ( is_array( $raw_action ) ) {
					$action_string = reset( $raw_action );
				} else {
					$action_string = (string) $raw_action;
				}
				if ( 'hide' === $action_string ) {
					$action = 'hide';
				}
			}
		}

		if ( empty( $conditions ) ) {
			return array();
		}

		$match_op = strtolower( (string) Value_Unwrapper::unwrap( $settings['mf_conditional_logic_form_and_or_operators'] ?? 'and' ) );
		$match    = 'or' === $match_op ? 'OR' : 'AND';

		return array(
			'conditional' => array(
				'enabled'       => true,
				'action'        => $action,
				'matchCriteria' => $match,
				'conditions'    => $conditions,
			),
		);
	}
}
