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

$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

$selfgrade = $DB->get_record('selfgrade', ['id' => $cm->instance], '*', MUST_EXIST);

$PAGE->set_url('/mod/selfgrade/view.php', ['id' => $id]);

$PAGE->set_context($context);
$PAGE->set_title($course->shortname . ': ' . $selfgrade->name);
$PAGE->set_heading($course->fullname);
$PAGE->add_body_class('limitedwidth');

$activityheader = ['hidecompletion' => false];
if (empty($selfgrade->printintro)) {
    $activityheader['description'] = '';
}
$PAGE->activityheader->set_attrs($activityheader);

$submission = $DB->get_record('selfgrade_submissions', [
    'selfgradeid' => $selfgrade->id,
    'userid' => $USER->id,
]);
$answer = $selfgrade ? $selfgrade->answer : '';
$oldtext = $submission ? $submission->text : '';
$oldgrade = $submission ? $submission->grade : '';

$maxgrade = $selfgrade->grade;

echo $OUTPUT->header();

if (has_capability('mod/selfgrade:viewall', $context) || is_siteadmin()) {
    $groups = groups_get_all_groups($course->id);

    // $usergroupsdata = groups_get_user_groups($course->id, $USER->id);
    // $usergroups = isset($usergroupsdata[1]) ? $usergroupsdata[1] : [];

    if (!empty($groups)) {
        echo html_writer::start_tag('form', ['method' => 'get', 'action' => 'viewsubmissions.php', 'class' => 'mb-3']);

        // Скрытое поле с id модуля
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);

        // label и select
        echo html_writer::label('Выберите группу для просмотра ответов:&nbsp;', 'groupid', false, ['class' => 'form-label']);
        echo html_writer::start_tag('select', ['name' => 'group', 'id' => 'groupid', 'class' => 'form-select', 'style' => 'max-width:300px;']);

        echo html_writer::tag('option', '', ['value' => '']);
        echo html_writer::tag('option', 'Все группы', ['value' => 0]);

        foreach ($groups as $group) {
            echo html_writer::tag('option', format_string($group->name), ['value' => $group->id]);
        }

        echo html_writer::end_tag('select');

        echo html_writer::end_tag('form');

        // JS для автоматического редиректа при выборе группы
        echo html_writer::script("
            document.getElementById('groupid').addEventListener('change', function() {
                var group = this.value;
                var url = new URL(window.location.origin + '/mod/selfgrade/viewsubmissions.php');
                url.searchParams.set('id', '{$cm->id}');
                if (group) {
                    url.searchParams.set('group', group);
                }
                window.location.href = url.toString();
            });
        ");
    } else {
        // Если групп нет — просто ссылка
        $url = new moodle_url('/mod/selfgrade/viewsubmissions.php', ['id' => $cm->id]);
        echo html_writer::link($url, 'Посмотреть ответы студентов');
        echo html_writer::empty_tag('p');
    }
}

$other = null;

