//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

import ui  from 'utilities/ui';
import url from 'utilities/url';

/**
 * 'Forgot password' page.
 */
new Vue({
    el: '#vue-forgot',

    data: {

        // Form contents.
        values: {},
        errors: {},
    },

    methods: {

        /**
         * Submits the form.
         */
        submit() {

            ui.block();

            axios.post(url('/forgot'), this.values)
                .then(() => {
                    ui.info(i18n['password.forgot.email_sent'], () => {
                        location.href = url('/login');
                    });
                })
                .catch(exception => {
                    ui.unblock();
                    this.errors = ui.errors(exception);
                });
        },

        /**
         * Goes back to the login page.
         */
        cancel() {
            location.href = url('/login');
        },
    },
});
