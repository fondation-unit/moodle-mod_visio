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

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/calendar/lib.php');

class mod_visio_mod_form extends moodleform_mod {
    
    function definition() {
        global $CFG, $DB;
        $mform =& $this->_form;

        $config = get_config('mod_visio');
        
        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        
        $this->standard_intro_elements();
        $element = $mform->getElement('introeditor');
        $attributes = $element->getAttributes();
        $attributes['rows'] = 5;
        $element->setAttributes($attributes);

        $mform->addRule('introeditor', null, 'required', null, 'client');
        $mform->addRule('introeditor', null, 'required', null, 'server');

        //-------------------------------------------------------

        // Start and end date selectors
        $time       = time();
        $starttime  = usertime($time);
        $mform->addElement('date_time_selector', 'starttime', get_string('starttime', 'visio'));
        $mform->addRule('starttime', null, 'required', null, 'client');

        $options = array(
            1800 => '30 ' . get_string('min', 'visio'),
            3600 => '1 ' . get_string('hour', 'visio'),
            7200 => '2 ' . get_string('hours', 'visio'),
            10800 => '3 ' . get_string('hours', 'visio'),
            14400 => '4 ' . get_string('hours', 'visio'),
        );
        $mform->addElement('select', 'duration', get_string('duration', 'visio'), $options);
        $mform->addRule('duration', null, 'required', null, 'client');
       
        $mform->addElement('text', 'broadcasturl', get_string('broadcasturl', 'visio'), array('size'=>'48'));
        $mform->setType('broadcasturl', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('broadcasturl', 'broadcasturl', 'visio');

        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values) {
        if (!empty($default_values['parameters'])) {
            $parameters = unserialize($default_values['parameters']);
            $i = 0;
            foreach ($parameters as $parameter=>$variable) {
                $default_values['parameter_'.$i] = $parameter;
                $default_values['variable_'.$i]  = $variable;
                $i++;
            }
        }
    }

    /**
     * Enforce validation rules here
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array
     **/
    function validation($data, $files) {
        global $DB;
        $config = get_config('mod_visio');
        $mform =& $this->_form;
        $errors = parent::validation($data, $files);

        $sql = "SELECT * FROM {event} WHERE eventtype = 'visio_conf' AND timestart BETWEEN ? AND ?";
        $start = strval(intval($data['starttime'])-900);
        $end = strval(intval($data['starttime'] + $data['duration'])+900);

        $events = $DB->get_records_sql($sql, array($start, $end));
        $room_id = count($events)+1;
        if (!empty($events)) {
	    // if the maximum number of rooms is already reached
            if (count($events) >= $config->roomsnumber) {
                $errors['starttime'] = get_string('date_taken', 'visio');
            } else {
                $mform->addElement('hidden', 'roomurl', $config->{'room'.$room_id});
            }
        } else {
            $mform->addElement('hidden', 'roomurl', $config->{'room'.$room_id});
        }

        return $errors;
    }
}