if (empty($submission->text)) {
    echo $OUTPUT->box(format_text($selfgrade->content, $selfgrade->contentformat, ['context' => $context]), 'generalbox');

    echo html_writer::start_tag('form', ['method' => 'post', 'action' => 'submit.php']);

    // textarea с классом form-control
    echo html_writer::tag('textarea', s($oldtext), [
    'name' => 'studenttext',
    'id' => 'studenttext',
    'rows' => 10,
    'cols' => 80,
    'class' => 'form-control',
    ]);

    echo html_writer::empty_tag('p');

    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
/*
    // кнопка submit с классом btn btn-primary (стандарт Bootstrap в Moodle)
    echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => 'Отправить',
    'class' => 'btn btn-primary mt-2',
    ]);
*/
    echo '<button type="submit" class="mt-2 btn btn-primary" data-modal="confirmation"
        data-modal-title-str=\'["submit", "core"]\' data-modal-content-str=\'["areyousure"]\'
        data-modal-yes-button-str=\'["confirm", "core"]\'">' . get_string('submit') . '</button>';

    echo html_writer::end_tag('form');
} else if ($submission->grade == 0) {
    if ($selfgrade->random) {
        // Получить список ID всех чужих неоценённых ответов.
        $othersql = "SELECT id
    	    FROM {selfgrade_submissions}
    	    WHERE selfgradeid = :selfgradeid
    		AND grade = 0
        	AND userid <> :userid";
        $otherparams = [
            'selfgradeid' => $selfgrade->id,
            'userid' => $USER->id,
        ];
        $ids = $DB->get_fieldset_sql($othersql, $otherparams);

        if (!empty($ids)) {
            // Случайный выбор ID через PHP (кросс-СУБД)
            $randomid = $ids[array_rand($ids)];

            // Получаем полную запись
            $other = $DB->get_record('selfgrade_submissions', ['id' => $randomid]);
        }

        if ($other) {
            echo html_writer::tag('h5', 'Ответ другого студента', ['class' => 'mt-4']);
            echo html_writer::tag('div', format_text($other->text), [
            'style' => 'overflow:auto; padding:4px; border:1px dashed #999; background:#f0f0f0; font-size:0.9em;',
            'class' => 'mb-3',
            ]);
        } else {
            echo html_writer::tag('h5', "Пока нет ответов на проверку, зайдите позже.", ['class' => 'mt-4']);

            echo format_text("Ваш ответ", FORMAT_HTML);

            echo    html_writer::tag('div', format_text($oldtext, FORMAT_HTML), [
            'style' => 'overflow:auto; padding:4px; border:1px solid #ccc; background:#f9f9f9; font-size:0.9em;',
            ]);

            echo "<br>";
            echo format_text("Правильный ответ", FORMAT_HTML);

            echo html_writer::tag('div', format_text($answer, FORMAT_HTML, ['context' => $context, 'noclean' => true, 'filter' => true]), [
            'style' => 'overflow:auto; padding:4px; border:1px solid #ccc; background:#f9f9f9; font-size:0.9em;',
            ]);

            echo "<br>Оценка: " . $submission->grade;
        }
    } else {
        echo format_text("Ваш ответ", FORMAT_HTML);
        echo html_writer::tag('div', format_text($oldtext), [
        'style' => 'overflow:auto; padding:4px; border:1px solid #ccc; background:#f9f9f9; font-size:0.9em;',
            ]);
    }

    echo "<br>";

    if ($other || !$selfgrade->random) {
        echo format_text("Правильный ответ", FORMAT_HTML);

        echo html_writer::tag('div', format_text($answer, FORMAT_HTML, ['context' => $context, 'noclean' => true, 'filter' => true]), [
        'style' => 'overflow:auto; padding:4px; border:1px solid #ccc; background:#f9f9f9; font-size:0.9em;',
            ]);

        echo html_writer::empty_tag('p');

        echo html_writer::start_tag('form', ['method' => 'post', 'action' => 'submit.php']);

        echo html_writer::start_tag('div', ['class' => 'd-flex align-items-center mb-3']);

        echo html_writer::tag('label', 'Оцените ответ (максимальный балл ' . $maxgrade . '):&nbsp;', [
           'for' => 'grade',
           'class' => 'form-label mt-2 me-2', // me-2 = margin-end (правый отступ)
           ]);

        echo html_writer::empty_tag('input', [
           'type' => 'number',
           'name' => 'grade',
           'id' => 'grade',
           'min' => 0,
           'max' => $maxgrade,
           'step' => '1',
           'value' => $oldgrade,
           'class' => 'form-control',
           'style' => 'width: 100px;', // ограничим ширину поля, чтобы не растягивалось
           ]);

        echo html_writer::end_tag('div');

        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);

        if ($other) {
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'random', 'value' => $other->id]);
        }
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

        // кнопка submit с классом btn btn-primary (стандарт Bootstrap в Moodle)
        echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => 'Отправить',
        'class' => 'btn btn-primary mt-2',
        ]);


        echo html_writer::end_tag('form');
    }
} else {
    echo format_text("Ваш ответ", FORMAT_HTML);

    echo    html_writer::tag('div', format_text($oldtext, FORMAT_HTML), [
        'style' => 'overflow:auto; padding:4px; border:1px solid #ccc; background:#f9f9f9; font-size:0.9em;',
            ]);

    echo "<br>";

    echo format_text("Правильный ответ", FORMAT_HTML);

    echo html_writer::tag('div', format_text($answer, FORMAT_HTML, ['context' => $context, 'noclean' => true, 'filter' => true]), [
        'style' => 'overflow:auto; padding:4px; border:1px solid #ccc; background:#f9f9f9; font-size:0.9em;',
            ]);

    echo "<br>Оценка: " . $submission->grade;
}


echo $OUTPUT->footer();
