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

class indicator_attendance extends indicator {
    private $currweek;

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
        $sql = 'SELECT a.id, al.studentid as userid, al.timetaken, astat.grade, amax.maxgrade
                FROM   {attendance} a
                JOIN   {attendance_sessions} asess ON (asess.attendanceid = a.id)
                JOIN   {attendance_log} al ON (al.sessionid = asess.id)
                JOIN   {attendance_statuses} astat ON (astat.id = al.statusid)
                JOIN (SELECT attendanceid, max(grade) as maxgrade FROM {attendance_statuses} GROUP BY attendanceid) amax ON (amax.attendanceid = a.id)
                WHERE a.course = :courseid
                      AND al.timetaken > :startdate AND al.timetaken < :enddate';
        $attrecs = $DB->get_recordset_sql($sql, $params);


        foreach ($attrecs as $att) {
            if (!isset($attendances[$att->userid])) {
                $attendances[$att->userid] = array();
                $attendances[$att->userid]['sumgrade'] = 0;
                $attendances[$att->userid]['summaxgrade'] = 0;
            }
            $attendances[$att->userid]['sumgrade'] = $attendances[$att->userid]['sumgrade'] + $att->grade;
            $attendances[$att->userid]['summaxgrade'] = $attendances[$att->userid]['summaxgrade'] + $att->maxgrade;
        }

        $rawdata = new stdClass();
        $rawdata->attendances = $attendances;
        return $rawdata;
    }

    protected function calculate_risks(array $userids) {
        $risks = array();
/*
        $strtotalposts = get_string('e_totalposts', 'engagementindicator_forum');
        $strmaxrisktitle = get_string('maxrisktitle', 'engagementindicator_forum');

        $startweek = date('W', $this->startdate);
        $this->currweek = date('W') - $startweek + 1;
        foreach ($userids as $userid) {
            $risk = 0;
            $reasons = array();
            if (!isset($this->rawdata->attendances[$userid])) {
                // Max risk.
                $info = new stdClass();
                $info->risk = 1.0 * ($this->config['w_totalposts'] +
                                               $this->config['w_replies'] +
                                               $this->config['w_newposts'] +
                                               $this->config['w_readposts']);
                $reason = new stdClass();
                $reason->weighting = '100%';
                $reason->localrisk = '100%';
                $reason->logic = "This user has never made a post or had tracked read posts in the ".
                                 "course and so is at the maximum 100% risk.";
                $reason->riskcontribution = '100%';
                $reason->title = $strmaxrisktitle;
                $info->info = array($reason);
                $risks[$userid] = $info;
                continue;
            }

            $local_risk = $this->calculate('totalposts', $this->rawdata->posts[$userid]['total']);
            $risk_contribution = $local_risk * $this->config['w_totalposts'];
            $reason = new stdClass();
            $reason->weighting = intval($this->config['w_totalposts']*100).'%';
            $reason->localrisk = intval($local_risk*100).'%';
            $reason->logic = "0% risk for more than {$this->config['no_totalposts']} posts a week. ".
                             "100% for {$this->config['max_totalposts']} posts a week.";
            $reason->riskcontribution = intval($risk_contribution*100).'%';
            $reason->title = $strtotalposts;
            $reasons[] = $reason;
            $risk += $risk_contribution;


            $info = new stdClass();
            $info->risk = $risk;
            $info->info = $reasons;
            $risks[$userid] = $info;
        }
*/
        return $risks;
    }

    protected function calculate($type, $num) {
        $risk = 0;
        $maxrisk = $this->config["max_$type"];
        $norisk = $this->config["no_$type"];
        $weight = $this->config["w_$type"];
        if ($num / $this->currweek <= $maxrisk) {
            $risk = $weight;
        } else if ($num / $this->currweek < $norisk) {
            $num = $num / $this->currweek;
            $num -= $maxrisk;
            $num /= $norisk - $maxrisk;
            $risk = $num * $weight;
        }
        return $risk;
    }

    protected function load_config() {
        parent::load_config();
        $defaults = $this->get_defaults();
        foreach ($defaults as $setting => $value) {
            if (!isset($this->config[$setting])) {
                $this->config[$setting] = $value;
            } else if (substr($setting, 0, 2) == 'w_') {
                $this->config[$setting] = $this->config[$setting] / 100;
            }
        }
    }

    public static function get_defaults() {
        $settings = array();
        $settings['w_total'] = 0.56;
        $settings['no_total'] = 1;
        $settings['max_total'] = 0;
        return $settings;
    }
}
