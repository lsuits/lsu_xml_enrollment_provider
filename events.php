<?php

abstract class xml_enrollment_events {
    public static function ues_list_provider($data) {
        $data->plugins += array('xml' => get_string('pluginname', 'local_xml'));
        return true;
    }

    public static function ues_load_xml_provider($data) {
        require_once dirname(__FILE__) . '/provider.php';
        return true;
    }
}
