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
require_once(dirname(__FILE__).'/locallib.php');

class indicator_attendance extends indicator {
    private $currweek;
    public $statuses;

    /**
     * get_risk_for_users_users
     *
     * @param mixed $userid     if userid is null, return risks for all users
     * @param mixed $courseid
     * @param mixed $startdate
     * @param mixed $enddate
     * @access protected
     * @return array            array of risk values, keyed on userid
     */
    protected function get_rawdata($startdate, $enddate) {
        global $DB;

        $attendances = array();
        $params = array('courseid'  => $this->courseid,
                        'startdate' => $startdate,
                        'enddate'   => $enddate);

        $sql = 'SELECT count(st.acronym), st.acronym, al.studentid as userid FROM {attendance_log} al
                  JOIN {attendance_statuses} st ON st.id = al.statusid
                  JOIN {attendance_sessions} asess ON asess.id = al.sessionid
                  JOIN {attendance} a ON a.id = asess.attendanceid
                 WHERE a.course = :courseid
                   AND al.timetaken > :startdate AND al.timetaken < :enddate
              GROUP BY st.acronym, studentid';
        $attrecs = $DB->get_recordset_sql($sql, $params);
        foreach ($attrecs as $att) {
            if (!isset($attendances[$att->userid])) {
                $attendances[$att->userid] = array();
            }
            $attendances[$att->userid][$att->acronym] = $att->count;
        }

        $rawdata = new stdClass();
        $rawdata->attendances = $attendances;
        return $rawdata;
    }

    protected function calculate_risks(array $userids) {
        $risks = array();
        $strmaxrisktitle = get_string('maxrisktitle', 'engagementindicator_forum');
        $strentries = get_string('entriesperweek', 'engagementindicator_attendance');

        $startweek = date('W', $this->startdate);
        $this->currweek = date('W') - $startweek + 1;
        foreach ($userids as $userid) {
            $risk = 0;
            $reasons = array();
            if (!isset($this->rawdata->attendances[$userid])) {
                // Max risk.
                $info = new stdClass();
                $info->risk = 1.0;
                $reason = new stdClass();
                $reason->weighting = '100%';
                $reason->localrisk = '100%';
                $reason->logic = "This user has never had attendance marked in the ".
                                 "course and so is at the maximum 100% risk.";
                $reason->riskcontribution = '100%';
                $reason->title = $strmaxrisktitle;
                $reason->entries = 0;
                $info->info = array($reason);
                $risks[$userid] = $info;
                continue;
            }

            foreach ($this->statuses as $status) {
                if (!isset($this->rawdata->attendances[$userid][$status->acronym])) {
                    $this->rawdata->attendances[$userid][$status->acronym] = 0;
                }
                $localrisk = $this->calculate($status->acronym, $this->rawdata->attendances[$userid][$status->acronym]);
                $riskcontribution = $localrisk * $this->config['w_'.$status->acronym];
                $reason = new stdClass();
                $reason->weighting = intval($this->config['w_'.$status->acronym]*100).'%';
                $reason->localrisk = intval($localrisk*100).'%';
                $reason->logic = "0% risk for {$this->config['no_'.$status->acronym]} entries a week. ".
                    "100% for {$this->config['max_'.$status->acronym]} entries a week.";
                $reason->riskcontribution = intval($riskcontribution*100).'%';
                $reason->title = $status->acronym. ' '.$strentries;
                $reason->entries = $this->rawdata->attendances[$userid][$status->acronym];
                $reasons[] = $reason;
                $risk += $riskcontribution;
            }

            $info = new stdClass();
            $info->risk = $risk;
            $info->info = $reasons;
            $risks[$userid] = $info;
        }

        return $risks;
    }

    protected function calculate($type, $num) {
        $risk = 0;
        $maxrisk = $this->config["max_$type"];
        $norisk = $this->config["no_$type"];
        $weight = $this->config["w_$type"];
        if ($num < $norisk || ($num == 0 && $norisk == 0)) {
            return 0;
        }
        if ($num >= $maxrisk) {
            $risk = $weight;
        } else if ($num / $this->currweek >= $maxrisk) {
            $risk = $weight;
        } else if ($num / $this->currweek > $norisk) {
            $num = $num / $this->currweek;
            $num -= $maxrisk;
            $num /= $norisk - $maxrisk;
            $risk = $num * $weight;
        }
        return $risk;
    }

    protected function load_config() {
        parent::load_config();
        $statuses = attendanceindicator_get_statuses($this->courseid);
        $this->statuses = $statuses;
        $defaults = $this->get_defaults();
        foreach ($defaults as $setting => $value) {
            if (!isset($this->config[$setting])) {
                $this->config[$setting] = $value;
            } else if (substr($setting, 0, 2) == 'w_') {
                $this->config[$setting] = $this->config[$setting] / 100;
            }
        }

    }
    public function get_defaults() {
        $settings = array();
        $statuses = $this->statuses;
        foreach ($statuses as $status) {
            // Set some default values based on default attendance statuses.
            if ($status->acronym == 'A') {
                $settings['w_'.$status->acronym] = 0.5;
                $settings['no_'.$status->acronym] = 0;
                $settings['max_'.$status->acronym] = 2;
            } else if ($status->acronym == 'L') {
                $settings['w_'.$status->acronym] = 0.35;
                $settings['no_'.$status->acronym] = 1;
                $settings['max_'.$status->acronym] = 3;
            } else if ($status->acronym == 'E') {
                $settings['w_'.$status->acronym] = 0.15;
                $settings['no_'.$status->acronym] = 4;
                $settings['max_'.$status->acronym] = 8;
            } else {
                $settings['w_'.$status->acronym] = 0;
                $settings['no_'.$status->acronym] = 0;
                $settings['max_'.$status->acronym] = 0;
            }
        }
        return $settings;
    }
}
