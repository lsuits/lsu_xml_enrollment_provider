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

require_once '../../config.php';
require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
ues::require_libs();
require_once 'provider.php';

require_login();

if (!is_siteadmin($USER->id)) {
    print_error('no_permission', 'local_xml', '/my');
}

$provider = new xml_enrollment_provider();

$confirmed = optional_param('confirm', null, PARAM_INT);

$semesters = ues_semester::in_session(time());

$base_url = new moodle_url('/local/xml/reprocess.php');

$_s = ues::gen_str('local_xml');

$pluginname = $_s('pluginname');
$heading = $_s('reprocess');

$admin_plugin = new moodle_url('/admin/settings.php', array('section' => 'local_xml'));

$PAGE->set_url($base_url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title("$pluginname: $heading");
$PAGE->set_heading("$pluginname: $heading");
$PAGE->navbar->add(get_string('administrationsite'));
$PAGE->navbar->add($pluginname, $admin_plugin);
$PAGE->navbar->add($heading);

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

if ($confirmed) {
    $ues = enrol_get_plugin('ues');

    echo html_writer::start_tag('pre');
    $provider->postprocess($ues);
    echo html_writer::end_tag('pre');

    echo $OUTPUT->continue_button($admin_plugin);

} else {

    $confirm = new moodle_url($base_url, array('confirm' => 1));
    echo $OUTPUT->confirm($_s('reprocess_confirm'), $confirm, $admin_plugin);
}

echo $OUTPUT->footer();
