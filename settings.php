<?php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ues_lib = $CFG->dirroot . '/enrol/ues/publiclib.php';

    if (file_exists($ues_lib)) {
        require_once $ues_lib;
        ues::require_extensions();

        require_once dirname(__FILE__) . '/provider.php';

        $provider = new lsu_enrollment_provider(false);

        $settings = new admin_settingpage('local_lsu', $provider->get_name());

        $provider->settings($settings);

        $ADMIN->add('localplugins', $settings);
    }
}
