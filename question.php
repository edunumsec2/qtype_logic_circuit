<?php

/**
 * Logic circuit question definition class.
 *
 * @package    qtype_logiccircuit
 * @copyright  2025 Groupe Modulo
 * @license    CC BY-NC-SA
 */

use ColinODell\Json5\SyntaxError;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/questionbase.php');
require_once($CFG->dirroot . '/question/type/logiccircuit/vendor/autoload.php');

/**
 * Represents a logic circuit question.
 *
 */
class qtype_logiccircuit_question extends question_graded_automatically {
    public $initialstate;
    public $editormode;
    public $componentstoshow;

    public function get_expected_data() {
        debugging("Getting expected data...", DEBUG_DEVELOPER);
        return array(
            'answer' => PARAM_RAW,
            'test_results' => PARAM_RAW
        );
    }

    public function get_correct_response() {
        return null;
    }

    public function summarise_response(array $response) {
        debugging("Summarising responses...", DEBUG_DEVELOPER);
        debugging(print_r($response, true), DEBUG_DEVELOPER);

        $hasanswer = isset($response['answer']) && trim($response['answer']) !== '';
        $hastestresults = isset($response['test_results']) && trim($response['test_results']) !== '';

        if (!$hasanswer && !$hastestresults) {
            return null;
        }

        $result = '';

        if ($hastestresults) {
            $result .= "# Tests: \n";
            $result .= $this->get_response_analysis_string($response['test_results']) . "\n";
        }

        if ($hasanswer) {
            $result .= "# Circuit: \n";
            $result .= trim($response['answer']) . "\n";
        }

        return rtrim($result);
    }

    public function get_response_analysis_string(string $test_results): string {
        $responseanalysis = $this->analyse_response($test_results);
        $testsummary = $responseanalysis['test_summary'];

        if (empty($testsummary)) {
            return '';
        }

        $result = '';

        foreach ($testsummary as $testname => $testresult) {
            $result .= "Test $testname: $testresult\n";
        }

        return rtrim($result);
    }

    // TODO do we need to classify the response ?
    public function classify_response(array $response) {
        debugging("Classifying response...", DEBUG_DEVELOPER);

        return array();
    }

    public function is_complete_response(array $response) {
        debugging("Is complete response ?");

        if(!isset($response['answer']) || !isset($response['test_results'])) {
            return false;
        }

        if (trim($response['answer']) === '' || trim($response['test_results']) === '') {
            return false;
        }

        debugging(print_r($response['answer'], true), DEBUG_DEVELOPER);
        debugging(print_r($response['test_results'], true), DEBUG_DEVELOPER);

        try {
            $answer = $this->decode_response_json($response['answer']);
            $testresults = $this->decode_response_json($response['test_results']);
        } catch (TypeError | SyntaxError $error) {
            //error_log($error->getMessage());
            return false;
        }

        return $this->is_valid_answer_payload($answer) &&
            $this->is_valid_test_results_payload($testresults);
    }

    public function get_validation_error(array $response) {
        if ($this->is_gradable_response($response)) {
            return '';
        }
        return get_string('answer_incomplete', 'qtype_logiccircuit');
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        if(!$this->not_empty_response($prevresponse) && !$this->not_empty_response($newresponse)) {
            return true;
        } else if(!$this->not_empty_response($prevresponse)) {
            return false;
        } else {
            try {
                $prevresponseparsed = $this->decode_response_json($prevresponse['answer']);
                $newresponseparsed = $this->decode_response_json($newresponse['answer']);
            } catch (TypeError | SyntaxError $error) {
                return question_utils::arrays_same_at_key_missing_is_blank(
                    $prevresponse,
                    $newresponse,
                    'answer'
                );
            }

            return $this->normalise_response_value($prevresponseparsed) ===
                $this->normalise_response_value($newresponseparsed);
        }
    }

