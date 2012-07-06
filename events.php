<?php

abstract class lsu_enrollment_events {
    public static function ues_list_provider($data) {
        $data->plugins += array('lsu' => get_string('pluginname', 'local_lsu'));
        return true;
    }

    public static function ues_load_lsu_provider($data) {
        require_once dirname(__FILE__) . '/provider.php';
        return true;
    }
}
