<?php

$events = array('ues_list_provider', 'ues_load_xml_provider');

$mapper = function($event) {
    return array(
        'handlerfile' => '/local/xml/events.php',
        'handlerfunction' => array('xml_enrollment_events', $event),
        'schedule' => 'instant'
    );
};

$handlers = array_combine($events, array_map($mapper, $events));