<?php

namespace MetForm\Core\Entries;

use MetForm_Pro\Core\FormBuilder\Blocks\Calculation\Expression_Evaluator;

defined('ABSPATH') || exit;

final class Form_Builder_Document
{
    /**
     * Gets the saved core/form-builder document for a form, if it has one.
     *
     * @param int $form_id Form post ID.
     * @return array|null The document array, or null when this form has no
     *                     (or an empty) form-builder document — i.e. it's a
     *                     legacy Elementor-widget form instead.
     */
    public static function get_document($form_id)
    {
        $document = get_post_meta($form_id, '_metform_builder_document', true);

        return (is_array($document) && !empty($document['elements'])) ? $document : null;
    }

    /**
     * Gets normalized field definitions for a form-builder form.
     * @param int $form_id Form post ID.
     * @return array|null Field definitions keyed by input name, or null when
     *                     this form has no form-builder document (caller
     *                     should fall back to the legacy Elementor mapping).
     */
    public static function get_fields($form_id)
    {
        $document = self::get_document($form_id);

        if ($document === null) {
            return null;
        }

        return Map_Block_Document::fields($document);
    }

    /**
     * Recursively extracts normalized field definitions from a standalone document tree.
     *
     * @deprecated Not on the live `get_fields()` path (which uses
     *             `Map_Block_Document::fields()` instead) — kept, unchanged,
     *             for backward compatibility with any external caller.
     *
     * @param array $elements Array of element nodes from _metform_builder_document.
     * @return array Normalized array of field definition objects.
     */
    public static function extract_fields_from_standalone_doc(array $elements): array
    {
        $fields = [];

        foreach ($elements as $element) {
            if (!is_array($element)) {
                continue;
            }

            $type  = sanitize_key($element['type'] ?? '');
            $props = (array) ($element['props'] ?? $element['settings'] ?? []);
            $name  = sanitize_text_field($props['name'] ?? '');

            // If this element is an input with a field name, normalize it for MetForm
            if (!empty($name)) {
                $field_obj = new \stdClass();
                $field_obj->mf_input_name  = $name;
                $field_obj->mf_input_label = sanitize_text_field($props['label'] ?? $name);
                $field_obj->widgetType     = (strpos($type, 'mf-') === 0) ? $type : ('mf-' . $type);
                $field_obj->mf_input_required = !empty($props['required']) ? 'yes' : 'no';
                $field_obj->mf_input_min_length = isset($props['minLength']) ? (string) $props['minLength'] : '';
                $field_obj->mf_input_max_length = isset($props['maxLength']) ? (string) $props['maxLength'] : '';
                $field_obj->mf_input_min_value = isset($props['minValue']) ? (string) $props['minValue'] : '';
                $field_obj->mf_input_max_value = isset($props['maxValue']) ? (string) $props['maxValue'] : '';
                $field_obj->mf_input_validation_type = $props['validationType'] ?? 'none';
                $field_obj->mf_input_validation_expression = $props['validationExpression'] ?? '';

                // Advanced / Pro fields support
                if (isset($props['paymentFieldName'])) {
                    $field_obj->mf_input_payment_field_name = $props['paymentFieldName'];
                }
                if (isset($props['repeaterFields'])) {
                    $field_obj->mf_input_repeater = $props['repeaterFields'];
                }

                $fields[$name] = $field_obj;
            }

            // Recursively traverse children (containers, columns, sections, grids)
            if (!empty($element['children']) && is_array($element['children'])) {
                $fields = array_merge($fields, self::extract_fields_from_standalone_doc($element['children']));
            }
        }

        return $fields;
    }

    /**
     * Server-side "never trust the client" recompute for every Calculation
     * @param int   $form_id   Form post ID.
     * @param array $form_data Sanitized submitted form data (`Action::$form_data`).
     * @return array The form data, with every Calculation field's value
     *               overwritten by its server-recomputed result.
     */
    public static function recompute_calculation_fields($form_id, array $form_data): array
    {
        $document = self::get_document($form_id);
        if ($document === null) {
            return $form_data;
        }

        foreach ($document['elements'] as $element) {
            if (!is_array($element) || 'calculation' !== ($element['type'] ?? '')) {
                continue;
            }

            $props = is_array($element['props'] ?? null) ? $element['props'] : [];
            $name = is_scalar($props['name'] ?? null) ? (string) $props['name'] : '';
            if ('' === $name) {
                continue;
            }

            if (!class_exists(Expression_Evaluator::class)) {
                continue;
            }

            $expression = is_scalar($props['expression'] ?? null) ? (string) $props['expression'] : '';
            $decimals = is_numeric($props['decimals'] ?? null) ? (int) $props['decimals'] : 2;

            try {
                $result = Expression_Evaluator::evaluate($expression, $form_data);
            } catch (\RuntimeException $e) {
                $result = null;
            }

            $form_data[$name] = (null === $result || !is_finite($result))
                ? ''
                : number_format($result, $decimals, '.', '');
        }

        return $form_data;
    }
}
