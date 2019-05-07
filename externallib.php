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

        //Parameters validation.
        $params = self::validate_parameters(self::host_launch_visio_parameters(), array('url'=>$url));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $ret_value = curl_exec($ch);
        curl_close($ch);
        unset($ch);

        if (isset($ret_value)) {
            $config = get_config('mod_visio');

            if (isset($config->connect_user) && isset($config->connect_pass)) {
                $xmlelem = simplexml_load_string($ret_value);
                $session = (string) $xmlelem->common->cookie;
                $accountid = xml_attribute($xmlelem->common->account, 'account-id');
                $final_url = $config->connect_url.'/api/xml?action=login&login='.$config->connect_user.'&password='.$config->connect_pass.'&account-id='.$accountid.'&session='.$session;
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $final_url);
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
    
}