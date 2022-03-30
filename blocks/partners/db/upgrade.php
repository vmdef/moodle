<?php

defined('MOODLE_INTERNAL') || die();

/**
 *
 * @global moodle_database $DB
 * @param int $oldversion
 */
function xmldb_block_partners_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

     if ($oldversion < 2014121900) {

        // Define table block_partners_countries to be created.
        $table = new xmldb_table('block_partners_countries');

        // Adding fields to table block_partners_countries.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('ipfrom', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('ipto', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('code2', XMLDB_TYPE_CHAR, '2', null, null, null, null);
        $table->add_field('code3', XMLDB_TYPE_CHAR, '3', null, null, null, null);
        $table->add_field('countryname', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table block_partners_countries.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_partners_countries.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_partners_ads to be created.
        $table = new xmldb_table('block_partners_ads');

        // Adding fields to table block_partners_ads.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('country', XMLDB_TYPE_CHAR, '2', null, null, null, null);
        $table->add_field('partner', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('image', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table block_partners_ads.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_partners_ads.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Partners savepoint reached.
        upgrade_block_savepoint(true, 2014121900, 'partners');
    }
    return true;
}
