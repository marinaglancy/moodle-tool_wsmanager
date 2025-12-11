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
import Modal from 'core/modal_cancel';

const SELECTORS = {
    EDITSERVICES: '[data-action="tool_wsmanager-edit-services"][data-function]',
    EDITDESCRIPTION: '[data-action="tool_wsmanager-edit-description"][data-function]',
    viewDetails: '[data-action="tool_wsmanager-view-details"][data-function]',
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

        // Edit description/tags for the function.
        const viewDetails = event.target.closest(SELECTORS.viewDetails);
        if (viewDetails) {
            event.preventDefault();
            showFunctionDetails(viewDetails);
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
        // TODO ask for confirmation?
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

/**
 * View function details in a modal
 *
 * @param {Element} targetElement
 */
async function showFunctionDetails(targetElement) {
    const functionname = targetElement.dataset.function;

    const paramsDesc = hightlighInJson(targetElement.dataset.parametersDesc);
    const title1 = await getString('functionparameters', 'tool_wsmanager');
    const returnDesc = hightlighInJson(targetElement.dataset.returnDesc);
    const title2 = await getString('functionreturns', 'tool_wsmanager');

    await Modal.create({
            title: functionname,
            large: true,
            removeOnClose: true,
            returnElement: targetElement,
            show: true,
            body:
            '<p><strong>' + title1 + ':</strong></p>' +
            '<pre>' + paramsDesc + '</pre>' +
            '<p><strong>' + title2 + ':</strong></p>' +
            '<pre>' + returnDesc + '</pre>',
        });
}

/**
 * Highlight keys in a JSON string
 *
 * @param {String} jsonString
 * @returns {String} formatted JSON with highlighted keys
 */
function hightlighInJson(jsonString) {
    if (!jsonString) {
        return '';
    }
    const doHighlight = function(v, parentKey = null) {
        if (typeof v === 'object' && v !== null) {
            const v2 = {};
            for (const key of Object.keys(v)) {
                const newKey = (parentKey === 'keys') ? '<strong class="key">' + key + '</strong>' : key;
                const newValue = key === 'desc' ? '<strong class="desc">' + v[key] + '</strong>' : doHighlight(v[key], key);
                v2[newKey] = newValue;
            }
            return v2;
        }
        return v;
    };

    const value = JSON.parse(jsonString);
    const newValue = doHighlight(value, null);

    return JSON.stringify(newValue, null, 2);
}