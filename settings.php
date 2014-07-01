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

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ues_lib = $CFG->dirroot . '/enrol/ues/publiclib.php';

    if (file_exists($ues_lib)) {

        $_s = function($key, $a=null) {
            return get_string($key, 'local_xml', $a);
        };

        require_once $ues_lib;
        ues::require_extensions();

        require_once dirname(__FILE__) . '/provider.php';

        $provider = new xml_enrollment_provider(false);

        $reprocessurl = new moodle_url('/local/xml/reprocess.php');

        $a = new stdClass;
        $a->reprocessurl = $reprocessurl->out(false);

        $settings = new admin_settingpage('local_xml', $provider->get_name());
        $settings->add(
            new admin_setting_heading('local_xml_header', '',
            $_s('pluginname_desc', $a))
        );

        // testing controls
        $settings->add(new admin_setting_configtext('local_xml/xmldir', $_s('xmldir'), $_s('xmldir_desc'), ''));

//        $provider->settings($settings);

        $ADMIN->add('localplugins', $settings);
    }
}
