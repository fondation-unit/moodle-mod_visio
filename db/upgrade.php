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

defined('MOODLE_INTERNAL') || die();

function xmldb_visio_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2019050700) {
        // Changing type of field value on table lti_types_config to text.
        $table = new xmldb_table('visio');
        $field = new xmldb_field('broadcasturl', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2019050700, 'visio');
    }

    if ($oldversion < 2019051702) {
        $table = new xmldb_table('visio_presence');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('visioid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('userfrom', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        // Adding keys to table.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('userfrom', XMLDB_KEY_FOREIGN, array('userfrom'), 'user', array('id'));
        // Create table.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2019051702, 'visio');
    }

    if ($oldversion < 2019060300) {
        $table = new xmldb_table('visio_presence');

        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('visio_presence');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('visioid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('value', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        // Adding keys to table.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Create table.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    }

    if ($oldversion < 2019060501) {
        // Adds fields for the polling feature.
        $table = new xmldb_table('visio');
        $fields = array(
            new xmldb_field('polltime_1', XMLDB_TYPE_INTEGER, '10', null, null, null, null),
            new xmldb_field('polltime_2', XMLDB_TYPE_INTEGER, '10', null, null, null, null),
            new xmldb_field('polltime_3', XMLDB_TYPE_INTEGER, '10', null, null, null, null),
            new xmldb_field('polltime_4', XMLDB_TYPE_INTEGER, '10', null, null, null, null),
            new xmldb_field('polltime_5', XMLDB_TYPE_INTEGER, '10', null, null, null, null)
        );

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        
        upgrade_mod_savepoint(true, 2019060501, 'visio');
    }


    if ($oldversion < 2019060502) {
        // Adds users choices fields for the polling feature.
        $table = new xmldb_table('visio_polls');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('visioid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('poll_value', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        // Adding keys to table.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('visioid', XMLDB_KEY_FOREIGN, array('visioid'), 'visio', array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Create table.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        upgrade_mod_savepoint(true, 2019060502, 'visio');
    }

    return true;
}