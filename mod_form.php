<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/selfgrade/locallib.php');

class mod_selfgrade_mod_form extends moodleform_mod {
    private function standard_editor_options() {
        return [
        'trusttext' => true,
        'subdirs' => false,
        'maxfiles' => 0,
        'maxbytes' => 0,
        'context' => isset($this->context) ? $this->context : context_system::instance(),
        ];
    }

    public function set_data($defaultvalues) {
        $decimals = isset($defaultvalues->decimalpoints) ? (int)$defaultvalues->decimalpoints : 2;
        if (isset($defaultvalues->grade)) {
            $defaultvalues->grade = format_float((float)$defaultvalues->grade, $decimals);
        }

        if (isset($defaultvalues->gradepass)) {
            $defaultvalues->gradepass = format_float((float)$defaultvalues->gradepass, $decimals);
        }

        if (!empty($defaultvalues->content)) {
            $draftid = file_get_submitted_draft_itemid('content_editor');
            $defaultvalues->content_editor = [
            'text'   => file_prepare_draft_area(
                $draftid,
                $this->context->id,
                'mod_selfgrade',
                'content',
                0,
                $this->standard_editor_options(),
                $defaultvalues->content
            ),
                'format' => $defaultvalues->contentformat,
                'itemid' => $draftid,
            ];
        }

        if (!empty($defaultvalues->answer)) {
            $draftid = file_get_submitted_draft_itemid('answer_editor');
            $defaultvalues->answer_editor = [
            'text'   => file_prepare_draft_area(
                $draftid,
                $this->context->id,
                'mod_selfgrade',
                'content',
                0,
                $this->standard_editor_options(),
                $defaultvalues->answer
            ),
                'format' => $defaultvalues->contentformat,
                'itemid' => $draftid,
            ];
        }

        parent::set_data($defaultvalues);
    }

    public function definition() {
        $mform = $this->_form;

        $config = get_config('selfgrade');

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $mform->addElement('advcheckbox', 'printintro', get_string('printintro', 'page'));

        $mform->addElement('header', 'contentsection', get_string('contentheader', 'page'));

        $mform->addElement('editor', 'content_editor', get_string('content', 'page'), null, page_get_editor_options($this->context));
        $mform->addRule('content_editor', get_string('required'), 'required', null, 'client');

        $mform->addElement('editor', 'answer_editor', get_string('answer', 'selfgrade'), null, page_get_editor_options($this->context));
        $mform->addRule('answer_editor', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'gradepass', get_string('gradepass', 'grades'), ['size' => '5']);
        $mform->setType('gradepass', PARAM_FLOAT);
        $mform->addHelpButton('gradepass', 'gradepass', 'grades');
        $mform->setDefault('gradepass', 0);

        $mform->addElement('text', 'grade', get_string('grademax', 'grades'), ['size' => '5']);
        $mform->setType('grade', PARAM_FLOAT);
        $mform->setDefault('grade', 10);

        // Overall decimal points.
        $options = [];
        for ($i = 0; $i <= 5; $i++) {
            $options[$i] = $i;
        }
        $mform->addElement(
            'select',
            'decimalpoints',
            get_string('decimalplaces', 'quiz'),
            $options
        );
        $mform->addHelpButton('decimalpoints', 'decimalplaces', 'quiz');

        $mform->addElement('advcheckbox', 'allowedit', get_string('allowedit', 'selfgrade'));

        $mform->addElement('advcheckbox', 'random', get_string('random', 'selfgrade'));
        $mform->addHelpButton('random', 'random', 'selfgrade');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
