<?php

$events = array('ues_list_provider', 'ues_load_lsu_provider');

$mapper = function($event) {
    return array(
        'handlerfile' => '/local/lsu/events.php',
        'handlerfunction' => array('lsu_enrollment_events', $event),
        'schedule' => 'instant'
    );
};

$handlers = array_combine($events, array_map($mapper, $events));
