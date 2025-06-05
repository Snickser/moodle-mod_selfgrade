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

$id = required_param('id', PARAM_INT); // Course module ID
$cm = get_coursemodule_from_id('selfgrade', $id, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, false, $cm);
require_capability('mod/selfgrade:viewall', $context);

$groupid = optional_param('group', 0, PARAM_INT); // выбранная группа, 0 — все

$PAGE->set_url('/mod/selfgrade/viewsubmissions.php', ['id' => $id, 'group' => $groupid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('viewsubmissions', 'selfgrade'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

// Выведем меню выбора групп
groups_print_activity_menu($cm, $PAGE->url);

// Формируем SQL с фильтром по группе, если выбран
$sql = "SELECT s.*, u.firstname, u.lastname, g.name AS groupname
        FROM {selfgrade_submissions} s
        JOIN {user} u ON s.userid = u.id
        LEFT JOIN {groups_members} gm ON gm.userid = u.id
        LEFT JOIN {groups} g ON g.id = gm.groupid AND g.courseid = :courseid
        WHERE s.selfgradeid = :sid";

$params = [
    'sid' => $cm->instance,
    'courseid' => $course->id,
];

if ($groupid) {
    $sql .= " AND g.id = :groupid";
    $params['groupid'] = $groupid;
}

$sql .= " ORDER BY s.timemodified DESC";

$submissions = $DB->get_records_sql($sql, $params);

if ($submissions) {
    $table = new html_table();
    $table->head = [
        get_string('fullname'),
        get_string('group'),
        get_string('gradeverb'),
        get_string('text', 'selfgrade'),
        get_string('date'),
        '&nbsp;',
    ];
    $table->attributes = ['class' => 'generaltable mod_selfgrade_submissions'];
    $table->data = [];

    foreach ($submissions as $s) {
        $s->firstnamephonetic = $s->firstnamephonetic ?? '';
        $s->lastnamephonetic = $s->lastnamephonetic ?? '';
        $s->middlename = $s->middlename ?? '';
        $s->alternatename = $s->alternatename ?? '';

        $url = new moodle_url('/mod/selfgrade/submit.php');

            $button = '<form name="delete' . $s->id . '" method="POST" action="' . $url . '">
                <input type="hidden" name="sesskey" value="' . sesskey() . '">
                <input type="hidden" name="id" value="' . $id . '">
                <input type="hidden" name="delete" value="' . $s->id . '">
                <button type="submit" class="btn btn-danger" data-modal="confirmation"
                    data-modal-title-str=\'["delete", "core"]\' data-modal-content-str=\'["areyousure"]\'
                    data-modal-yes-button-str=\'["confirm", "core"]\'">' . get_string('delete') . '</button>
            </form>';

        $table->data[] = [
            fullname($s),
            $s->groupname ?? '-',
            format_float($s->grade, 2),
            html_writer::tag('div', format_text($s->text), [
                'style' => 'max-height:100px; overflow:auto; padding:4px; border:1px solid #ccc; background:#f9f9f9; font-size:0.9em;',
            ]),
            html_writer::tag('div', userdate($s->timemodified, '%Y.%m.%d %H:%M'), ['style' => 'white-space: nowrap;']),
        $button,
        ];
    }

    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('nosubmissions', 'selfgrade'), 'info');
}

echo $OUTPUT->footer();
