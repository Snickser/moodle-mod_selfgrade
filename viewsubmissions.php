<?php
require('../../config.php');

$id = required_param('id', PARAM_INT); // Course module ID
$cm = get_coursemodule_from_id('selfgrade', $id, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, false, $cm);
require_capability('mod/selfgrade:viewall', $context); // Добавим в access.php

$PAGE->set_url('/mod/selfgrade/viewsubmissions.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_title('Ответы студентов');
$PAGE->set_heading('Ответы студентов');

echo $OUTPUT->header();

$selfgrade = $DB->get_record('selfgrade', ['id' => $cm->instance], '*', MUST_EXIST);

$sql = "SELECT s.*, u.firstname, u.lastname
        FROM {selfgrade_submissions} s
        JOIN {user} u ON s.userid = u.id
        WHERE s.selfgradeid = :sid
        ORDER BY s.timemodified DESC";
$submissions = $DB->get_records_sql($sql, ['sid' => $selfgrade->id]);

if ($submissions) {
    echo html_writer::start_tag('table', ['class' => 'generaltable']);
    echo html_writer::tag('tr',
        html_writer::tag('th', 'ФИО') .
        html_writer::tag('th', 'Оценка') .
        html_writer::tag('th', 'Текст') .
        html_writer::tag('th', 'Дата'));

    foreach ($submissions as $s) {
        echo html_writer::tag('tr',
            html_writer::tag('td', fullname($s)) .
            html_writer::tag('td', $s->grade) .
            html_writer::tag('td', format_text($s->text)) .
            html_writer::tag('td', userdate($s->timemodified))
        );
    }

    echo html_writer::end_tag('table');
} else {
    echo "Нет отправленных ответов.";
}

echo $OUTPUT->footer();
