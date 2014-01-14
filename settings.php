<?php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ues_lib = $CFG->dirroot . '/enrol/ues/publiclib.php';

    if (file_exists($ues_lib)) {

        $_s = function($key, $a=null) {
            return get_string($key, 'local_lsu', $a);
        };

        require_once $ues_lib;
        ues::require_extensions();

        require_once dirname(__FILE__) . '/provider.php';

        $provider = new lsu_enrollment_provider(false);

        $reprocessurl = new moodle_url('/local/lsu/reprocess.php');

        $a = new stdClass;
        $a->reprocessurl = $reprocessurl->out(false);

        $settings = new admin_settingpage('local_lsu', $provider->get_name());
        $settings->add(
            new admin_setting_heading('local_lsu_header', '',
            $_s('pluginname_desc', $a))
        );

        // testing controls
        $settings->add(new admin_setting_configcheckbox('local_lsu/testing', $_s('local_lsu_testing_name'), $_s('local_lsu_testing_description'), 1));
        $settings->add(new admin_setting_configtext('local_lsu/testdir', $_s('testdir'), $_s('testdir_desc'), ''));

        $provider->settings($settings);

        $ADMIN->add('localplugins', $settings);
    }
}
