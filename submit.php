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
require_once($CFG->libdir . '/gradelib.php');

require_login();
require_sesskey();

$id = required_param('id', PARAM_INT);

$studenttext = optional_param('studenttext', '', PARAM_RAW);
$grade = optional_param('grade', 0, PARAM_FLOAT);
$delete = optional_param('delete', 0, PARAM_INT);
$random = optional_param('random', 0, PARAM_INT);

$cm = get_coursemodule_from_id('selfgrade', $id, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);

$selfgrade = $DB->get_record('selfgrade', ['id' => $cm->instance], '*', MUST_EXIST);
$maxgrade = $selfgrade->grade;

if ($random) {
    $existing = $DB->get_record('selfgrade_submissions', [
    'id' => $random,
    ]);
} else {
    $existing = $DB->get_record('selfgrade_submissions', [
    'selfgradeid' => $selfgrade->id,
    'userid' => $USER->id,
    ]);
}

if ($delete && isset($existing->id)) {
    require_capability('mod/selfgrade:viewall', $context);
    $DB->delete_records('selfgrade_submissions', ['id' => $delete]);
    redirect(new moodle_url('/mod/selfgrade/viewsubmissions.php', ['id' => $cm->id]));
}

$record = new stdClass();
if ($random) {
    $record = $existing;
    $record->other = $USER->id;
} else {
    $record->selfgradeid = $selfgrade->id;
    $record->userid = $USER->id;
}
$record->timemodified = time();

if (empty($existing->text)) {
    if ($studenttext === '') {
        redirect(
            new moodle_url('/mod/selfgrade/view.php', ['id' => $cm->id]),
            "Вы должны ввести текст.",
            0,
            1
        );
    } else {
        $record->text = $studenttext;
        $record->grade = 0;
    }
}

if (!empty($existing->text)) {
    if (($grade <= 0 || $grade > $maxgrade)) {
        redirect(
            new moodle_url('/mod/selfgrade/view.php', ['id' => $cm->id]),
            "Неверная оценка. Должна быть больше 0 и не больше $maxgrade.",
            0,
            1
        );
    } else {
        $record->grade = $grade;
        $record->text = $existing->text;
    }
}

// Сохраняем (обновляем, если уже есть)
if ($existing) {
    $record->id = $existing->id;
    $DB->update_record('selfgrade_submissions', $record);

// echo serialize($record); die;

    $gradeobj = new stdClass();
    $gradeobj->userid = $USER->id;
    $gradeobj->rawgrade = $grade;
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    grade_update('mod/selfgrade', $course->id, 'mod', 'selfgrade', $selfgrade->id, 0, $gradeobj);

    redirect(
        new moodle_url('/mod/selfgrade/view.php', ['id' => $cm->id]),
        "Ваша оценка: $grade из $maxgrade.",
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
} else {
    $DB->insert_record('selfgrade_submissions', $record);

    redirect(
        new moodle_url('/mod/selfgrade/view.php', ['id' => $cm->id]),
        "Ответ сохранён.",
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}
