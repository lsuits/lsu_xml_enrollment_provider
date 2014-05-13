<?php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ues_lib = $CFG->dirroot . '/enrol/ues/publiclib.php';

    if (file_exists($ues_lib)) {

        $_s = function($key, $a=null) {
            return get_string($key, 'local_testlsu', $a);
        };

        require_once $ues_lib;
        ues::require_extensions();

        require_once dirname(__FILE__) . '/provider.php';

        $provider = new testlsu_enrollment_provider(false);

        $settings = new admin_settingpage('local_testlsu', $provider->get_name());
        $settings->add(
            new admin_setting_heading('local_testlsu_header', '',
            $_s('pluginname_desc'))
        );

        // testing controls
        $settings->add(new admin_setting_configtext('local_testlsu/testdir', $_s('testdir'), $_s('testdir_desc'), ''));

//        $provider->settings($settings);

        $ADMIN->add('localplugins', $settings);
    }
}
