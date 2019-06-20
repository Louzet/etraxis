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
 * 'Password' form ('Settings' page).
 */
new Vue({
    el: '#vue-password',

    data: {

        // Form contents.
        values: {},
        errors: {},
    },

    methods: {

        /**
         * Saves new password.
         */
        submit() {

            if (this.values.new !== this.values.confirm) {
                ui.alert(i18n['password.dont_match']);
                return;
            }

            ui.block();

            axios.put(url('/api/my/password'), this.values)
                .then(() => {
                    ui.info(i18n['password.changed'], () => {
                        this.values = {};
                        this.errors = {};
                    });
                })
                .catch(exception => (this.errors = ui.errors(exception)))
                .then(() => ui.unblock());
        },
    },
});
