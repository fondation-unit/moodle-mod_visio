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

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('mod_visio/connect_url', get_string('connect_url', 'visio'),
            get_string('connect_url', 'visio'), false, PARAM_TEXT));

    $settings->add(new admin_setting_configtext('mod_visio/connect_user', get_string('connect_user', 'visio'),
            get_string('connect_user', 'visio'), false, PARAM_TEXT));

    $settings->add(new admin_setting_configtext('mod_visio/connect_pass', get_string('connect_pass', 'visio'),
            get_string('connect_pass', 'visio'), false, PARAM_TEXT));

    $settings->add(new admin_setting_configtext('mod_visio/roomsnumber', get_string('roomsnumber', 'visio'),
            get_string('roomsnumber', 'visio'), false, PARAM_INT));

    $settings->add(new admin_setting_configtext('mod_visio/room1', get_string('room', 'visio', 1),
            get_string('urlroom1', 'visio'), false, PARAM_TEXT));

    $settings->add(new admin_setting_configtext('mod_visio/room2', get_string('room', 'visio', 2),
            get_string('urlroom2', 'visio'), false, PARAM_TEXT));

    $settings->add(new admin_setting_configtext('mod_visio/room3', get_string('room', 'visio', 3),
            get_string('urlroom3', 'visio'), false, PARAM_TEXT));

    $settings->add(new admin_setting_configtext('mod_visio/room4', get_string('room', 'visio', 4),
            get_string('urlroom4', 'visio'), false, PARAM_TEXT));

    $settings->add(new admin_setting_configtext('mod_visio/room5', get_string('room', 'visio', 5),
            get_string('urlroom5', 'visio'), false, PARAM_TEXT));
}

