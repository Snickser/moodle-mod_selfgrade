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

class backup_selfgrade_activity_structure_step extends backup_activity_structure_step {
    protected function define_structure() {
        $selfgrade = new backup_nested_element('selfgrade', ['id'], [
            'name', 'intro', 'grade', 'gradepass', 'decimalpoints', 'allowedit',
            'printintro', 'content', 'answer', 'random', 'timemodified']);

        $selfgrade->set_source_table('selfgrade', ['id' => backup::VAR_ACTIVITYID]);

        // Define file annotations
        $selfgrade->annotate_files('mod_selfgrade', 'intro', null); // This file areas haven't itemid
        $selfgrade->annotate_files('mod_selfgrade', 'content', null); // This file areas haven't itemid

        return $this->prepare_activity_structure($selfgrade);
    }
}
