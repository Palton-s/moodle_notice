<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_notice\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider: stores which notices each user dismissed.
 *
 * @package    local_notice
 * @copyright  2026
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Metadata.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_notice_ack', [
            'noticeid' => 'privacy:metadata:local_notice_ack:noticeid',
            'userid' => 'privacy:metadata:local_notice_ack:userid',
            'timecreated' => 'privacy:metadata:local_notice_ack:timecreated',
        ], 'privacy:metadata:local_notice_ack');
        $collection->add_database_table('local_notice', [
            'usermodified' => 'privacy:metadata:local_notice:usermodified',
        ], 'privacy:metadata:local_notice');
        return $collection;
    }

    /**
     * Contexts with user data.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $contextlist = new contextlist();
        if ($DB->record_exists('local_notice_ack', ['userid' => $userid])) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    /**
     * Users in context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        if ($userlist->get_context()->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        $userlist->add_from_sql('userid', 'SELECT userid FROM {local_notice_ack}', []);
    }

    /**
     * Export.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }
            $records = $DB->get_records_sql(
                "SELECT a.id, n.title, a.timecreated
                   FROM {local_notice_ack} a
                   JOIN {local_notice} n ON n.id = a.noticeid
                  WHERE a.userid = :userid",
                ['userid' => $userid]
            );
            $data = array_map(fn($r) => [
                'title' => $r->title,
                'timecreated' => transform::datetime($r->timecreated),
            ], array_values($records));
            if ($data) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_notice')], (object) ['acknowledged' => $data]);
            }
        }
    }

    /**
     * Delete all users in context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $DB->delete_records('local_notice_ack');
        }
    }

    /**
     * Delete one user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_SYSTEM) {
                $DB->delete_records('local_notice_ack', ['userid' => $contextlist->get_user()->id]);
            }
        }
    }

    /**
     * Delete several users.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        if ($userlist->get_context()->contextlevel != CONTEXT_SYSTEM || !$userlist->get_userids()) {
            return;
        }
        [$sql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_notice_ack', "userid $sql", $params);
    }
}
