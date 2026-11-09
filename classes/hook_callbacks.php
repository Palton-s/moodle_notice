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

namespace local_notice;

use core\hook\output\before_footer_html_generation;
use local_notice\local\manager;

/**
 * Hook callbacks.
 *
 * @package    local_notice
 * @copyright  2026
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /** @var string[] Page layouts where a popup would be out of place. */
    const SKIP_LAYOUTS = ['login', 'popup', 'embedded', 'frametop', 'maintenance', 'print', 'redirect', 'secure'];

    /**
     * Adds pending notices to the page footer and starts the JS that shows them.
     *
     * @param before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE, $USER;

        if (during_initial_install() || CLI_SCRIPT || AJAX_SCRIPT || !manager::is_enabled()) {
            return;
        }
        if (!isloggedin() || isguestuser() || \core\session\manager::is_loggedinas()) {
            return;
        }
        if (in_array($PAGE->pagelayout, self::SKIP_LAYOUTS) || get_user_preferences('auth_forcepasswordchange')) {
            return;
        }
        // Don't interfere with the site policy acceptance flow.
        if (strpos($PAGE->url->get_path(), '/admin/tool/policy/') !== false) {
            return;
        }

        $notices = manager::get_pending_notices((int) $USER->id);
        if (!$notices) {
            return;
        }

        $hook->add_html($hook->renderer->render_from_template('local_notice/notices', [
            'preview' => false,
            'notices' => manager::export_for_template($notices),
        ]));
        $PAGE->requires->js_call_amd('local_notice/notice', 'init');
    }
}
