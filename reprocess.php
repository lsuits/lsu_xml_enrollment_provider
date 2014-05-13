<?php

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
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
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
