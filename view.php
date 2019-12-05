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
$id       = optional_param('id', 0, PARAM_INT);        // Course module ID.
$u        = optional_param('u', 0, PARAM_INT);         // URL instance id.
$redirect = optional_param('redirect', 0, PARAM_BOOL);

if ($u) {  // Two ways to specify the module.
    $visio = $DB->get_record('visio', array('id' => $u), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('visio', $visio->id, $visio->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('visio', $id, 0, false, MUST_EXIST);
    $visio = $DB->get_record('visio', array('id' => $cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/visio:view', $context);

// Print the page header.
$url = new moodle_url('/mod/visio/view.php', array('id' => $cm->id));

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title('Visio');
$PAGE->set_heading($course->fullname);

// Check user's role.
$isteacher = has_capability('gradereport/grader:view', $context);

echo $OUTPUT->header();

echo $OUTPUT->box_start('generalbox visiobox boxaligncenter');
echo '<h5>'.$OUTPUT->image_icon('icon', 'mod', 'visio').get_string("modulename", "visio").'</h5>';
echo '<h2 class="mb-4">'.$visio->name.'</h2>';

if ($visio->intro != "") {
    echo html_writer::div(
        '<p>'.$visio->intro.'</p>'
    );
}

$starttime = date('d/m/Y', $visio->starttime) . ' ' .get_string("to"). ' ' .date('H:i', $visio->starttime);
$duration = gmdate('H:i', $visio->duration);
$accesstime = intval($visio->starttime) - 900;
$passedtime = intval($visio->starttime + $visio->duration) + 900;
$roomurl = get_visio_url($visio);

$header = new \mod_visio\output\header($starttime, $duration);
$output = $PAGE->get_renderer('mod_visio');
echo $output->render($header);

$poll = new \mod_visio\output\poll($visio->id, $USER->id, $USER->firstname, $USER->lastname, $isteacher);
$output = $PAGE->get_renderer('mod_visio');
echo $output->render($poll);

// Visio launcher.
$config = get_config('mod_visio');
$apiurl = $config->connect_url . "/api/xml?action=common-info";

$launcher = new \mod_visio\output\launcher(
    $visio->id,
    $USER->id,
    $USER->firstname,
    $USER->lastname,
    $isteacher,
    $roomurl,
    $apiurl,
    $visio->broadcasturl,
    $accesstime,
    $passedtime
);
$launcherout = $PAGE->get_renderer('mod_visio');
echo $launcherout->render($launcher);

if ($isteacher) {
    $renderable = new \mod_visio\output\visio_table($visio->id, $USER->id, $course->id);
    $output = $PAGE->get_renderer('mod_visio');
    echo $output->render($renderable);
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
