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


function xml_attribute($object, $attribute) {
    if (isset($object[$attribute])) {
        return (string) $object[$attribute];
    }
}

function get_visio_url($visio) {
    $config = get_config('mod_visio');

    if (!isset($visio->roomurl) || $visio->roomurl == '0') {
        if (isset($config->room1)) {
            return $config->room1;
        } else {
            throw new \coding_exception('The module has no room set.');
        }
    } else {
        return $visio->roomurl;
    }
}

function prepare_message($userfrom, $userto, $courseid, $instanceid, $subject, $body, $name) {
    $contexturl = new moodle_url('/mod/visio/view.php', array('id' => $instanceid));

    $message = new \core\message\message();
    $message->courseid = $courseid;
    $message->component = 'mod_visio';
    $message->name = 'submission';
    $message->userfrom = $userfrom;
    $message->userto = $userto;
    $message->subject = $subject;
    $message->fullmessage = $body;
    $message->fullmessageformat = FORMAT_PLAIN;
    $message->fullmessagehtml = '<p>'.$body.'</p>';
    $message->smallmessage = $body;
    $message->notification = 1;
    $message->contexturl = $contexturl;
    $message->contexturlname = 'visio';

    return $message;
}

function send_visio_notifications($userfrom, $courseid, $instanceid, $subject, $body, $name) {
    global $DB;

    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

    if ($course->groupmode == SEPARATEGROUPS) {
        $groups = groups_get_all_groups($courseid, 0, 0, 'g.*', true);

        foreach ($groups as $group) {
            if ( in_array($userfrom->id, $group->members) ) {

                foreach ($group->members as $member) {
                    if ($userfrom->id != $member) {
                        $message = prepare_message($userfrom, $member, $courseid, $instanceid, $subject, $body, $name);
                        message_send($message);
                    }
                }
            }
        }
    } else {
        $coursecontext = context_course::instance($courseid);
        $users = get_enrolled_users($coursecontext, '', 0, 'u.*', '', 0, 0);

        foreach ($users as $user) {
            $message = prepare_message($userfrom, $user, $courseid, $instanceid, $subject, $body, $name);
            message_send($message);
        }
    }
}

function send_admin_notifications($userfrom, $courseid, $instanceid, $subject, $body, $name) {
    global $DB;

    $admins = get_admins();
    foreach ($admins as $admin) {
        $message = prepare_message($userfrom, $admin, $courseid, $instanceid, $subject, $body, $name);
        message_send($message);
    }
}

function send_reminder_notification() {
    global $CFG, $USER, $DB;

    // Get all the visios that must start within 30 minutes from now.
    $sql = 'SELECT * FROM {visio} '.
            'WHERE starttime <= ? AND starttime > ? AND userid = ?';
    $time = new DateTime("now", core_date::get_user_timezone_object());
    $timecheck = $time->getTimestamp() + 1800;
    $timelimit = $time->getTimestamp() + 900;
    $visios = $DB->get_records_sql($sql, array($timecheck, $timelimit, $USER->id), IGNORE_MISSING);
    $str = get_string('messageprovider:beginsoon', 'visio');

    // Proceed to send notifications to all participants.
    foreach ($visios as $visio) {
        $cm = get_coursemodule_from_instance('visio', $visio->id, $visio->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $enrolled = get_enrolled_users($context, 'mod/visio:view');

        $body = '<p>'.get_string('messageprovider:beginsoon', 'visio').'</p>';
        $body .= '<p><strong>'.$visio->name.'</strong></p>';
        $body .= '<p>'.$visio->intro.'</p>';
        $body .= '<p><strong>'.get_string("starttime", "visio").'</strong> : ';
        $body .= date('d/m/Y', $visio->starttime).' - '.date('H:i', $visio->starttime);
        $body .= '<br><strong>'.get_string("duration", "visio").'</strong> : '.gmdate('H:i', $visio->duration).'</p>';

        foreach ($enrolled as $enrol) {
            $message = prepare_message($USER, $enrol->id, $visio->course, $cm->id, $str, $body, $visio->name);
            message_send($message);
        }
    }
}

function get_roles_in_course($instanceid) {
    global $DB;

    $req = 'SELECT rolestoshow FROM {observation} where id = '.$instanceid;
    $roles = $DB->get_records_sql($req);

    foreach ($roles as $role) {
        return json_decode($role->rolestoshow);
    }
}

function generate_sql($cm, $userid, $courseid) {
    global $CFG, $USER, $DB;

    require_once($CFG->dirroot . '/course/lib.php');
    require_once($CFG->dirroot . '/user/lib.php');

    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $coursecontext = context_course::instance($courseid, IGNORE_MISSING);

    if ($courseid == SITEID) {
        $context = context_system::instance();
    } else {
        $context = $coursecontext;
    }
    try {
        external_api::validate_context($context);
    } catch (Exception $e) {
        $exceptionparam = new stdClass();
        $exceptionparam->message = $e->getMessage();
        $exceptionparam->courseid = $courseid;
        throw new moodle_exception('errorcoursecontextnotvalid' , 'webservice', '', $exceptionparam);
    }

    course_require_view_participants($context);

    list($enrolledsql, $enrolledparams) = get_enrolled_sql($coursecontext, '', null, false);

    $ctxselect = ', ' . context_helper::get_preload_record_columns_sql('ctx');
    $ctxjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = u.id AND ctx.contextlevel = :contextlevel)";
    $enrolledparams['contextlevel'] = CONTEXT_USER;

    $groupmode = groups_get_activity_groupmode($cm, $course);

    $groupjoin = '';
    if ($groupmode == SEPARATEGROUPS) {
        // Filter by groups the user can view.
        $usersgroups = groups_get_user_groups($courseid, $userid);

        if (!empty($usergroups['0'])) {
            list($groupsql, $groupparams) = $DB->get_in_or_equal($usergroups['0'], SQL_PARAMS_NAMED);
            $groupjoin = "JOIN {groups_members} gm ON (u.id = gm.userid AND gm.groupid $groupsql)";
            $enrolledparams = array_merge($enrolledparams, $groupparams);
        } else {
            // User doesn't belong to any group, so he can't see any user. Return an empty array.
            return array();
        }
    }

    foreach ($usersgroups as $group) {
        $users = groups_get_members($group);
    }

    $sqlselect = 'us.*';
    $sqlfrom = '{user} us
              JOIN (
                  SELECT DISTINCT u.id '.$ctxselect.'
                    FROM {user} u '.$ctxjoin . $groupjoin . '
                   WHERE u.id IN ('.$enrolledsql.')
              ) q ON q.id = us.id';

    // Check if the user id is in the list of users enrolled in a course with specified roles.
    $sqlwhere = 'us.id IN('.
    'SELECT ue.userid FROM mdl_role_assignments AS ra '.
    'LEFT JOIN mdl_user_enrolments AS ue ON ra.userid = ue.userid '.
    'LEFT JOIN mdl_context AS c ON c.id = ra.contextid '.
    'LEFT JOIN mdl_enrol AS e ON e.courseid = c.instanceid AND ue.enrolid = e.id '.
    'WHERE e.courseid = '.$courseid.')';

    $enrolledparams = array_merge($enrolledparams);
    if ($enrolledparams == null) {
        $enrolledparams = array();
    }

    return array('select' => $sqlselect, 'from' => $sqlfrom, 'where' => $sqlwhere, 'param' => $enrolledparams);
}