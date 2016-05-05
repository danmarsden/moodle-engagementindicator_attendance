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
 * Strings
 *
 * @package    engagementindicator_attendance
 * @author     Dan Marsden <dan@danmarsden.com>
 * @copyright  2013 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Attendance Activity';
$string['pluginname_help'] = 'This indicator calculates risk rating based on attendance';
$string['entriesperweek'] = 'Entries per week';
$string['weighting'] = 'Weighting';
$string['weighting_help'] = 'This figure shows the amount this item contributes towards the overall risk for the Attendance indicator. The local risk will be multiplied by this to form the risk contribution.';
$string['localrisk'] = 'Local Risk';
$string['localrisk_help'] = 'The risk percentage of this alone, out of 100.  The local risk is multiplied by the login weighting to form the Risk Contribution.';
$string['riskcontribution'] = 'Risk Contribution';
$string['riskcontribution_help'] = 'The amount of risk this particular Attendance contributes to the overall risk returned for the Attendance indicator.  This is formed by multiplying the Local Risk with the Weighting.  The Risk Contributions of each forum item are summed together to form the overall risk for the indicator.';
