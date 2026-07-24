<?php

namespace qtype_logiccircuit;

use qtype_logiccircuit;
use qtype_logiccircuit_edit_form;
use qtype_logiccircuit_renderer;
use question_bank;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/logiccircuit/questiontype.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/edit_question_form.php');
require_once($CFG->dirroot . '/question/type/logiccircuit/edit_logiccircuit_form.php');
require_once($CFG->dirroot . '/question/type/logiccircuit/renderer.php');

/**
 * Unit tests for the logic circuit question definition class.
 *
 * @package    qtype_logiccircuit
 * @copyright  2007 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class question_type_test extends \advanced_testcase {
    protected $qtype;

    protected function setUp(): void {
        parent::setUp();
        $this->qtype = new qtype_logiccircuit();
    }

    protected function tearDown(): void {
        $this->qtype = null;
        parent::tearDown();
    }

    public function test_name(): void {
        $this->assertEquals($this->qtype->name(), 'logiccircuit');
    }

    public function test_can_analyse_responses(): void {
        $this->assertTrue($this->qtype->can_analyse_responses());
    }

    public function test_load_question(): void {
        global $CFG;

        $this->resetAfterTest();

        $syscontext = \context_system::instance();
        /** @var core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category(['contextid' => $syscontext->id]);

        $fromform = \test_question_maker::get_question_form_data('logiccircuit');
        $fromform->category = $category->id . ',' . $syscontext->id;

        $question = new \stdClass();
        $question->category = $category->id;
        $question->qtype = 'logiccircuit';
        $question->createdby = 0;

        $this->qtype->save_question($question, $fromform);
        $questiondata = question_bank::load_question_data($question->id);

        $this->assertEquals(['id', 'category', 'parent', 'name', 'questiontext', 'questiontextformat',
                'generalfeedback', 'generalfeedbackformat', 'defaultmark', 'penalty', 'qtype',
                'length', 'stamp', 'timecreated', 'timemodified', 'createdby', 'modifiedby', 'idnumber', 'contextid',
                'status', 'versionid', 'version', 'questionbankentryid', 'categoryobject', 'options', 'hints'],
                array_keys(get_object_vars($questiondata)));
        $this->assertEquals($category->id, $questiondata->category);
        $this->assertEquals(0, $questiondata->parent);
        $this->assertEquals($fromform->name, $questiondata->name);
        $this->assertEquals($fromform->questiontext, $questiondata->questiontext);
        $this->assertEquals($fromform->questiontextformat, $questiondata->questiontextformat);
        $this->assertEquals($fromform->generalfeedback['text'], $questiondata->generalfeedback);
        $this->assertEquals($fromform->generalfeedback['format'], $questiondata->generalfeedbackformat);
        $this->assertEquals($fromform->defaultmark, $questiondata->defaultmark);
        $this->assertEquals('logiccircuit', $questiondata->qtype);
        $this->assertEquals(\core_question\local\bank\question_version_status::QUESTION_STATUS_READY, $questiondata->status);
        $this->assertEquals($question->createdby, $questiondata->createdby);
        $this->assertEquals($question->createdby, $questiondata->modifiedby);
        $this->assertEquals('', $questiondata->idnumber);
        $this->assertEquals($category->contextid, $questiondata->contextid);

        // Options.
		$jsonAnswerString = file_get_contents($CFG->dirroot . '/question/type/logiccircuit/tests/fixtures/2bit-decoder.json');
        $this->assertEquals($jsonAnswerString, $questiondata->options->initialstate);
        $this->assertEquals('', $questiondata->options->penaltyregime);

        // Hints.
        $this->assertEquals([], $questiondata->hints);
    }

    public function test_save_question(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $questiondata = \test_question_maker::get_question_data('logiccircuit');
        $formdata = \test_question_maker::get_question_form_data('logiccircuit');

        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category(array());

        $formdata->category = "{$cat->id},{$cat->contextid}";
        qtype_logiccircuit_edit_form::mock_submit((array)$formdata);

        $form = \qtype_logiccircuit_test_helper::get_question_editing_form($cat, $questiondata);

        $this->assertTrue($form->is_validated());

        $fromform = $form->get_data();

        $returnedfromsave = $this->qtype->save_question($questiondata, $fromform);
        $actualquestionsdata = question_load_questions([$returnedfromsave->id], 'qbe.idnumber');
        $actualquestiondata = end($actualquestionsdata);

        foreach ($questiondata as $property => $value) {
            if (!in_array($property, array('options'))) {
                $this->assertEquals($value, $actualquestiondata->$property);
            }
        }

        $this->assertEquals($questiondata->options->initialstate, $actualquestiondata->options->initialstate);
        $this->assertEquals($questiondata->options->penaltyregime, $actualquestiondata->options->penaltyregime);
    }

    public function test_penalty_regime_validation_accepts_valid_values(): void {
        $method = $this->get_penalty_regime_validation_method();
        $form = (new \ReflectionClass(qtype_logiccircuit_edit_form::class))->newInstanceWithoutConstructor();

        foreach (['10', '10%', '10, 20, ...'] as $penaltyregime) {
            $this->assertSame('', $method->invoke($form, $penaltyregime));
        }
    }

    public function test_penalty_regime_validation_rejects_invalid_values(): void {
        $method = $this->get_penalty_regime_validation_method();
        $form = (new \ReflectionClass(qtype_logiccircuit_edit_form::class))->newInstanceWithoutConstructor();

        foreach (['...', '10, ...', '20, 10, ...', '10,,20', 'one'] as $penaltyregime) {
            $this->assertNotSame('', $method->invoke($form, $penaltyregime));
        }
    }

    public function test_renderer_normalises_double_encoded_answer_values(): void {
        global $CFG;

        $jsonanswerstring = file_get_contents($CFG->dirroot . '/question/type/logiccircuit/tests/fixtures/2bit-decoder.json');

        $this->assertEquals(
            trim($jsonanswerstring),
            qtype_logiccircuit_renderer::normalise_json_value_for_editor(json_encode($jsonanswerstring))
        );
    }

    private function get_penalty_regime_validation_method(): \ReflectionMethod {
        $method = new \ReflectionMethod(qtype_logiccircuit_edit_form::class, 'validate_penalty_regime');
        $method->setAccessible(true);

        return $method;
    }
}
