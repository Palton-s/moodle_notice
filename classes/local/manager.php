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

namespace local_notice\local;

use context_system;
use stdClass;

/**
 * Data access and helpers for notices.
 *
 * @package    local_notice
 * @copyright  2026
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /** @var string Main table. */
    const TABLE = 'local_notice';

    /** @var string Acknowledgements table. */
    const TABLE_ACK = 'local_notice_ack';

    /** @var string File area for embedded media. */
    const FILEAREA = 'content';

    /**
     * Options for the content editor (allows images, video, files).
     *
     * @return array
     */
    public static function editor_options(): array {
        global $CFG;
        return [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'subdirs' => 0,
            'noclean' => true,
            'trusttext' => false,
            'context' => context_system::instance(),
        ];
    }

    /**
     * Whether the whole feature is switched on.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return (bool) get_config('local_notice', 'enabled');
    }

    /**
     * Invalidates the per-session "nothing to show" cache for every user.
     */
    public static function bump_revision(): void {
        set_config('rev', ((int) get_config('local_notice', 'rev')) + 1, 'local_notice');
    }

    /**
     * Returns the notices the user still has to see, oldest first.
     *
     * When nothing is pending the result is cached in the session, so normal page
     * loads cost no queries until a notice is created/changed or a scheduled one starts.
     *
     * @param int $userid
     * @return stdClass[]
     */
    public static function get_pending_notices(int $userid): array {
        global $DB, $SESSION;

        $now = time();
        $rev = (int) get_config('local_notice', 'rev');
        $cache = $SESSION->local_notice_cache ?? null;
        if ($cache && $cache->userid == $userid && $cache->rev == $rev && $cache->until > $now) {
            return [];
        }

        $sql = "SELECT n.*
                  FROM {" . self::TABLE . "} n
             LEFT JOIN {" . self::TABLE_ACK . "} a ON a.noticeid = n.id AND a.userid = :userid
                 WHERE n.enabled = 1
                   AND a.id IS NULL
                   AND n.timestart <= :now1
                   AND (n.timeend = 0 OR n.timeend > :now2)
              ORDER BY n.timestart ASC, n.id ASC";
        $notices = $DB->get_records_sql($sql, ['userid' => $userid, 'now1' => $now, 'now2' => $now]);

        if (!$notices) {
            // Remember until the next scheduled notice starts (if any).
            $next = $DB->get_field_sql(
                "SELECT MIN(timestart) FROM {" . self::TABLE . "} WHERE enabled = 1 AND timestart > :now",
                ['now' => $now]
            );
            $SESSION->local_notice_cache = (object) [
                'userid' => $userid,
                'rev' => $rev,
                'until' => $next ? (int) $next : PHP_INT_MAX,
            ];
        }
        return array_values($notices);
    }

    /**
     * Records that a user dismissed a notice.
     *
     * @param int $noticeid
     * @param int $userid
     * @return bool true if the notice exists (and is now acknowledged)
     */
    public static function acknowledge(int $noticeid, int $userid): bool {
        global $DB;
        if (!$DB->record_exists(self::TABLE, ['id' => $noticeid])) {
            return false;
        }
        if (!$DB->record_exists(self::TABLE_ACK, ['noticeid' => $noticeid, 'userid' => $userid])) {
            try {
                $DB->insert_record(self::TABLE_ACK, (object) [
                    'noticeid' => $noticeid,
                    'userid' => $userid,
                    'timecreated' => time(),
                ]);
            } catch (\dml_write_exception $e) {
                // Double click / two tabs: unique index already has it. Nothing to do.
                if (!$DB->record_exists(self::TABLE_ACK, ['noticeid' => $noticeid, 'userid' => $userid])) {
                    throw $e;
                }
            }
        }
        return true;
    }

    /**
     * Makes a notice show again for everyone.
     *
     * @param int $noticeid
     */
    public static function reset_acknowledgements(int $noticeid): void {
        global $DB;
        $DB->delete_records(self::TABLE_ACK, ['noticeid' => $noticeid]);
        self::bump_revision();
    }

    /**
     * Deletes a notice, its acknowledgements and its files.
     *
     * @param int $noticeid
     */
    public static function delete(int $noticeid): void {
        global $DB;
        $DB->delete_records(self::TABLE_ACK, ['noticeid' => $noticeid]);
        $DB->delete_records(self::TABLE, ['id' => $noticeid]);
        get_file_storage()->delete_area_files(context_system::instance()->id, 'local_notice', self::FILEAREA, $noticeid);
        self::bump_revision();
    }

    /**
     * Prepares notices for the template.
     *
     * @param stdClass[] $notices
     * @return array
     */
    public static function export_for_template(array $notices): array {
        $context = context_system::instance();
        $result = [];
        foreach ($notices as $notice) {
            $content = file_rewrite_pluginfile_urls($notice->content ?? '', 'pluginfile.php', $context->id,
                'local_notice', self::FILEAREA, $notice->id);
            $result[] = [
                'id' => (int) $notice->id,
                'title' => format_string($notice->title, true, ['context' => $context]),
                'content' => format_text($content, $notice->contentformat, [
                    'context' => $context,
                    'noclean' => true,
                    'overflowdiv' => false,
                ]),
                'buttonlabel' => trim((string) $notice->buttonlabel) !== ''
                    ? format_string($notice->buttonlabel, true, ['context' => $context])
                    : get_string('defaultbutton', 'local_notice'),
            ];
        }
        return $result;
    }
}
