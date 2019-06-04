<?php
// This file is part of Moodle - http://moodle.org/
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
 *
 * @package    mod_visio
 * @copyright  2019 Pierre Duverneix - Fondation UNIT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/visio/locallib.php');

const VISIO_EVENT_TYPE_OPEN = "visio_conf";

function visio_add_instance($data, $mform) {
    global $CFG, $DB, $USER;

    $parameters = array();
    for ($i = 0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);

    $data->timemodified = time();
    $data->userid = $USER->id;
    $data->id = $DB->insert_record('visio', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'visio', $data->id, $completiontimeexpected);

    set_event($data);

    $visio = $DB->get_record('visio', array('id' => $data->id), '*', MUST_EXIST);
    $subject = get_string('nofiticationsubject', 'visio');
    $body = '<p>'.get_string('messageprovider:submission', 'visio').'</p>';
    $body .= '<p><strong>'.$visio->name.'</strong></p>';
    $body .= '<p>'.$visio->intro.'</p>';
    $body .= '<p><strong>'.get_string("starttime", "visio").'</strong> : ';
    $body .= date('d/m/Y', $visio->starttime).' - '.date('H:i', $visio->starttime);
    $body .= '<br><strong>'.get_string("duration", "visio").'</strong> : '.gmdate('H:i', $visio->duration).'</p>';

    send_visio_notifications($USER, $data->course, $data->coursemodule, $subject, $body, $data->name);
    send_admin_notifications($USER, $data->course, $data->coursemodule, $subject, $body, $data->name);

    return $data->id;
}

function visio_update_instance($data, $mform) {
    global $CFG, $DB, $USER;

    $parameters = array();
    for ($i = 0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);

    $data->timemodified = time();
    $data->id           = $data->instance;

    // Get the visio object before update to check the broadcast URL.
    $oldvisio = $DB->get_record('visio', array('id' => $data->id), '*', MUST_EXIST);

    $DB->update_record('visio', $data);
    visio_update_events($data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'visio', $data->id, $completiontimeexpected);

    // Send notification if a broadcast URL has been added.
    if ($oldvisio->broadcasturl != $data->broadcasturl) {
        $subject = get_string('notificationbroadcast', 'visio');
        $body = '<p>'.get_string('messageprovider:broadcastadded', 'visio').'</p>';
        $body .= '<p><strong>'.$data->name.'</strong></p>';

        send_visio_notifications($USER, $data->course, $data->coursemodule, $subject, $body, $data->name);
    }

    return true;
}

function visio_delete_instance($id) {
    global $DB, $USER;

    if (!$visio = $DB->get_record('visio', array('id' => $id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('visio', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'visio', $id, null);

    $subject = get_string('nofiticationdeletion', 'visio');
    $body = get_string('messageprovider:notificationdeletion', 'visio');

    send_visio_notifications($USER, $visio->course, $visio->id, $subject, $body, $visio->name);

    $DB->delete_records('event', array('modulename' => 'visio', 'instance' => $visio->id));
    $DB->delete_records('visio', array('id' => $visio->id));

    return true;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_visio_core_calendar_provide_event_action(calendar_event $event,
                                                       \core_calendar\action_factory $factory) {
    $cm = get_fast_modinfo($event->courseid)->instances['visio'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/visio/view.php', ['id' => $cm->id]),
        1,
        true
    );
}

function set_event($data) {
    global $USER;

    $event = new stdClass();
    $event->eventtype = VISIO_EVENT_TYPE_OPEN;
    $event->type = CALENDAR_EVENT_TYPE_STANDARD;
    $event->name = get_string('calendar_event', 'mod_visio', $data->name);
    $event->description = $data->intro . "<br><p>Enseignant : ".$USER->firstname." ".$USER->lastname." - ".$USER->email."</p>";
    $event->courseid = $data->course;
    $event->groupid = 0;
    $event->userid = $USER->id;
    $event->modulename = 'visio';
    $event->instance = $data->id;
    $event->timestart = $data->starttime;
    $event->visible = 1;
    $event->timeduration = $data->duration;

    calendar_event::create($event);
}


function visio_update_events($data) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/calendar/lib.php');
    $conds = array('modulename' => 'visio', 'instance' => $data->instance);
    $oldevents = $DB->get_records('event', $conds, 'id ASC');

    foreach ($oldevents as $event) {
        $evt = calendar_event::load($event->id);
        $evt->name = get_string('calendar_event', 'mod_visio', $data->name);
        $evt->description = $data->intro;
        $evt->timestart = $data->starttime;
        $evt->timeduration = $data->duration;
        $evt->update($evt);
    }
}
