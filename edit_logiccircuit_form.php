<?php

/**
 * Defines the editing form for the logic circuit question type.
 *
 * @package    qtype_logiccircuit
 * @copyright  2025 Groupe Modulo
 * @license    CC BY-NC-SA
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/edit_question_form.php');
require_once($CFG->dirroot . '/question/type/logiccircuit/vendor/autoload.php');
use ColinODell\Json5\SyntaxError;
use \core\url;

/**
 * Logic circuit question editing form definition.
 *
 */
class qtype_logiccircuit_edit_form extends question_edit_form {

    /**
     * Add logic circuit specific form fields.
     *
     * @param object $mform the form being built.
     */
    protected function definition_inner($mform) {
        global $PAGE;

        $PAGE->requires->js(new url('https://logic.modulo-info.ch/simulator/lib/bundle.js'));

        // TODO this is a quick hack to make the editor full width
        $mform->addElement('html', '<style>div.form-control-static[data-name=initialstate_editor] { width: 100%;}</style>');

        $mform->addElement(
            'static',
            'initialstate_editor',
            get_string('initialstate', 'qtype_logiccircuit'),
            '<div style="width: 100%; height: 600px"><logic-editor linkedto="[data-logicid=moodlefield]" mode="full" norestore></logic-editor></div>'
        );

        $mform->addElement(
            'textarea',
            'initialstate',
            get_string('initialstatejson', 'qtype_logiccircuit'),
            ['wrap' => 'virtual', 'rows' => 20, 'cols' => 50,
             "style" => "font-family:monospace;font-size:80%;",
             'data-logicid' => 'moodlefield']
        );

        $mform->addHelpButton('initialstate', 'initialstatejson', 'qtype_logiccircuit');
        $mform->addHelpButton('initialstate_editor', 'initialstate', 'qtype_logiccircuit');

        $mform->setType('initialstate', PARAM_RAW);

        $mode_dropdown_options = [
            0 => get_string('option_complete', 'qtype_logiccircuit'),
            1 => get_string('option_connect', 'qtype_logiccircuit')
        ];

        $mform->addElement(
            'select',
            'editormode',
            get_string('mode_dropdown_label', 'qtype_logiccircuit'),
            $mode_dropdown_options
        );
        $mform->setDefault('editormode', 0);
        $mform->addRule('editormode', null, 'required', null, 'client');
        $mform->addHelpButton('editormode', 'mode_dropdown', 'qtype_logiccircuit');
        $mform->setType('editormode', PARAM_INT);

        $mform->addElement(
            'text',
            'componentstoshow',
            get_string('componentstoshow_label', 'qtype_logiccircuit')
        );
        $mform->disabledIf('componentstoshow', 'editormode', 'eq', 1);
        $mform->addHelpButton('componentstoshow', 'componentstoshow_text_field', 'qtype_logiccircuit');
        $mform->setType('componentstoshow', PARAM_TEXT);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $initState = $data['initialstate'];

        if (empty($initState)) {
            $errors['initialstate'] = get_string('initial_state_empty', 'qtype_logiccircuit');
        }

        try {
            json5_decode($initState);
        } catch (TypeError | SyntaxError) {
            $errors['initialstate'] = get_string('not_valid_json', 'qtype_logiccircuit');
        }

        return $errors;
    }

    public function qtype() {
        return 'logiccircuit';
    }
}
