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

require_once($CFG->dirroot . '/mod/selfgrade/backup/moodle2/backup_selfgrade_stepslib.php');

class backup_selfgrade_activity_task extends backup_activity_task {
    protected function define_my_settings() {
        // No special settings for this activity.
    }

    protected function define_my_steps() {
        $this->add_step(new backup_selfgrade_activity_structure_step('selfgrade_structure', 'selfgrade.xml'));
    }

    public static function encode_content_links($content) {
        global $CFG;
        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of selfgrades.
        // $content = preg_replace("/(" . $base . "\/mod\/selfgrade\/index.php\?id=)([0-9]+)/", '$@SELFGRADEINDEX*$2@$', $content);

        // Link to selfgrade view by moduleid.
        $content = preg_replace("/(" . $base . "\/mod\/selfgrade\/view.php\?id=)([0-9]+)/", '$@SELFGRADEVIEWBYID*$2@$', $content);

        return $content;
    }
}
