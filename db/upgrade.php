<?php

/**
 * Logic circuit editor question type upgrade code
 *
 * @package    qtype_logiccircuit
 * @copyright  2025 Groupe Modulo
 * @license    CC BY-NC-SA
 */

/**
 * Method to perform upgrade steps between versions
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_qtype_logiccircuit_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026042101) {
        $table = new xmldb_table('question_logiccircuit');
        $field = new xmldb_field('penaltyregime', XMLDB_TYPE_TEXT, null, null, null, null, null, 'componentstoshow');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026042101, 'qtype', 'logiccircuit');
    }

    // Automatically generated Moodle v4.2.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v4.3.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v4.4.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v4.5.0 release upgrade line.
    // Put any upgrade step following this.

    return true;
}
