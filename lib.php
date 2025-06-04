<?php

function selfgrade_supports($feature) {
    switch ($feature) {
        case FEATURE_GRADE_HAS_GRADE: return true;
        case FEATURE_MOD_ARCHETYPE: return MOD_ARCHETYPE_OTHER;
        default: return null;
    }
}

function selfgrade_add_instance($data) {
    global $DB;

    $data->timecreated = time();
    return $DB->insert_record('selfgrade', $data);
}

function selfgrade_update_instance($data) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;
    return $DB->update_record('selfgrade', $data);
}

function selfgrade_delete_instance($id) {
    global $DB;

    $DB->delete_records('selfgrade_submissions', ['selfgradeid' => $id]);
    return $DB->delete_records('selfgrade', ['id' => $id]);
}

