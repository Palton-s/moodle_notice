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
 * Shows pending notices one after another in a modal. Closing the modal
 * (button, X or Esc) records that the user has seen it.
 *
 * @module     local_notice/notice
 * @copyright  2026
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import ModalEvents from 'core/modal_events';
import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {notifyFilterContentUpdated} from 'core_filters/events';

const acknowledge = (noticeid) => Ajax.call([{
    methodname: 'local_notice_acknowledge',
    args: {noticeid},
}])[0];

/**
 * Show one notice and resolve when it is closed.
 *
 * @param {HTMLTemplateElement} item
 * @param {boolean} preview
 * @returns {Promise}
 */
const showNotice = async(item, preview) => {
    const noticeid = parseInt(item.dataset.id, 10);
    const modal = await Modal.create({
        title: item.dataset.title,
        body: item.innerHTML,
        footer: '<button type="button" class="btn btn-primary local-notice-ok" data-action="local-notice-ok"></button>',
        large: true,
        isVerticallyCentered: true,
        removeOnClose: true,
    });

    const root = modal.getRoot();
    root.addClass('local-notice-modal');
    const button = modal.getFooter().find('[data-action="local-notice-ok"]');
    button.text(item.dataset.button);

    return new Promise((resolve) => {
        button.on('click', (e) => {
            e.preventDefault();
            modal.hide();
        });
        root.on(ModalEvents.shown, () => {
            notifyFilterContentUpdated(modal.getBody().get());
            button.trigger('focus');
        });
        root.on(ModalEvents.hidden, () => {
            if (preview) {
                resolve();
                return;
            }
            acknowledge(noticeid).catch(Notification.exception).then(resolve);
        });
        modal.show();
    });
};

/**
 * Entry point.
 */
export const init = async() => {
    const containers = document.querySelectorAll('.local-notice-container:not([data-processed])');
    const queue = [];
    containers.forEach((container) => {
        container.dataset.processed = '1';
        const preview = container.dataset.preview === '1';
        container.querySelectorAll('template.local-notice-item').forEach((item) => queue.push({item, preview}));
    });

    for (const {item, preview} of queue) {
        try {
            await showNotice(item, preview);
        } catch (error) {
            Notification.exception(error);
        }
    }
};
