<?php

namespace MetForm\Core\Entries;

defined('ABSPATH') || exit;

class Form_Data
{
    private static $form_data = null;

    private static function get_form_data($form_id)
    {
        if (static::$form_data) {
            return static::$form_data;
        }

        $map_data = \MetForm\Core\Entries\Action::instance()->get_fields($form_id);

        return static::$form_data = json_decode(json_encode($map_data), true);
    }

    /**
     * Simple Repeater: sub-field name => label, from the field's
     * `mf_input_repeater` list (form-builder forms get it from
     * Map_Block_Document; legacy Elementor forms store the same keys).
     *
     * @param array $field_map One field's map data.
     * @return array<string,string>
     */
    protected static function repeater_labels_by_name($field_map)
    {
        $labels = [];
        foreach ((array) ($field_map['mf_input_repeater'] ?? []) as $sub) {
            $name  = is_array($sub) ? (string) ($sub['mf_input_repeater_name'] ?? '') : '';
            $label = is_array($sub) ? (string) ($sub['mf_input_repeater_label'] ?? '') : '';
            // Sub-fields with option lists keep the older display path,
            // which can show option names instead of raw values.
            $has_options = is_array($sub) && !empty($sub['mf_input_repeater_option']);
            if ('' !== $name && '' !== $label && !$has_options) {
                $labels[$name] = $label;
            }
        }
        return $labels;
    }

    /**
     * Label for one processed repeater key ("phone-2" → label of "phone"),
     * or null to fall back to the older position-based lookup.
     *
     * @param array  $labels repeater_labels_by_name() result.
     * @param string $key    Processed key: "{name}-{row}".
     * @return string|null
     */
    protected static function repeater_label_for_key($labels, $key)
    {
        if (empty($labels)) {
            return null;
        }
        $name = preg_replace('/-\d+$/', '', (string) $key);
        return isset($labels[$name]) ? $labels[$name] : null;
    }

    /**
     * Resolves raw submitted value(s) against an options list,
     * returning title, value, and composite display strings.
     *
     * Supports scalar values, comma-separated strings, and native array inputs.
     * Matches both modern {label, value} and legacy Elementor {mf_input_option_text, mf_input_option_value} schemas.
     *
     * @param mixed $raw_value    Submitted field value.
     * @param array $options_list The widget's mf_input_list array.
     * @return array<int, array{title: string, value: string, display: string}>
     */
    protected static function resolve_option_display_items($raw_value, array $options_list): array
    {
        if (null === $raw_value || '' === $raw_value) {
            return [];
        }

        $selected_values = is_array($raw_value)
            ? array_map('trim', array_map('strval', $raw_value))
            : array_map('trim', explode(',', (string) $raw_value));

        $selected_values = array_filter($selected_values, static fn($v) => '' !== $v);
        if (empty($selected_values)) {
            return [];
        }

        $results = [];

        foreach ($selected_values as $selected) {
            $matched = false;

            foreach ($options_list as $opt) {
                if (!is_array($opt)) {
                    continue;
                }

                $opt_val = (string) ($opt['value'] ?? $opt['mf_input_option_value'] ?? '');
                $opt_title = (string) (
                    $opt['label']
                    ?? $opt['mf_input_option_text']
                    ?? $opt['mf_image_select_title']
                    ?? $opt_val
                );

                if ($opt_val === (string) $selected) {
                    $results[] = [
                        'title'   => $opt_title,
                        'value'   => $opt_val,
                        'display' => ($opt_title !== '' && $opt_title !== $opt_val)
                            ? ($opt_title . ' - ' . $opt_val)
                            : $opt_val,
                    ];
                    $matched = true;
                    break;
                }
            }

            // Fallback if submitted value does not match defined options
            if (!$matched) {
                $results[] = [
                    'title'   => (string) $selected,
                    'value'   => (string) $selected,
                    'display' => (string) $selected,
                ];
            }
        }

        return $results;
    }

