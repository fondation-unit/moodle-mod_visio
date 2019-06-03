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

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/visio/lib.php');
require_once($CFG->dirroot . '/mod/visio/locallib.php');
require_once($CFG->dirroot . '/enrol/externallib.php');


define('DEFAULT_PAGE_SIZE', 10); // Number of page to show.
$perpage = DEFAULT_PAGE_SIZE;
$id       = optional_param('id', 0, PARAM_INT);        // Course module ID
$u        = optional_param('u', 0, PARAM_INT);         // URL instance id
$redirect = optional_param('redirect', 0, PARAM_BOOL);

if ($u) {  // Two ways to specify the module
    $visio = $DB->get_record('visio', array('id'=>$u), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('visio', $visio->id, $visio->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('visio', $id, 0, false, MUST_EXIST);
    $visio = $DB->get_record('visio', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/visio:view', $context);

/// Print the page header
$url = new moodle_url('/mod/visio/view.php', array('id' => $cm->id));

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title('Visio');
$PAGE->set_heading($course->fullname);

// Check user's role.
$isteacher = has_capability('gradereport/grader:view', $context);

echo $OUTPUT->header();

echo $OUTPUT->box_start('generalbox visiobox boxaligncenter');
echo '<h2>'.get_string("modulename", "visio").'</h2>';
echo '<h4>'.$visio->name.'</h4>';

if ($visio->intro != "") {
    echo html_writer::div(
        '<p>'.$visio->intro.'</p>'
    );
}

echo html_writer::div(
	'<p><strong>'.get_string("starttime", "visio").'</strong> : le '.date('d/m/Y', $visio->starttime).' &agrave; '.date('H:i', $visio->starttime).'</p>'
);
echo html_writer::div(
	'<p><strong>'.get_string("duration", "visio").'</strong> : '.gmdate('H:i', $visio->duration).'</p>'
);

$access_time = intval($visio->starttime)-900;
$passed_visio = intval($visio->starttime + $visio->duration)+900;
$room_url = get_visio_url($visio);

echo html_writer::div('<p>&nbsp;</p>');

if ($isteacher) {
    // if $USER is the course teacher
    $config = get_config('mod_visio');
    $path = $config->connect_url . "/api/xml?action=common-info";

    $PAGE->requires->js_call_amd('mod_visio/visio_actions', 'init', array($path, $room_url, get_string('access', 'visio')));
    echo '<div id="mod_visio_receiver"></div>';

    $renderable = new \mod_visio\output\visio_table($visio->id, $USER->id, $course->id);
    $output = $PAGE->get_renderer('mod_visio');
    echo $output->render($renderable);
} else {
    // students
    $external_url = $room_url . '/?guestName=' . $USER->firstname . ' ' . $USER->lastname;

    $launcher = new \mod_visio\output\launcher($visio->id, $USER->id, $external_url, $access_time, $passed_visio);
    $launcherout = $PAGE->get_renderer('mod_visio');
    echo $launcherout->render($launcher);
}
echo $OUTPUT->box_end();

echo $OUTPUT->footer();
