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

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('selfgrade', $id, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);

$selfgrade = $DB->get_record('selfgrade', ['id' => $cm->instance], '*', MUST_EXIST);
$maxgrade = $selfgrade->grade;

$studenttext = required_param('studenttext', PARAM_RAW);
$grade = required_param('grade', PARAM_FLOAT);

if ($grade <= 0 || $grade > $maxgrade) {
    redirect(
        new moodle_url('/mod/selfgrade/view.php', ['id' => $cm->id]),
        "Неверная оценка. Должна быть больше 0 и не больше $maxgrade.",
        0,
        1
    );
}

if ($studenttext === '') {
    redirect(
        new moodle_url('/mod/selfgrade/view.php', ['id' => $cm->id]),
        "Вы должны ввести текст.",
        0,
        1
    );
}

$record = new stdClass();
$record->selfgradeid = $selfgrade->id;
$record->userid = $USER->id;
$record->text = $studenttext;
$record->grade = $grade;
$record->timemodified = time();

// Сохраняем (обновляем, если уже есть)
$existing = $DB->get_record('selfgrade_submissions', [
    'selfgradeid' => $selfgrade->id,
    'userid' => $USER->id,
]);

if ($existing) {
    $record->id = $existing->id;
    $DB->update_record('selfgrade_submissions', $record);

    $gradeobj = new stdClass();
    $gradeobj->userid = $USER->id;
    $gradeobj->rawgrade = $grade;
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    grade_update('mod/selfgrade', $course->id, 'mod', 'selfgrade', $selfgrade->id, 0, $gradeobj);
} else {
    $DB->insert_record('selfgrade_submissions', $record);
}

redirect(
    new moodle_url('/mod/selfgrade/view.php', ['id' => $cm->id]),
    "Ответ сохранён. Ваша оценка: $grade из $maxgrade.",
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
