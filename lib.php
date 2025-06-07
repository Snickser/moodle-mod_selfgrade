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

function selfgrade_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        default:
            return null;
    }
}

function selfgrade_add_instance($data) {
    global $DB;

    $data->timecreated = time();
    $data->intro = $data->intro;
    $data->introformat = $data->introformat ?? FORMAT_MOODLE;
    $data->content = $data->content_editor['text'];
    $data->answer = $data->answer_editor['text'];
    $data->contentformat = $data->content_editor['format'];
    unset($data->content_editor); // Не сохраняем редактор напрямую

    selfgrade_grade_item_update($data);

    return $DB->insert_record('selfgrade', $data);
}

function selfgrade_update_instance($data) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;
    $data->intro = $data->intro;
    $data->introformat = $data->introformat ?? FORMAT_MOODLE;
    $data->content = $data->content_editor['text'];
    $data->answer = $data->answer_editor['text'];
    $data->contentformat = $data->content_editor['format'];
    unset($data->content_editor); // Не сохраняем редактор напрямую

    selfgrade_grade_item_update($data);

    return $DB->update_record('selfgrade', $data);
}

/**
 * Удаляет экземпляр selfgrade.
 *
 * @param int $id ID записи selfgrade в таблице {selfgrade}
 * @return bool успех
 * @package mod_selfgrade
 */
function selfgrade_delete_instance($id) {
    global $DB;

    if (!$selfgrade = $DB->get_record('selfgrade', ['id' => $id])) {
        return false;
    }

    // Удаление всех отправленных ответов
    $DB->delete_records('selfgrade_submissions', ['selfgradeid' => $selfgrade->id]);

    // Удаление основной записи selfgrade
    $DB->delete_records('selfgrade', ['id' => $selfgrade->id]);

    return true;
}

function selfgrade_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    // Проверка: есть ли отправка у пользователя
    $params = [
        'selfgradeid' => $cm->instance,
        'userid' => $userid,
    ];

    return $DB->record_exists('selfgrade_submissions', $params);
}

/*
function selfgrade_get_completion_active_rule_descriptions($cm) {
    return [
        'completionSubmit' => get_string('completionSubmit', 'selfgrade'),
    ];
}
*/

function selfgrade_grade_item_update($selfgrade, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    if (!isset($selfgrade->id)) {
        return null;
    }

    $params['itemname'] = $selfgrade->name;

    if ($selfgrade->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = (float)($quiz->grade ?? 10);
        $params['grademin']  = 0;
    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update(
        'mod/selfgrade',
        $selfgrade->course,
        'mod',
        'selfgrade',
        $selfgrade->id,
        0,
        $grades,
        $params
    );
}
