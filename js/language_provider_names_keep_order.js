/*
 * Copyright (C) 2023 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation in version 3.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

CRM.$(document).ready(function() {
    // Keeps the order of options on submit by moving newly selected option
    // elements to the end of the select element.
    CRM.$('#language_provider_names').on('change', function (e) {
        if (e.added) {
            const $this = CRM.$(this);
            const $option = CRM.$(e.added.element);
            $this.append($option);
        }
    });
});