    protected static function criteriaMet($conditional_value, $conditional_operator, $conditional_field_value)
    {
        switch ($conditional_operator) {
            case '<':
                return $conditional_value < $conditional_field_value;
            case '<=':
                return $conditional_value <= $conditional_field_value;
            case '>':
                return $conditional_value > $conditional_field_value;
            case '>=':
                return $conditional_value >= $conditional_field_value;
            case '==':
                return $conditional_value == $conditional_field_value;
            case '!=':
                return $conditional_value != $conditional_field_value;
            case 'empty':
                return empty($conditional_value);
            case 'not-empty':
                return !empty($conditional_value);
            default:
                return false;
        }
    }

    public static function format_form_data($form_id, $form_data)
    {
        $map_data = static::get_form_data($form_id);
        $form_settings = \MetForm\Core\Forms\Action::instance()->get_all_data($form_id);

        ob_start();
        ?>
        <div class="mf-entries-main-card-wrapper">
            <?php
            foreach ($map_data as $key => $value) {
                if (in_array($value['widgetType'], ['mf-simple-captcha', 'mf-recaptcha', 'mf-file-upload'])) {
                    continue;
                }

                $conditions = isset($map_data[$key]["mf_conditional_logic_form_list"]) ? $map_data[$key]["mf_conditional_logic_form_list"] : [];
                $no_of_condition = count($conditions);
                $checking_result = array();
                $condition_match_criteria = !empty($map_data[$key]["mf_conditional_logic_form_and_or_operators"]) ? $map_data[$key]["mf_conditional_logic_form_and_or_operators"] : '';

                list($map_data, $form_data, $checking_result) = self::condition_criteria_match($map_data, $key, $conditions, $form_data, $checking_result);

                if (!empty($map_data[$key]["mf_conditional_logic_form_enable"]) && $map_data[$key]["mf_conditional_logic_form_enable"] == "yes" && $no_of_condition > 0) {
                    $condition_action = !empty($map_data[$key]["mf_conditional_logic_form_action"]) ? $map_data[$key]["mf_conditional_logic_form_action"] : 'show';

                    if ($condition_match_criteria == "or") {
                        $is_validated = in_array(true, $checking_result, true);
                    } else {
                        $is_validated = !in_array(false, $checking_result, true);
                    }

                    $should_hide = ($condition_action === 'hide') ? $is_validated : !$is_validated;
                    if ($should_hide) {
                        continue;
                    }
                }

                $entry_row_correct_class = '';
                if(isset($form_settings['form_type']) && $form_settings['form_type'] === 'quiz-form' && isset($form_data['wrong-answer']) && isset($form_data['right-answer'])){
                    $wrong_answers = array_map('sanitize_text_field', explode(",", $form_data['wrong-answer']));
                    $right_answers = array_map('sanitize_text_field', explode(",", $form_data['right-answer']));

                    if(in_array($map_data[$key]['mf_input_name'], $right_answers)){
                        $entry_row_correct_class = 'mf-entries-correct-result';
                    } elseif( in_array($map_data[$key]['mf_input_name'], $wrong_answers) ){
                        $entry_row_correct_class = 'mf-entries-wrong-result';
                    }
                }

                // Row container
                echo sprintf("<div class='mf-entries-data-row %s'>", esc_attr($entry_row_correct_class));
                // Field label
                echo "<span class='mf-entries-field-label'>" . esc_html(($map_data[$key]['mf_input_label'] != '') ? $map_data[$key]['mf_input_label'] : $key) . "</span>";
                // Value container
                echo "<div class='mf-entries-value-pill-row'>";
                // Value box
                echo "<div class='mf-entries-field-value-box'>";

                // ── Standard text-like fields ──────────────────────────
                if (!in_array($value['widgetType'], ['mf-file-upload', 'mf-textarea', 'mf-simple-repeater', 'mf-signature', 'mf-like-dislike', 'mf-credit-card', 'mf-image-select', 'mf-checkbox', 'mf-mobile'])) {
                    $has_options = isset($map_data[$key]['mf_input_list']) && is_array($map_data[$key]['mf_input_list']) && !empty($map_data[$key]['mf_input_list']);
                    $show_names  = !empty($form_settings['mf_field_name_show']) && (int) $form_settings['mf_field_name_show'] === 1;

                    if ($has_options) {
                        $items = static::resolve_option_display_items($form_data[$key] ?? null, $map_data[$key]['mf_input_list']);
                        if (!empty($items)) {
                            foreach ($items as $item) {
                                $text = $show_names ? $item['display'] : $item['value'];
                                echo "<span class='mf-entries-badge-pill'>" . esc_html($text) . "</span>";
                            }
                        }
                    } else if (isset($value['widgetType']) && $value['widgetType'] == 'mf-text-editor') {
                        echo \MetForm\Utils\Util::kses_entry_rich_text($form_data[$key]);
                    } else {
                        $output = isset($form_data[$key]) ? (is_array($form_data[$key]) ? implode(', ', $form_data[$key]) : $form_data[$key]) : '';
                        echo esc_html($output);
                    }
                }

                // ── Mobile ────────────────────────────────────────────
                if (isset($value['widgetType']) && $value['widgetType'] == 'mf-mobile') {
                    echo esc_html(isset($form_data[$key]) ? $form_data[$key] : '');
                }

                // ── Checkbox ──────────────────────────────────────────
                if (isset($value['widgetType']) && $value['widgetType'] == 'mf-checkbox') {
                    $options_list = (isset($map_data[$key]['mf_input_list']) && is_array($map_data[$key]['mf_input_list'])) ? $map_data[$key]['mf_input_list'] : [];
                    $show_names   = !empty($form_settings['mf_field_name_show']) && (int) $form_settings['mf_field_name_show'] === 1;

                    $items = static::resolve_option_display_items($form_data[$key] ?? null, $options_list);
                    if (!empty($items)) {
                        foreach ($items as $item) {
                            $text = $show_names ? $item['display'] : $item['value'];
                            echo "<span class='mf-entries-badge-pill'>" . esc_html($text) . "</span>";
                        }
                    }
                }

                // ── Image Select ──────────────────────────────────────
                if (isset($value['widgetType']) && $value['widgetType'] == 'mf-image-select') {
                    $options_list = (isset($map_data[$key]['mf_input_list']) && is_array($map_data[$key]['mf_input_list'])) ? $map_data[$key]['mf_input_list'] : [];
                    $show_names   = !empty($form_settings['mf_field_name_show']) && (int) $form_settings['mf_field_name_show'] === 1;

                    $items = static::resolve_option_display_items($form_data[$key] ?? null, $options_list);
                    if (!empty($items)) {
                        foreach ($items as $item) {
                            $text = $show_names ? $item['display'] : $item['value'];
                            echo "<span class='mf-entries-badge-pill'>" . esc_html($text) . "</span>";
                        }
                    }
                }

                // ── Signature ─────────────────────────────────────────
                if (isset($value['widgetType']) && $value['widgetType'] == 'mf-signature') {
                    if (!empty($form_data[$key])) {
                        echo "<img class='mf-entries-signature-img' src='" . esc_attr($form_data[$key]) . "'>";
                    } else {
                        echo esc_html__('No signature provided', 'metform');
                    }
                }

                // ── Textarea ──────────────────────────────────────────
                if (isset($value['widgetType']) && $value['widgetType'] == 'mf-textarea') {
                    echo wp_kses(isset($form_data[$key]) ? nl2br($form_data[$key]) : '', ['br' => []]);
                }

                // ── Simple Repeater ───────────────────────────────────
                if (isset($value['widgetType']) && $value['widgetType'] == 'mf-simple-repeater') {
                    $repeater_data = (array_key_exists($key, $form_data)) ? $form_data[$key] : [];
                    $count_index = 0; $previous_level = null;
                    $repeater_labels = static::repeater_labels_by_name($map_data[$key] ?? []);
                    foreach ($repeater_data as $k => $v) {
                        $repeater_level = static::repeater_label_for_key($repeater_labels, $k);
                        if (null !== $repeater_level) {
                            echo "<div class='mf-entries-repeater-row'><strong>" . esc_html($repeater_level) . ": </strong><span>" . esc_html($v) . "</span></div>";
                            $count_index++;
                            continue;
                        }
                        if (isset($map_data[$key]["mf_input_repeater"][$count_index]["mf_input_repeater_label"])) {
                            $repeater_level = $map_data[$key]["mf_input_repeater"][$count_index]["mf_input_repeater_label"];
                        }
                        if (isset($map_data[$key]["mf_input_repeater"][$count_index - 1]["mf_input_repeater_label"])) {
                            $previous_level = $map_data[$key]["mf_input_repeater"][$count_index - 1]["mf_input_repeater_label"];
                        }
                        if ($repeater_level == null && $previous_level != null) { $repeater_level = $previous_level; }

                        echo "<div class='mf-entries-repeater-row'><strong>" . esc_html(($repeater_level != null) ? $repeater_level : $k) . ": </strong>";
                        $show_names  = !empty($form_settings['mf_field_name_show']) && (int) $form_settings['mf_field_name_show'] === 1;
                        $rep_opt_str = $map_data[$key]["mf_input_repeater"][$count_index]["mf_input_repeater_option"] ?? '';

                        if ($show_names && '' !== trim((string) $rep_opt_str)) {
                            $options = array_filter(array_map('trim', explode("\n", (string) $rep_opt_str)));
                            $optionOutput = [];
                            $selected_options = is_array($v) ? $v : array_map('trim', explode(',', (string) $v));

                            foreach ($options as $option) {
                                $parts = explode('|', $option, 2);
                                $optionName = $parts[0];
                                $optionValue = $parts[1] ?? $parts[0];
                                if (in_array($optionValue, $selected_options, true) || in_array($optionName, $selected_options, true)) {
                                    $optionOutput[] = ($optionName !== $optionValue) ? "$optionName - $optionValue" : $optionName;
                                }
                            }
                            echo "<span>" . esc_html(!empty($optionOutput) ? implode(", ", $optionOutput) : (is_array($v) ? implode(', ', $v) : $v)) . "</span>";
                        } else {
                            echo "<span>" . esc_html(is_array($v) ? implode(', ', $v) : $v) . "</span>";
                        }
                        echo "</div>";
                        $count_index++;
                        if ($count_index == count($map_data[$key]["mf_input_repeater"])) {
                            $count_index = 0;
                            echo "<div class='mf-entries-repeater-divider'></div>";
                        }
                    }
                }

                // ── Credit Card ───────────────────────────────────────
                if (isset($value['widgetType']) && $value['widgetType'] == 'mf-credit-card') {
                    echo "<div><strong>" . esc_html__('Card Number:', 'metform') . "</strong> " . esc_html(isset($form_data[$key]) ? $form_data[$key] : '') . "</div>";
                    if (isset($form_data[$key . '--type'])) {
                        $type = $form_data[$key . '--type'];
                        $type = ($type === "amex") ? 'American Express' : $type;
                        echo "<div><strong>" . esc_html__('Card Type:', 'metform') . "</strong> " . esc_html(ucfirst($type)) . "</div>";
                    }
                }

                // ── Like / Dislike ────────────────────────────────────
                if (isset($value['widgetType']) && ($value['widgetType'] == 'mf-like-dislike')) {
                    $like_dislike = (isset($form_data[$key]) ? $form_data[$key] : '');
                    echo (($like_dislike == '1') ? "<span class='dashicons dashicons-thumbs-up' style='color:#2563eb;'></span>" : "");
                    echo (($like_dislike == '0') ? "<span class='dashicons dashicons-thumbs-down' style='color:#ef4444;'></span>" : "");
                }

                echo "</div>"; // close .mf-entries-field-value-box
                if ( isset( $form_settings['form_type'] ) && $form_settings['form_type'] === 'quiz-form'
                    && isset( $form_data['wrong-answer'] ) && isset( $form_data['right-answer'] ) ) {

                    if ( $entry_row_correct_class === 'mf-entries-correct-result' ) {
                        echo "<span class='mf-entries-result-pill mf-entries-result-pill--correct'>
                                <span class='dashicons dashicons-yes'></span>" . esc_html__('Correct', 'metform') . "
                            </span>";
                    } elseif ( $entry_row_correct_class === 'mf-entries-wrong-result' ) {
                        echo "<span class='mf-entries-result-pill mf-entries-result-pill--wrong'>
                                <span class='dashicons dashicons-no-alt'></span>" . esc_html__('Wrong', 'metform') . "
                            </span>";
                    }
                }
                echo "</div>"; // close .mf-entries-data-row
                echo "</div>"; // close .mf-entries-value-pill-row
            }
            ?>
            <?php do_action('metform_after_entries_table_data', $form_id, $form_data, $map_data); ?>
        </div>
        <?php
        $data_html = ob_get_contents();
        ob_end_clean();
        return $data_html;
    }

