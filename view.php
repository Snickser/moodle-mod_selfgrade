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

require('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('selfgrade', $id, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($cm->course, false, $cm);

$PAGE->set_url('/mod/selfgrade/view.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_title("Self Grading");
$PAGE->set_heading("Self Grading");
$PAGE->add_body_class('limitedwidth');

$selfgrade = $DB->get_record('selfgrade', ['id' => $cm->instance], '*', MUST_EXIST);
$maxgrade = $selfgrade->grade;

$submission = $DB->get_record('selfgrade_submissions', [
    'selfgradeid' => $selfgrade->id,
    'userid' => $USER->id,
]);

$oldtext = $submission ? $submission->text : '';
$oldgrade = $submission ? $submission->grade : '';

echo $OUTPUT->header();


if (has_capability('mod/selfgrade:viewall', $context)) {
    $url = new moodle_url('/mod/selfgrade/viewsubmissions.php', ['id' => $cm->id]);
    echo html_writer::link($url, 'Посмотреть ответы студентов');
    echo html_writer::empty_tag('p');
}

echo $OUTPUT->box(format_text($selfgrade->content, $selfgrade->contentformat, ['context' => $context]), 'generalbox');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => 'submit.php']);
echo html_writer::tag('textarea', s($oldtext), [
    'name' => 'studenttext',
    'id' => 'studenttext',
    'rows' => 10,
    'cols' => 80,
]);
echo html_writer::empty_tag('p');
echo html_writer::label('Оценка (от 0 до ' . $maxgrade . '):&nbsp;', 'grade');
echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'grade', 'min' => 0, 'max' => $maxgrade, 'step' => '1', 'value' => $oldgrade]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => 'Отправить']);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
