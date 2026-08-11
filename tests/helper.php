<?php
/**
 * Test helpers for the logiccircuit question type.
 *
 * @package    qtype_logiccircuit
 * @copyright  2025 Groupe Modulo
 * @license    CC BY-NC-SA
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Test helper class for the logic circuit question type.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_logiccircuit_test_helper extends question_test_helper {
    public function get_test_questions() {
        return array('test');
    }

    /**
     * Makes a logic circuit question.
     * @return qtype_logiccircuit_question
     */
    public function make_logiccircuit_question_test() {
		global $CFG;

        question_bank::load_question_definition_classes('logiccircuit');
        $q = new qtype_logiccircuit_question();
        test_question_maker::initialise_a_question($q);
        $q->name = 'Logic circuit question';
        $q->questiontext = 'Answer this question';
        $q->generalfeedback = 'General feedback.';
        $q->qtype = question_bank::get_qtype('logiccircuit');

        $q->initialstate = file_get_contents($CFG->dirroot . '/question/type/logiccircuit/tests/fixtures/2bit-decoder.json');
        $q->gradingmode = 0;
        $q->penaltyregime = '';

        return $q;
    }

    public function get_logiccircuit_question_form_data_test() {
		global $CFG;

        $form = new stdClass();
        $form->name = 'Logic circuit question';
        $form->questiontext = array();
        $form->questiontext['format'] = '1';
        $form->questiontext['text'] = 'Create a 2-bit decoder.';

        $form->defaultmark = 1;
        $form->generalfeedback = array();
        $form->generalfeedback['format'] = '1';
        $form->generalfeedback['text'] = 'You had to construct a 2-bit decoder.';

        $form->initialstate = file_get_contents($CFG->dirroot . '/question/type/logiccircuit/tests/fixtures/2bit-decoder.json');
        $form->gradingmode = 0;
        $form->penaltyregime = '';

        $form->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

        return $form;
    }

    function get_logiccircuit_question_data_test() {
		global $CFG;

        $q = new stdClass();
        $q->name = 'Logic circuit question';
        $q->questiontext = 'Create a 2-bit decoder.';
        $q->questiontextformat = FORMAT_HTML;
        $q->generalfeedback = 'General feedback.';
        $q->generalfeedbackformat = FORMAT_HTML;
        $q->defaultmark = 1;
        $q->qtype = 'logiccircuit';
        $q->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
        $q->createdby = '2';
        $q->modifiedby = '2';
        $q->options = new stdClass();
        $q->options->initialstate = file_get_contents($CFG->dirroot . '/question/type/logiccircuit/tests/fixtures/2bit-decoder.json');
        $q->options->gradingmode = 0;
        $q->options->penaltyregime = '';

        return $q;
    }
}