    public static function format_data_for_mail($form_id, $form_data, $file_info)
    {
        $map_data = static::get_form_data($form_id);
        $form_settings = \MetForm\Core\Forms\Action::instance()->get_all_data($form_id);

        //$file_meta_data = get_post_meta( $post_id, 'metform_entries__file_upload', true );

        ob_start();

    ?>
        <div style="border-left:5px solid #2EB5AB;padding-left:5px;">
            <table width="100%" cellpadding="5" cellspacing="0" bgcolor="#FFFFFF" style="border: 1px solid #EAF2FA; word-break: break-word;">
                <tbody>
                    <?php
                    foreach ($map_data as $key => $value) {

                        if (in_array($value['widgetType'], ['mf-simple-captcha', 'mf-recaptcha', 'mf-file-upload', 'mf-button'])) {
                            continue;
                        }

                 

                        $conditions = isset($map_data[$key]["mf_conditional_logic_form_list"]) ? $map_data[$key]["mf_conditional_logic_form_list"] : [];
                        $no_of_condition = count($conditions);
                        $checking_result = array();
                        $condition_match_criteria = !empty($map_data[$key]["mf_conditional_logic_form_and_or_operators"]) ? $map_data[$key]["mf_conditional_logic_form_and_or_operators"] : '';

                        list($map_data, $form_data, $checking_result) = self::condition_criteria_match($map_data, $key, $conditions, $form_data, $checking_result);

                        if (!empty($map_data[$key]["mf_conditional_logic_form_enable"]) && $map_data[$key]["mf_conditional_logic_form_enable"] == "yes" && $no_of_condition > 0) {
                            $condition_action = !empty($map_data[$key]["mf_conditional_logic_form_action"]) ? $map_data[$key]["mf_conditional_logic_form_action"] : 'show';

                            if ($condition_match_criteria == "or") {
                                $is_validated = in_array(true, $checking_result, true);
                            } else {
                                $is_validated = !in_array(false, $checking_result, true);
                            }

                            $should_hide = ($condition_action === 'hide') ? $is_validated : !$is_validated;
                            if ($should_hide) {
                                continue;
                            }
                        }

                        echo "<tr bgcolor='#EAF2FA'>";
                        echo "<td colspan='2'><strong>" . esc_html(($map_data[$key]['mf_input_label'] != '') ? $map_data[$key]['mf_input_label'] : $key) . "</strong></td>";
                        echo "</tr>";
                        echo "<tr bgcolor='#FFFFFF'>";
                        echo "<td width='20'>&nbsp;</td>";

                        if (!in_array($value['widgetType'], ['mf-file-upload', 'mf-textarea', 'mf-simple-repeater', 'mf-signature', 'mf-like-dislike', 'mf-credit-card','mf-image-select','mf-checkbox','mf-mobile'])) {                            
                            $has_options = isset($map_data[$key]['mf_input_list']) && is_array($map_data[$key]['mf_input_list']) && !empty($map_data[$key]['mf_input_list']);
                            $show_names  = !empty($form_settings['mf_field_name_show']) && (int) $form_settings['mf_field_name_show'] === 1;

                            if ($has_options) {
                                $items = static::resolve_option_display_items($form_data[$key] ?? null, $map_data[$key]['mf_input_list']);
                                $rendered = array_map(static fn($it) => $show_names ? $it['display'] : $it['value'], $items);
                                echo "<td>" . esc_html(implode(', ', $rendered)) . "</td>";
                            } else if(isset($value['widgetType']) && $value['widgetType'] == 'mf-text-editor') {
                                echo "<td>" . \MetForm\Utils\Util::kses_entry_rich_text($form_data[$key]) . "</td>";
                            }else{
                                $output = isset($form_data[$key]) ? (is_array($form_data[$key]) ? implode(', ', $form_data[$key]) : $form_data[$key]) : '';
                                echo "<td>" . esc_html($output) . "</td>";
                            }
                        }

                        if (isset($value['widgetType']) && $value['widgetType'] == 'mf-mobile') {
                            $output = isset($form_data[$key]) && !empty($form_data[$key]) ? esc_html($form_data[$key]) : '';
                            echo "<td>" . $output . "</td>";
                        }
                        
                        if (isset($value['widgetType']) && $value['widgetType'] == 'mf-checkbox') {
                            $options_list = (isset($map_data[$key]['mf_input_list']) && is_array($map_data[$key]['mf_input_list'])) ? $map_data[$key]['mf_input_list'] : [];
                            $show_names   = !empty($form_settings['mf_field_name_show']) && (int) $form_settings['mf_field_name_show'] === 1;

                            $items    = static::resolve_option_display_items($form_data[$key] ?? null, $options_list);
                            $rendered = array_map(static fn($it) => $show_names ? $it['display'] : $it['value'], $items);
                            echo "<td>" . esc_html(implode(', ', $rendered)) . "</td>";
                        }

                        if (isset($value['widgetType']) && $value['widgetType'] == 'mf-image-select') {
                            $options_list = (isset($map_data[$key]['mf_input_list']) && is_array($map_data[$key]['mf_input_list'])) ? $map_data[$key]['mf_input_list'] : [];
                            $show_names   = !empty($form_settings['mf_field_name_show']) && (int) $form_settings['mf_field_name_show'] === 1;

                            $items    = static::resolve_option_display_items($form_data[$key] ?? null, $options_list);
                            $rendered = array_map(static fn($it) => $show_names ? $it['display'] : $it['value'], $items);
                            echo "<td>" . esc_html(implode(', ', $rendered)) . "</td>";
                        }                      
                        
                        if (isset($value['widgetType']) && $value['widgetType'] == 'mf-signature') {
                            if (!empty($form_data[$key])) {
                                echo "<td><img class='signature-img' src='" . esc_attr($form_data[$key]) . "'></td>";
                            } else {
                                echo "<td>" . esc_html__('No signature provided', 'metform') . "</td>";
                            }
                        }

                        if (isset($value['widgetType']) && $value['widgetType'] == 'mf-textarea') {
                            echo "<td>" . wp_kses( isset($form_data[$key]) ? nl2br( $form_data[$key] ) : '', [ 'br' => [] ]) . "</td>";
                        }

                        if (isset($value['widgetType']) && $value['widgetType'] == 'mf-simple-repeater') {
                            echo "<td>";
                            $repeater_data = ((array_key_exists($key, $form_data)) ? $form_data[$key] : []);                            
                            $count_index = 0; // number of repeater input field counter
                            $previous_level = null;
                            $repeater_labels = static::repeater_labels_by_name($map_data[$key] ?? []);
                        
                            foreach ($repeater_data as $k => $v) {
                                $repeater_level = static::repeater_label_for_key($repeater_labels, $k);
                                if (null !== $repeater_level) {
                                    echo "<strong>" . esc_html($repeater_level) . ": </strong>" . wp_kses_post("<span>" . esc_html($v) . "</span>") . "<br>";
                                    $count_index++;
                                    if (isset($map_data[$key]["mf_input_repeater"]) && $count_index == count($map_data[$key]["mf_input_repeater"])) {
                                        $count_index = 0;
                                        echo "<div class='simple-repeater-entry-data-divider'></div>";
                                    }
                                    continue;
                                }
                        
                                if ((isset($map_data[$key]["mf_input_repeater"][$count_index]["mf_input_repeater_label"]))) {
                                    $repeater_level = $map_data[$key]["mf_input_repeater"][$count_index]["mf_input_repeater_label"];
                                }
                                if ((isset($map_data[$key]["mf_input_repeater"][$count_index - 1]["mf_input_repeater_label"]))) {
                                    $previous_level = $map_data[$key]["mf_input_repeater"][$count_index - 1]["mf_input_repeater_label"];
                                }
                        
                                if ($repeater_level == null && $previous_level != null) {
                                    $repeater_level = $previous_level;
                                }
                        
                                echo "<strong>" . esc_html(($repeater_level != null) ? $repeater_level : $k) . ": </strong>";
                                $show_names  = !empty($form_settings['mf_field_name_show']) && (int) $form_settings['mf_field_name_show'] === 1;
                                $rep_opt_str = $map_data[$key]["mf_input_repeater"][$count_index]["mf_input_repeater_option"] ?? '';

                                if ($show_names && '' !== trim((string) $rep_opt_str)) {
                                    $options = array_filter(array_map('trim', explode("\n", (string) $rep_opt_str)));
                                    $optionOutput = [];
                                    $selected_options = is_array($v) ? $v : array_map('trim', explode(',', (string) $v));

                                    foreach ($options as $option) {
                                        $parts       = explode('|', $option, 2);
                                        $optionName  = $parts[0];
                                        $optionValue = $parts[1] ?? $parts[0];
                                        if (in_array($optionValue, $selected_options, true) || in_array($optionName, $selected_options, true)) {
                                            $optionOutput[] = ($optionName !== $optionValue) ? "$optionName - $optionValue" : $optionName;
                                        }
                                    }
                                    echo wp_kses_post("<span>" . esc_html(!empty($optionOutput) ? implode(", ", $optionOutput) : (is_array($v) ? implode(', ', $v) : $v)) . "</span>");
                                } else {
                                    echo wp_kses_post("<span>" . esc_html(is_array($v) ? implode(', ', $v) : $v) . "</span>");
                                }
                        
                                echo "<br>";
                                $count_index++;
                        
                                if ($count_index == count($map_data[$key]["mf_input_repeater"])) {
                                    $count_index = 0;
                                    echo "<div class='simple-repeater-entry-data-divider'></div>";
                                }
                            }
                            echo "</td>";
                        }

                        /**
                         * Credit Card form data entries
                         */
                        if (isset($value['widgetType']) && $value['widgetType'] == 'mf-credit-card') {
                            echo "<td><strong>". esc_html__('Number:', 'metform') ."</strong> " . esc_html(isset($form_data[$key]) ? $form_data[$key] : '') . "</br>";
                            if (isset($form_data[$key . '--type'])) {
                                $type = $form_data[$key . '--type'];
                                $type = ($type === "amex") ? 'Amerian Express' : $type;
                                echo "<strong>".esc_html__('Type:', 'metform')."</strong> " . esc_html(ucfirst($type)) . " Card</td>";
                            }
                        }

                        echo "</tr>";
                    }

                    if (!empty($file_info)) {
                        foreach ($file_info as $key => $file) {
                            echo "<tr bgcolor='#EAF2FA'>";
                            echo "<td colspan='2'><strong>" . esc_html(($map_data[$key]['mf_input_label'] != '') ? $map_data[$key]['mf_input_label'] : $key) . "</strong></td>";
                            echo "</tr>";
                            echo "<tr bgcolor='#FFFFFF'>";
                            echo "<td width='20'>&nbsp;</td>";
                                echo "<td>";
                                    foreach($file as $value) {
                                        echo "<a href='" . esc_url(isset($value['url']) ? $value['url'] : '') . "'>".esc_html__('Download', 'metform')."</a> ";
                                    }
                                echo "</td>";
                            echo "</tr>";
                        }
                    }

                    ?>
                </tbody>
            </table>
        </div>
    <?php
        $data_html = ob_get_contents();
        ob_end_clean();
        return $data_html;
    }

