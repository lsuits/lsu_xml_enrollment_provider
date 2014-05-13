<?php

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
