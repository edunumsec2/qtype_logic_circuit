<?php

/**
 * Logic circuit question renderer class.
 *
 * @package    qtype_logiccircuit
 * @copyright  2026 Groupe Modulo
 * @license    CC BY-NC-SA
 */

use ColinODell\Json5\SyntaxError;
use \core\url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/logiccircuit/vendor/autoload.php');


/**
 * Renders the logic-editor questions.
 */
class qtype_logiccircuit_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        global $PAGE, $OUTPUT;

        $question = $qa->get_question();
        $init_state = $question->initialstate;
        $editor_mode = $question->editormode;
        $components_to_show = $question->componentstoshow;
        $question_name = $question->name;
        $question_text = $question->questiontext;

        $response = $qa->get_last_qt_data();
        $answer_input_name = $qa->get_qt_field_name('answer');
        $test_results_input_name = $qa->get_qt_field_name('test_results');
        $answer_value = isset($response['answer']) ? self::normalise_json_value_for_editor($response['answer']) : '';
        $test_results_value = isset($response['test_results'])
            ? self::normalise_json_value_for_editor($response['test_results'])
            : '';
        $readonly = $options->readonly;
        $readonly_answer_pre = '';

        if ($readonly && $answer_value !== '') {
            $readonly_answer_pre = self::render_as_pre($answer_value);
        }

        $readonly_test_results = '';
        if ($readonly && $test_results_value !== '') {
            $readonly_test_results = $question->get_response_analysis_string($test_results_value);
        }

        if (debugging('', DEBUG_DEVELOPER)) {
            $is_debug = true;
        } else {
            $is_debug = false;
        }

        static $assets_included = false;
        if (!$assets_included) {
            $PAGE->requires->js(new url('https://logic.modulo-info.ch/simulator/lib/bundle.js'));
            $PAGE->requires->js_call_amd('qtype_logiccircuit/save-result', 'init');
            $assets_included = true;
        }


        $template_data = [
            'question_name' => $question_name,
            'question_text' => $question_text,
            'init_state' => $init_state,
            'editor_mode' => $editor_mode,
            'components_to_show' => $components_to_show,
            'answer_input_name' => $answer_input_name,
            'test_results_input_name' => $test_results_input_name,
            'answer_value' => $answer_value,
            'test_results_value' => $test_results_value,
            'readonly_answer_pre' => $readonly_answer_pre,
            'readonly_test_results' => $readonly_test_results,
            'readonly' => $readonly,
            'is_debug' => $is_debug
        ];

        return $OUTPUT->render_from_template('qtype_logiccircuit/logic-editor', $template_data);
    }

    public static function normalise_json_value_for_editor(string $value): string {
        if ($value === '') {
            return $value;
        }

        try {
            $decoded = json5_decode($value, true);
        } catch (TypeError | SyntaxError $error) {
            return $value;
        }

        if (!is_string($decoded)) {
            return $value;
        }

        try {
            $nesteddecoded = json5_decode(trim($decoded), true);
        } catch (TypeError | SyntaxError $error) {
            return $value;
        }

        return is_array($nesteddecoded) ? trim($decoded) : $value;
    }

    private static function render_as_pre(string $answervalue): string {
        return html_writer::tag('pre', s($answervalue), ['class' => 'qtype-logiccircuit-answer-json5']);
    }
}