    /**
     * @param mixed $map_data
     * @param int|string $key
     * @param mixed $conditions
     * @param $form_data
     * @param array $checking_result
     * @return array
     */
    public static function condition_criteria_match($map_data, $key, $conditions, $form_data, $checking_result)
    {
        if (!empty($map_data[$key]["mf_conditional_logic_form_enable"]) && $map_data[$key]["mf_conditional_logic_form_enable"] == "yes") {

            foreach ($conditions as $condition_key => $condition) {

                $conditional_field_name = !empty($condition["mf_conditional_logic_form_if"]) ? $condition["mf_conditional_logic_form_if"] : '';

                $conditional_operator = !empty($condition["mf_conditional_logic_form_comparison_operators"]) ? $condition["mf_conditional_logic_form_comparison_operators"] : 'not-empty';

                // operator value not-empty hole backend theke data ase na tai not-empty default vabe dewa hoise..
                // if condition logic change after submission previous entries condition also change.

                $conditional_field_value = !empty($condition["mf_conditional_logic_form_value"]) ? $condition["mf_conditional_logic_form_value"] : '';

                // Resolve dynamic reference in conditional field value if it matches [field-name] pattern
                if (is_string($conditional_field_value) && preg_match('/^\[([^\]]+)\]$/', $conditional_field_value, $matches)) {
                    $ref_key = $matches[1];
                    if (array_key_exists($ref_key, $form_data)) {
                        $ref_val = $form_data[$ref_key];
                        if (is_array($ref_val)) {
                            $conditional_field_value = implode(',', array_map('strval', $ref_val));
                        } elseif (is_scalar($ref_val) || is_null($ref_val)) {
                            $conditional_field_value = (string) $ref_val;
                        } else {
                            $conditional_field_value = '';
                        }
                    } else {
                        $conditional_field_value = '';
                    }
                }

                $conditional_value_data = !empty($form_data[$conditional_field_name]) ? $form_data[$conditional_field_name] : '';
                $condition_value_array = explode(",", $conditional_value_data);

                $is_switch = false;
                if (isset($map_data[$conditional_field_name]['widgetType']) && $map_data[$conditional_field_name]['widgetType'] === 'mf-switch') {
                    $is_switch = true;
                } else {
                    foreach ($map_data as $field) {
                        if (isset($field['mf_input_name']) && $field['mf_input_name'] === $conditional_field_name && isset($field['widgetType']) && $field['widgetType'] === 'mf-switch') {
                            $is_switch = true;
                            break;
                        }
                    }
                }

                if ($is_switch && in_array($conditional_operator, ['==', '!='])) {
                    $lower_array = array_map('strtolower', $condition_value_array);
                    $condition_value_index = array_search(strtolower($conditional_field_value), $lower_array);
                    $conditional_value = isset($condition_value_array[$condition_value_index]) ? $condition_value_array[$condition_value_index] : "";

                    if (strcasecmp($conditional_value, $conditional_field_value) === 0) {
                        $conditional_field_value = $conditional_value;
                    }
                } else {
                    $condition_value_index = array_search($conditional_field_value, $condition_value_array);
                    $conditional_value = isset($condition_value_array[$condition_value_index]) ? $condition_value_array[$condition_value_index] : "";
                }

                $criteria_match = static::criteriaMet($conditional_value, $conditional_operator, $conditional_field_value);
                array_push($checking_result, $criteria_match);
            }
        }
        return array($map_data, $form_data, $checking_result);
    }
}