    public function grade_response(array $response) {
        debugging("Grading response...", DEBUG_DEVELOPER);
        debugging(print_r($response, true), DEBUG_DEVELOPER);

        if (!isset($response['test_results']) || trim($response['test_results']) === '') {
            $fraction = 0;
        } else {
            $responseanalysis = $this->analyse_response($response['test_results']);
            $fraction = $responseanalysis['fraction'];
        }

        return array($fraction, question_state::graded_state_for_fraction($fraction));
    }

    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        return parent::check_file_access(
            $qa,
            $options,
            $component,
            $filearea,
            $args,
            $forcedownload
        );
    }

    /**
     * Return the question settings that define this question as structured data.
     *
     * @param question_attempt $qa the current attempt for which we are exporting the settings.
     * @param question_display_options $options the question display options which say which aspects of the question
     * should be visible.
     * @return mixed structure representing the question settings. In web services, this will be JSON-encoded.
     */
    public function get_question_definition_for_external_rendering(question_attempt $qa, question_display_options $options) {
        // No need to return anything, external clients do not need additional information for rendering this question type.
        return null;
    }

    private function analyse_response_internal(string $testResults): array {
        $testresultsarray = $this->decode_response_json($testResults);
        $testcaseresults = $this->extract_test_case_results($testresultsarray);

        if (empty($testcaseresults)) {
            return array(
                'fraction' => 0,
                'test_summary' => []
            );
        }

        $totaltests = 0;
        $successfulltests = 0;
        $testsummaryarray = array();

        foreach ($testcaseresults as $index => $testcaseresult) {
            if (!is_array($testcaseresult) || !isset($testcaseresult[0], $testcaseresult[1])) {
                continue;
            }

            $testcasedescription = $testcaseresult[0];
            $testresult = $testcaseresult[1];

            if (!is_array($testcasedescription) || !is_array($testresult) || !isset($testresult['_tag'])) {
                continue;
            }

            $testname = $testcasedescription['name'] ?? "test_$index";
            $testtag = $testresult['_tag'];

            $totaltests += 1;
            $testsummaryarray[$testname] = $testtag;

            if ($testtag === 'pass') {
                $successfulltests += 1;
            }
        }

        if ($totaltests === 0) {
            return array(
                'fraction' => 0,
                'test_summary' => []
            );
        }

        return array(
            'fraction' => round($successfulltests / $totaltests, 2),
            'test_summary' => $testsummaryarray
        );
    }

    private function not_empty_response(array $response) {
        return (isset($response['answer']) && !empty($response['answer'])) &&
            (isset($response['test_results']) && !empty($response['test_results']));
    }

    private function normalise_response_value($value) {
        if (!is_array($value)) {
            return $value;
        }

        $normalisedarray = array();
        foreach ($value as $key => $item) {
            $normalisedarray[$key] = $this->normalise_response_value($item);
        }

        if (!$this->is_list_array($normalisedarray)) {
            ksort($normalisedarray);
        }

        return $normalisedarray;
    }

    private function is_list_array(array $array) {
        if ($array === array()) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }

    private function decode_response_json(string $value) {
        $decoded = json5_decode($value, true);

        if (!is_string($decoded)) {
            return $decoded;
        }

        $nestedcandidate = trim($decoded);
        if ($nestedcandidate === '') {
            return $decoded;
        }

        try {
            $nesteddecoded = json5_decode($nestedcandidate, true);
        } catch (TypeError | SyntaxError $error) {
            return $decoded;
        }

        return is_array($nesteddecoded) ? $nesteddecoded : $decoded;
    }

    public function analyse_response(string $testResults): array {
        try {
            return $this->analyse_response_internal($testResults);
        } catch (TypeError | SyntaxError $error) {
            return array(
                'fraction' => 0,
                'test_summary' => []
            );
        }
    }

    private function is_valid_answer_payload($answer): bool {
        return is_array($answer) && !empty($answer);
    }

    private function is_valid_test_results_payload($testresults): bool {
        return !empty($this->extract_test_case_results($testresults));
    }

    private function extract_test_case_results($testresults): array {
        if (!is_array($testresults) || empty($testresults)) {
            return array();
        }

        if (isset($testresults['testCaseResults']) && is_array($testresults['testCaseResults'])) {
            return $testresults['testCaseResults'];
        }

        if (isset($testresults[0]['testCaseResults']) && is_array($testresults[0]['testCaseResults'])) {
            return $testresults[0]['testCaseResults'];
        }

        return array();
    }
}
