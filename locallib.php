<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file defines functions used for the attendance indicator
 *
 * @package    engagementindicator_attendance
 * @author     Dan Marsden <dan@danmarsden.com>
 * @copyright  2013 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Process the edit form data, returning an array of config settings to store
 *
 * @param array $data
 * @return array
 */
function engagementindicator_attendance_process_edit_form($data) {
    $configdata = array();
    $elements = attendanceindicator_get_statuses($data->id);

    foreach ($elements as $element => $unused) {
        if (isset($data->{"attendance_no_$element"})) {
            $configdata["attendance_no_$element"] = $data->{"attendance_no_$element"};
        }
        if (isset($data->{"attendance_max_$element"})) {
            $configdata["attendance_max_$element"] = $data->{"attendance_max_$element"};
        }
        if (isset($data->{"attendance_w_$element"})) {
            $configdata["attendance_w_$element"] = $data->{"attendance_w_$element"};
        }
    }

    return $configdata;
}

function attendanceindicator_get_statuses($courseid) {
    global $DB;
    $sql = 'SELECT asess.acronym, asess.description, asess.grade FROM {attendance_statuses} asess
                 JOIN {attendance} a ON a.id = asess.attendanceid
                WHERE a.course = ?
             ORDER BY asess.grade ASC';
    $statuses = $DB->get_records_sql($sql, array($courseid));
    return $statuses;
}
