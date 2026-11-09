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

/**
 * Admin settings for local_notice.
 *
 * @package    local_notice
 * @copyright  2026
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('localplugins', new admin_category('local_notice_category', get_string('pluginname', 'local_notice')));

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_notice_settings', get_string('settings', 'local_notice'));
    if ($ADMIN->fulltree) {
        $setting = new admin_setting_configcheckbox('local_notice/enabled',
            get_string('enabled', 'local_notice'), get_string('enabled_desc', 'local_notice'), 1);
        $setting->set_updatedcallback('local_notice_setting_updated');
        $settings->add($setting);
    }
    $ADMIN->add('local_notice_category', $settings);
}

$ADMIN->add('local_notice_category', new admin_externalpage('local_notice_manage',
    get_string('managenotices', 'local_notice'),
    new moodle_url('/local/notice/manage.php'),
    'local/notice:manage'));

// Keep $settings null so Moodle doesn't add a duplicate page for this plugin.
$settings = null;
