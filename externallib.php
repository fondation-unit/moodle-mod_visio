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

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/mod/visio/locallib.php');

/**
 * feedback save functions
 */
class mod_visio_external extends external_api {

    /**
     * Describes the parameters for host_launch_visio.
     *
     * @return external_function_parameters
     * @since  Moodle 3.4
     */
    public static function host_launch_visio_parameters() {
        return new external_function_parameters(
            array('url' => new external_value(PARAM_RAW, 'URL to launch the host visio'))
        );
    }

    public static function host_launch_visio($url) {
        global $CFG;

        // Parameters validation.
        $params = self::validate_parameters(self::host_launch_visio_parameters(), array('url' => $url));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $retvalue = curl_exec($ch);
        curl_close($ch);
        unset($ch);

        if (isset($retvalue)) {
            $config = get_config('mod_visio');

            if (isset($config->connect_user) && isset($config->connect_pass)) {
                $xmlelem = simplexml_load_string($retvalue);
                $session = (string) $xmlelem->common->cookie;
                $accountid = xml_attribute($xmlelem->common->account, 'account-id');

                $finalurl = $config->connect_url.'/api/xml?action=login&login=';
                $finalurl .= $config->connect_user.'&password='.$config->connect_pass;
                $finalurl .= '&account-id='.$accountid.'&session='.$session;

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $finalurl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $value = curl_exec($ch);
                curl_close($ch);

                return $session;
            } else {
                throw new \coding_exception('The module config is not correctly set.');
            }
        } else {
            throw new \coding_exception('The module config is not correctly set.');
        }
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.4
     */
    public static function host_launch_visio_returns() {
        return new external_value(PARAM_RAW, 'Visio launched by the host');
    }


    /**
     * Describes the parameters for set_presence.
     *
     * @return external_function_parameters
     * @since  Moodle 3.4
     */
    public static function set_presence_parameters() {
        return new external_function_parameters(
            array(
                'visioid' => new external_value(PARAM_INT, 'The visio ID'),
                'userid' => new external_value(PARAM_INT, 'The user ID'),
                'value' => new external_value(PARAM_INT, 'The presence value'),
            )
        );
    }

    public static function set_presence($visioid, $userid, $value) {
        global $DB, $USER;

        // Parameters validation.
        $params = self::validate_parameters(self::set_presence_parameters(),
            array('visioid' => $visioid, 'userid' => $userid, 'value' => $value));

        $dataobject = new \stdClass();
        $dataobject->visioid = $params['visioid'];
        $dataobject->userid = $params['userid'];
        $dataobject->value = $params['value'];
        $dataobject->timecreated = \time();

        // Check for presence.
        $presence = $DB->get_record('visio_presence', array(
            'visioid' => $params['visioid'],
            'userid' => $params['userid']));

        if (!$presence) {
            $DB->insert_record('visio_presence', $dataobject, true);
        } else {
            if ($USER->id == $params['userid']) {
                // The user indicates his presence by himself.
                $presence->value = 1;
                $DB->update_record('visio_presence', $presence);
            } else {
                // Teachers.
                if ($params['value'] == 2) {
                    $presence->value = 2;
                    $DB->update_record('visio_presence', $presence);
                } else {
                    $presence->value = 0;
                    $DB->update_record('visio_presence', $presence);
                }
            }
        }

        return date('H:i:s', time());
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.4
     */
    public static function set_presence_returns() {
        return new external_value(PARAM_TEXT, 'The time the presence was set');
    }


    /**
     * Describes the parameters for get_presence.
     *
     * @return external_function_parameters
     * @since  Moodle 3.4
     */
    public static function get_presence_parameters() {
        return new external_function_parameters(
            array(
                'visioid' => new external_value(PARAM_INT, 'The visio ID')
            )
        );
    }

    public static function get_presence($visioid) {
        global $DB;

        // Parameters validation.
        $params = self::validate_parameters(self::get_presence_parameters(),
            array('visioid' => $visioid));

        $data = $DB->get_records('visio_presence', array('visioid' => $params['visioid']));
        return $data;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.4
     */
    public static function get_presence_returns() {
        return new external_multiple_structure(new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'The component id.'),
                'visioid' => new external_value(PARAM_RAW, 'The visio id.'),
                'userid' => new external_value(PARAM_RAW, 'The user id.'),
                'value' => new external_value(PARAM_RAW, 'Value of the presence.'),
                'timecreated' => new external_value(PARAM_TEXT, 'Time created.'),
            )
        ));
    }
}