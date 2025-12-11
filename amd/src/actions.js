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
import Ajax from 'core/ajax';
import Notification from 'core/notification';

const SELECTORS = {
    EDITSERVICES: '[data-action="tool_wsmanager-edit-services"][data-function]',
    EDITDESCRIPTION: '[data-action="tool_wsmanager-edit-description"][data-function]',
    bulkActionsForm: 'form#tool_wsmanager-bulk-actions-form',
    reportWrapper: '[data-region="tool_wsfunction-list-wrapper"]',
    checkedRows: '[data-togglegroup="report-select-all"][data-toggle="slave"]:checked',
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
            showFunctionModalForm(editServices,
                getString('editservices', 'tool_wsmanager'),
                'tool_wsmanager\\form\\function_services_form');
        }

        // Edit description/tags for the function.
        const editDescription = event.target.closest(SELECTORS.EDITDESCRIPTION);
        if (editDescription) {
            event.preventDefault();
            showFunctionModalForm(editDescription,
                getString('editdetails', 'tool_wsmanager'),
                'tool_wsmanager\\form\\edit_function_form');
        }
    });

    const bulkForm = document.querySelector(SELECTORS.bulkActionsForm);
    const reportElement = bulkForm?.closest(SELECTORS.reportWrapper)?.querySelector(reportSelectors.regions.report);
    if (bulkForm && reportElement) {
        const actionSelect = bulkForm.querySelector('select');
        actionSelect.addEventListener('change', event => {
            event.preventDefault();
            const value = event.target.value;
            if (value !== '0') {
                const functionNames = [...reportElement.querySelectorAll(SELECTORS.checkedRows)].map(v => v.value);
                window.console.log('onChange', event.target.value, functionNames);
                event.target.value = '0';
                if (functionNames.length > 0) {
                    performBulkAction(actionSelect, reportElement, value, functionNames);
                }
            }
        });
    }
}

/**
 * Perform bulk action
 *
 * @param {Element} actionSelect
 * @param {Element} reportElement
 * @param {String} actionValue
 * @param {Array} functionNames
 */
async function performBulkAction(actionSelect, reportElement, actionValue, functionNames) {

    var arr = actionValue.match(/^(add|remove)-service-(\d+)$/);
    if (arr) {
        // TODO confirmation?
        const request = {
            methodname: 'tool_wsmanager_add_function_to_service',
            args: {functionnames: functionNames, serviceid: parseInt(arr[2]), action: arr[1]}
        };
        Ajax.call([request])[0]
            .then(() => {
                dispatchEvent(reportEvents.tableReload, {preservePagination: true}, reportElement);
                return null;
            })
            .catch((e) => Notification.exception(e));
    }
    if (actionValue == 'add-tag') {
        showTagsModalForm(
            actionSelect,
            reportElement,
            {functionnames: functionNames.join(','), action: 'add'},
            getString('addtag', 'tool_wsmanager'),
        );
    }
    if (actionValue == 'remove-tag') {
        showTagsModalForm(
            actionSelect,
            reportElement,
            {functionnames: functionNames.join(','), action: 'remove'},
            getString('removetag', 'tool_wsmanager'),
        );
    }
}

/**
 * Show modal form for editing a function
 *
 * @param {Element} targetElement
 * @param {Promise} title
 * @param {String} formClass
 */
function showFunctionModalForm(targetElement, title, formClass) {
    const functionname = targetElement.dataset.function;

    const modalForm = new ModalForm({
        modalConfig: {title},
        formClass,
        args: {functionname},
        saveButtonText: getString('savechanges', 'moodle'),
        returnFocus: targetElement,
    });

    // Reload report when the form is submitted.
    const reportElement = targetElement.closest(reportSelectors.regions.report);
    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
        dispatchEvent(reportEvents.tableReload, {preservePagination: true}, reportElement);
    });

    modalForm.show();
}

/**
 * Show modal form for tags bulk actions
 *
 * @param {Element} actionSelect
 * @param {Element} reportElement
 * @param {Object} args
 * @param {String} title
 */
function showTagsModalForm(actionSelect, reportElement, args, title) {

    const modalForm = new ModalForm({
        modalConfig: {title},
        formClass: 'tool_wsmanager\\form\\change_tags_form',
        args,
        saveButtonText: getString('savechanges', 'moodle'),
        returnFocus: actionSelect,
    });

    // Reload report when the form is submitted.
    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
        dispatchEvent(reportEvents.tableReload, {preservePagination: true}, reportElement);
    });

    modalForm.show();
}