<?php
/// This file is part of Moodle - http://moodle.org/
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
 * This is a one-line short description of the file.
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    local_xml
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Setting strings
$string['pluginname'] = 'General XML Enrollment Provider';
$string['pluginname_desc'] = 'General XML enrollment provider affords testing of the enrollment process';

// Reprocess strings
$string['no_permission'] = 'You do no have sufficient permission to access this page.';
$string['reprocess'] = 'Reprocess Student Data';
$string['reprocess_confirm'] = 'You are about to reprocess student meta
information for all recognized semesters in session. Continue?';

$string['student_data'] = 'Process Student Data';
$string['student_data_desc'] = 'This will enable processing student data in the `postprocess` section of the LSU provider';

$string['anonymous_numbers'] = 'Process LAW Numbers';
$string['anonymous_numbers_desc'] = 'This will enable processing anonymous numbers in the `postprocess` section of the LSU provider';

$string['degree_candidates'] = 'Process Degree Candidacy';
$string['degree_candidates_desc'] = 'This will enabled processing degree candidate information in the `postprocess` section of the provider';

$string['sports_information'] = 'Sports Information';
$string['sports_information_desc'] = 'This will enable the pulling of student athletic information in the `postprocess` section of the provider';

$string['xmldir'] = 'Enrollment data directory';
$string['xmldir_desc'] = 'Path to directory where enrollment files can be found; path is expected to be relative to the Moodle Dataroot';

$string['local_xml'] = 'General XML Enrollment Provider';