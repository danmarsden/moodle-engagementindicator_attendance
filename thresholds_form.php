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
 * This file defines a class with attendance indicator logic
 *
 * @package    engagementindicator_attendance
 * @author     Dan Marsden <dan@danmarsden.com>
 * @copyright  2013 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../indicator.class.php');
require_once(dirname(__FILE__).'/indicator.class.php');
require_once(dirname(__FILE__).'/locallib.php');

class engagementindicator_attendance_thresholds_form {

    /**
     * Define the elements to be displayed in the form
     *
     * @param $mform
     * @access public
     * @return void
     */
    public function definition_inner(&$mform) {

        $strmaxrisk = get_string('maxrisk', 'engagementindicator_forum');
        $strnorisk = get_string('norisk', 'engagementindicator_forum');
        $courseid = optional_param('id', 0, PARAM_INT);

        $statuses = attendanceindicator_get_statuses($courseid);

        foreach ($statuses as $status) {
            $element = $status->acronym;

            $grouparray = array();
            $grouparray[] =& $mform->createElement('static', '', '', "&nbsp;$strnorisk");
            $grouparray[] =& $mform->createElement('text', "attendance_no_$element", '', array('size' => 5));
            $mform->setType("attendance_no_$element", PARAM_FLOAT);
            $mform->setDefault("attendance_no_$element", 0);

            $grouparray[] =& $mform->createElement('static', '', '', $strmaxrisk);
            $grouparray[] =& $mform->createElement('text', "attendance_max_$element", '', array('size' => 5));
            $mform->setType("attendance_max_$element", PARAM_FLOAT);
            $mform->setDefault("attendance_max_$element", 0);

            $grouparray[] =& $mform->createElement('static', '', '', get_string('weighting', 'report_engagement'));
            $grouparray[] =& $mform->createElement('text', "attendance_w_$element", '', array('size' => 3));
            $mform->setType("attendance_w_$element", PARAM_FLOAT);
            $mform->setDefault("attendance_w_$element", 0);

            $grouparray[] =& $mform->createElement('static', '', '', '%');
            $mform->addGroup($grouparray, "group_attendance_$element", $status->description, '&nbsp;',
                false);
        }
    }
}
