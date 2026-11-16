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

namespace local_notice\table;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tablelib.php');

use context_system;
use html_writer;
use local_notice\local\manager;
use moodle_url;
use stdClass;

/**
 * Users who saw (or have not yet seen) a notice, with the details of each view.
 *
 * @package    local_notice
 * @copyright  2026
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class views_table extends \table_sql {

    /** @var string[] Accepted values for the status filter. */
    const STATUSES = ['seen', 'notseen', 'all'];

    /** @var string[] Identity fields shown as columns (site setting "showuseridentity"). */
    protected $identityfields;

    /** @var array Normalised filters. */
    protected $filters;

    /** @var string Date format for the time columns. */
    protected $dateformat;

    /**
     * Constructor.
     *
     * @param stdClass $notice
     * @param array $filters search, status, timefrom, timeto, closemethod (see views.php)
     * @param moodle_url $baseurl page URL including the filters
     */
    public function __construct(stdClass $notice, array $filters, moodle_url $baseurl) {
        global $CFG, $DB;

        // One set of table preferences (sorting, hidden columns) per status, as the columns differ.
        parent::__construct('local_notice_views_' . $filters['status']);
        $this->filters = $filters;
        $this->dateformat = get_string('strftimedatetimeshort', 'langconfig');

        $context = context_system::instance();
        $this->identityfields = \core_user\fields::get_identity_fields($context);
        $userfields = \core_user\fields::for_identity($context)->with_userpic()->excluding('id')
            ->get_sql('u', true, '', '', true);

        // Columns.
        $columns = ['fullname' => get_string('user', 'local_notice')];
        foreach ($this->identityfields as $field) {
            $columns[$field] = \core_user\fields::get_display_name($field);
        }
        $showaccess = $filters['status'] !== 'notseen';
        if ($filters['status'] === 'all') {
            $columns['seenstatus'] = get_string('seenstatus', 'local_notice');
        }
        if ($showaccess) {
            $columns += [
                'timecreated' => get_string('timeseen', 'local_notice'),
                'duration' => get_string('duration', 'local_notice'),
                'closemethod' => get_string('closemethod', 'local_notice'),
                'ipaddress' => get_string('ipaddress', 'local_notice'),
                'browser' => get_string('browser', 'local_notice'),
            ];
        }
        $columns['lastaccess'] = get_string('lastaccess', 'local_notice');

        $this->define_columns(array_keys($columns));
        $this->define_headers(array_values($columns));
        $this->define_baseurl($baseurl);
        $this->sortable(true, $showaccess ? 'timecreated' : 'lastname', $showaccess ? SORT_DESC : SORT_ASC);
        $this->no_sorting('browser');
        $this->collapsible(true);
        $this->set_attribute('class', 'generaltable table-sm local-notice-views');

        // SQL. Every column is always selected, so a sort order remembered from another view stays valid.
        $params = $userfields->params + ['noticeid' => $notice->id, 'guestid' => $CFG->siteguest];
        $join = $filters['status'] === 'seen' ? 'JOIN' : 'LEFT JOIN';
        $from = "{user} u
            $join {" . manager::TABLE_ACK . "} a ON a.userid = u.id AND a.noticeid = :noticeid
                  {$userfields->joins}";
        $active = 'u.suspended = 0 AND u.confirmed = 1';

        $where = ['u.deleted = 0', 'u.id <> :guestid'];
        if ($filters['status'] === 'notseen') {
            $where[] = "a.id IS NULL AND $active";
        } else if ($filters['status'] === 'all') {
            $where[] = "(a.id IS NOT NULL OR ($active))";
        }

        if ($filters['search'] !== '') {
            $like = [];
            $columnsearch = [$DB->sql_fullname('u.firstname', 'u.lastname'), 'u.email', 'u.username', 'u.idnumber',
                'a.ipaddress'];
            foreach ($columnsearch as $i => $column) {
                $like[] = $DB->sql_like($column, ":search$i", false, false);
                $params["search$i"] = '%' . $DB->sql_like_escape($filters['search']) . '%';
            }
            $where[] = '(' . implode(' OR ', $like) . ')';
        }

        // Filters on the view itself make no sense for users who have not seen it.
        if ($showaccess) {
            if ($filters['timefrom']) {
                $where[] = 'a.timecreated >= :timefrom';
                $params['timefrom'] = $filters['timefrom'];
            }
            if ($filters['timeto']) {
                $where[] = 'a.timecreated < :timeto';
                $params['timeto'] = $filters['timeto'];
            }
            if ($filters['closemethod'] !== '') {
                $where[] = 'a.closemethod = :closemethod';
                $params['closemethod'] = $filters['closemethod'];
            }
        }
        $where = implode(' AND ', $where);

        $this->set_sql("u.id, u.lastaccess, a.id AS ackid, a.timecreated, a.duration, a.closemethod,
                        a.ipaddress, a.useragent {$userfields->selects}", $from, $where, $params);
        $this->set_count_sql("SELECT COUNT(1) FROM $from WHERE $where", $params);
    }

    /**
     * User picture and name, linked to the profile.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_fullname($row) {
        global $OUTPUT;
        $name = fullname($row, true);
        if ($this->is_downloading()) {
            return $name;
        }
        $picture = $OUTPUT->user_picture($row, ['size' => 35, 'link' => false, 'alttext' => false]);
        return html_writer::link(new moodle_url('/user/profile.php', ['id' => $row->id]), $picture . $name,
            ['class' => 'local-notice-user']);
    }

    /**
     * Seen / not seen badge.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_seenstatus($row) {
        $seen = !empty($row->ackid);
        $label = get_string($seen ? 'seen' : 'notseen', 'local_notice');
        if ($this->is_downloading()) {
            return $label;
        }
        return html_writer::span($label, 'badge ' . ($seen ? 'bg-success text-white' : 'bg-secondary text-white'));
    }

    /**
     * When the notice was closed.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_timecreated($row) {
        return empty($row->ackid) ? '' : userdate($row->timecreated, $this->dateformat);
    }

    /**
     * Time on screen. Views recorded before version 1.1 have no details.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_duration($row) {
        if (empty($row->ackid) || empty($row->closemethod)) {
            return $this->notrecorded();
        }
        return $row->duration ? format_time((int) $row->duration) : get_string('lessthanasecond', 'local_notice');
    }

    /**
     * Button or X / Esc.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_closemethod($row) {
        if (empty($row->ackid) || empty($row->closemethod)) {
            return $this->notrecorded();
        }
        return get_string('closemethod_' . $row->closemethod, 'local_notice');
    }

    /**
     * IP address, linked to Moodle's IP lookup.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_ipaddress($row) {
        if (empty($row->ipaddress)) {
            return empty($row->ackid) ? '' : $this->notrecorded();
        }
        if ($this->is_downloading()) {
            return $row->ipaddress;
        }
        return html_writer::link(new moodle_url('/iplookup/index.php', ['ip' => $row->ipaddress, 'user' => $row->id]),
            s($row->ipaddress), ['target' => '_blank']);
    }

    /**
     * Browser and operating system, with the full user agent as tooltip (or in full when downloading).
     *
     * @param stdClass $row
     * @return string
     */
    public function col_browser($row) {
        if (empty($row->useragent)) {
            return empty($row->ackid) ? '' : $this->notrecorded();
        }
        if ($this->is_downloading()) {
            return $row->useragent;
        }
        return html_writer::span(s(self::describe_useragent($row->useragent)), '', ['title' => $row->useragent]);
    }

    /**
     * Last access to the site.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_lastaccess($row) {
        return $row->lastaccess ? userdate($row->lastaccess, $this->dateformat) : get_string('never', 'local_notice');
    }

    /**
     * Identity fields (email, ID number, custom profile fields...), escaped.
     *
     * @param string $column
     * @param stdClass $row
     * @return string|null
     */
    public function other_cols($column, $row) {
        if (in_array($column, $this->identityfields)) {
            $value = (string) ($row->$column ?? '');
            return $this->is_downloading() ? $value : s($value);
        }
        return null;
    }

    /**
     * Placeholder for details that were not recorded.
     *
     * @return string
     */
    protected function notrecorded(): string {
        if ($this->is_downloading()) {
            return '';
        }
        return html_writer::span('&ndash;', 'text-muted', ['title' => get_string('notrecorded', 'local_notice')]);
    }

    /**
     * Short "Browser version · System" description of a user agent string.
     *
     * @param string $useragent
     * @return string
     */
    public static function describe_useragent(string $useragent): string {
        // Order matters: most browsers also claim to be Chrome/Safari, Android also claims Linux, iOS claims Mac OS X.
        $browsers = [
            'Moodle app' => '~MoodleMobile[ /]?([\d.]*)~',
            'Edge' => '~Edg(?:e|A|iOS)?/([\d.]+)~',
            'Opera' => '~(?:OPR|Opera)/([\d.]+)~',
            'Samsung Internet' => '~SamsungBrowser/([\d.]+)~',
            'Firefox' => '~(?:Firefox|FxiOS)/([\d.]+)~',
            'Chrome' => '~(?:Chrome|CriOS)/([\d.]+)~',
            'Safari' => '~Version/([\d.]+).*Safari/~',
        ];
        $systems = [
            'Android' => '~Android~',
            'iOS' => '~iPhone|iPad|iPod~',
            'Windows' => '~Windows~',
            'ChromeOS' => '~CrOS~',
            'macOS' => '~Macintosh|Mac OS X~',
            'Linux' => '~Linux~',
        ];

        $browser = '';
        foreach ($browsers as $name => $pattern) {
            if (preg_match($pattern, $useragent, $m)) {
                $major = strtok($m[1] ?? '', '.');
                $browser = $name . ($major ? ' ' . $major : '');
                break;
            }
        }
        $system = '';
        foreach ($systems as $name => $pattern) {
            if (preg_match($pattern, $useragent)) {
                $system = $name;
                break;
            }
        }

        $parts = array_filter([$browser, $system]);
        return $parts ? implode(' · ', $parts) : shorten_text($useragent, 40);
    }
}
