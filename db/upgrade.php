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

defined('MOODLE_INTERNAL') || die();

function xmldb_selfgrade_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Пример: добавление поля gradepass
    if ($oldversion < 2025060401) {
        // Добавляем поле gradepass в таблицу selfgrade
        $table = new xmldb_table('selfgrade');
        $field = new xmldb_field('gradepass', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, 0, 'grade');

        // Добавляем поле, если его ещё нет
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Обновляем версию плагина
        upgrade_mod_savepoint(true, 2025060401, 'selfgrade');
    }

    // Добавляем поле introformat в таблицу selfgrade (версия 2025060402 — пример)
    if ($oldversion < 2025060403) {
        $table = new xmldb_table('selfgrade');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, null, null, null, null, null, 'gradepass');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('selfgrade');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1', 'intro');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2025060403, 'selfgrade');
    }

    if ($oldversion < 2025060405) {
        $table = new xmldb_table('selfgrade');

        $content = new xmldb_field('content', XMLDB_TYPE_TEXT, null, null, null, null, null, 'introformat');
        if (!$dbman->field_exists($table, $content)) {
            $dbman->add_field($table, $content);
        }

        $contentformat = new xmldb_field('contentformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, 1, 'content');
        if (!$dbman->field_exists($table, $contentformat)) {
            $dbman->add_field($table, $contentformat);
        }

        upgrade_mod_savepoint(true, 2025060405, 'selfgrade');
    }

    if ($oldversion < 2025060407) {
        $table = new xmldb_table('selfgrade');
        $field = new xmldb_field('printintro', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'introformat');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2025060407, 'selfgrade');
    }

    if ($oldversion < 2025060408) {
        $table = new xmldb_table('selfgrade');

        $content = new xmldb_field('answer', XMLDB_TYPE_TEXT, null, null, null, null, null, 'content');
        if (!$dbman->field_exists($table, $content)) {
            $dbman->add_field($table, $content);
        }

        upgrade_mod_savepoint(true, 2025060408, 'selfgrade');
    }

    if ($oldversion < 2025060509) {
        $table = new xmldb_table('selfgrade');
        $field = new xmldb_field('random', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'answer');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2025060509, 'selfgrade');
    }

    if ($oldversion < 2025060511) {
        $table = new xmldb_table('selfgrade_submissions');
        $field = new xmldb_field('other', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'grade');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2025060511, 'selfgrade');
    }

    if ($oldversion < 2025060612) {
        $table = new xmldb_table('selfgrade');
        $field = new xmldb_field('decimalpoints', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'gradepass');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2025060612, 'selfgrade');
    }

    if ($oldversion < 2025060613) {
        $table = new xmldb_table('selfgrade');
        $field = new xmldb_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2025060613, 'selfgrade');
    }

    if ($oldversion < 2025060816) {
        $table = new xmldb_table('selfgrade');
        $field = new xmldb_field('allowedit', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'decimalpoints');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2025060816, 'selfgrade');
    }

    return true;
}
