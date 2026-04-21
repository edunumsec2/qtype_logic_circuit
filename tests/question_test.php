<?php

namespace qtype_logiccircuit;

use question_state;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');


/**
 * Unit tests for the logic circuit question definition class.
 *
 * @package    qtype_logiccircuit
 * @copyright  2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class question_test extends \advanced_testcase {

	private string $jsonAnswerString;
	private string $correctTestResults;
	private string $semiCorrectTestResults;
	private string $incorrectTestResults;

	protected function setUp(): void {
		global $CFG;
		$this->jsonAnswerString = file_get_contents($CFG->dirroot . '/question/type/logiccircuit/tests/fixtures/2bit-decoder.json');
		$this->correctTestResults = file_get_contents($CFG->dirroot . '/question/type/logiccircuit/tests/fixtures/correct-test-results.json');
		$this->semiCorrectTestResults = file_get_contents($CFG->dirroot . '/question/type/logiccircuit/tests/fixtures/semi-correct-test-results.json');
		$this->incorrectTestResults = file_get_contents($CFG->dirroot . '/question/type/logiccircuit/tests/fixtures/incorrect-test-results.json');
	}

	public function test_is_complete_response(): void {
		$question = \test_question_maker::make_question('logiccircuit', 'test');

		$this->assertFalse($question->is_complete_response(array()));
		$this->assertFalse($question->is_complete_response(array('answer' => " ")));
		$this->assertFalse($question->is_complete_response(array('answer' => "", 'test_results' => " ")));
		$this->assertFalse($question->is_complete_response(array('answer' => '"uploaded answer"', 'test_results' => $this->correctTestResults)));
		$this->assertFalse($question->is_complete_response(array('answer' => $this->jsonAnswerString, 'test_results' => $this->jsonAnswerString)));
		$this->assertTrue($question->is_complete_response(array('answer' => $this->jsonAnswerString, 'test_results' => $this->correctTestResults)));
		$this->assertTrue($question->is_complete_response(array(
			'answer' => json_encode($this->jsonAnswerString),
			'test_results' => json_encode($this->correctTestResults),
		)));

		$incorrectJSON = substr($this->jsonAnswerString, 0, -5);
		$this->assertFalse($question->is_complete_response(array('answer' => $incorrectJSON, 'test_results' => $incorrectJSON)));
	}

	public function test_grading(): void {
		$question = \test_question_maker::make_question('logiccircuit', 'test');

		$this->assertEquals(
			array(0, question_state::$gradedwrong),
			$question->grade_response(array('answer' => $this->jsonAnswerString, 'test_results' => $this->incorrectTestResults))
		);
		$this->assertEquals(
			array(1, question_state::$gradedright),
			$question->grade_response(array('answer' => $this->jsonAnswerString, 'test_results' => $this->correctTestResults))
		);
		$this->assertEquals(
			array(0.92, question_state::$gradedpartial),
			$question->grade_response(array('answer' => $this->jsonAnswerString, 'test_results' => $this->semiCorrectTestResults))
		);
	}

	public function test_is_same_response_handles_scalar_json_answers(): void {
		$question = \test_question_maker::make_question('logiccircuit', 'test');

		$this->assertTrue($question->is_same_response(
			array('answer' => '"uploaded answer"', 'test_results' => $this->correctTestResults),
			array('answer' => '"uploaded answer"', 'test_results' => $this->incorrectTestResults)
		));

		$this->assertFalse($question->is_same_response(
			array('answer' => '"uploaded answer"', 'test_results' => $this->correctTestResults),
			array('answer' => '"different answer"', 'test_results' => $this->correctTestResults)
		));
	}

	public function test_is_same_response_handles_double_encoded_answer_json(): void {
		$question = \test_question_maker::make_question('logiccircuit', 'test');

		$this->assertTrue($question->is_same_response(
			array('answer' => json_encode($this->jsonAnswerString), 'test_results' => $this->correctTestResults),
			array('answer' => $this->jsonAnswerString, 'test_results' => $this->correctTestResults)
		));
	}

	public function test_is_same_response_ignores_object_key_order(): void {
		$question = \test_question_maker::make_question('logiccircuit', 'test');

		$this->assertTrue($question->is_same_response(
			array('answer' => '{"b": 2, "a": 1}', 'test_results' => $this->correctTestResults),
			array('answer' => '{"a": 1, "b": 2}', 'test_results' => $this->correctTestResults)
		));
	}

	public function test_grading_handles_double_encoded_test_results(): void {
		$question = \test_question_maker::make_question('logiccircuit', 'test');

		$this->assertEquals(
			array(1, question_state::$gradedright),
			$question->grade_response(array(
				'answer' => $this->jsonAnswerString,
				'test_results' => json_encode($this->correctTestResults),
			))
		);
	}

	public function test_get_question_summary(): void {
		$question = \test_question_maker::make_question('logiccircuit', 'test');
		$qsummary = $question->get_question_summary();
		$this->assertEquals($question->questiontext, $qsummary);
	}

	/*
	public function test_summarise_response(): void {
		$question = \test_question_maker::make_question('logiccircuit', 'test');

		$this->assertEquals(
			get_string('false', 'qtype_truefalse'),
			$question->summarise_response(array('answer' => '0')));

		$this->assertEquals(
			get_string('true', 'qtype_truefalse'),
			$question->summarise_response(array('answer' => '1')));
	}
	*/
}
