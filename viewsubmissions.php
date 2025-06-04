<?php
require('../../config.php');

$id = required_param('id', PARAM_INT); // Course module ID
$cm = get_coursemodule_from_id('selfgrade', $id, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, false, $cm);
require_capability('mod/selfgrade:viewall', $context);

$PAGE->set_url('/mod/selfgrade/viewsubmissions.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('viewsubmissions', 'selfgrade'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

$selfgrade = $DB->get_record('selfgrade', ['id' => $cm->instance], '*', MUST_EXIST);

$sql = "SELECT s.*, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
        FROM {selfgrade_submissions} s
        JOIN {user} u ON s.userid = u.id
        WHERE s.selfgradeid = :sid
        ORDER BY s.timemodified DESC";
$submissions = $DB->get_records_sql($sql, ['sid' => $selfgrade->id]);

if ($submissions) {
    $table = new html_table();
    $table->head = [
        get_string('fullname'),
        get_string('grade', 'grades'),
        get_string('text', 'selfgrade'),
        get_string('date')
    ];
    $table->attributes = ['class' => 'generaltable mod_selfgrade_submissions'];
    $table->data = [];

    foreach ($submissions as $s) {
        // Обеспечиваем наличие всех нужных полей для fullname()
        $s->firstnamephonetic = $s->firstnamephonetic ?? '';
        $s->lastnamephonetic = $s->lastnamephonetic ?? '';
        $s->middlename = $s->middlename ?? '';
        $s->alternatename = $s->alternatename ?? '';

        $table->data[] = [
            fullname($s),
            format_float($s->grade, 2),
            format_text($s->text, FORMAT_HTML),
            userdate($s->timemodified)
        ];
    }

    echo html_writer::table($table);

} else {
    echo $OUTPUT->notification(get_string('nosubmissions', 'selfgrade'), 'info');
}

echo $OUTPUT->footer();
