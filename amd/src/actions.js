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
 * Actions for webservice manager tool.
 *
 * @module     tool_wsmanager/actions
 * @copyright  Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import {getString} from 'core/str';
import * as reportEvents from 'core_reportbuilder/local/events';
import * as reportSelectors from 'core_reportbuilder/local/selectors';
import {dispatchEvent} from 'core/event_dispatcher';

const SELECTORS = {
    EDITSERVICES: '[data-action="tool_wsmanager-edit-services"][data-function]',
};

/**
 * Initialize module.
 */
export function init() {

    document.addEventListener('click', event => {

        // Edit services for the function.
        const editServices = event.target.closest(SELECTORS.EDITSERVICES);
        if (editServices) {
            event.preventDefault();
            const functionname = editServices.dataset.function;

            const modalForm = new ModalForm({
                modalConfig: {
                    title: getString('editservices', 'tool_wsmanager'),
                },
                formClass: 'tool_wsmanager\\form\\function_services_form',
                args: {functionname},
                saveButtonText: getString('savechanges', 'moodle'),
                returnFocus: editServices,
            });

            // Show a toast notification when the form is submitted.
            const reportElement = event.target.closest(reportSelectors.regions.report);
            modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
                dispatchEvent(reportEvents.tableReload, {preservePagination: true}, reportElement);
            });

            modalForm.show();
        }
    });
}