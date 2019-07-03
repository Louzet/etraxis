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

window.i18n = window.i18n || {};
window.eTraxis = {};

Vue.options.delimiters = ['${', '}'];
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Makes an AJAX call for DataTable component.
 *
 * @param  {string}   url      Absolute URL of the call.
 * @param  {Request}  request  Request from the DataTable component.
 * @param  {function} callback Callback function to process the received data.
 * @return {Promise}  Promise of response.
 */
axios.datatable = (url, request, callback) => {

    let headers = {
        'X-Search': request.search,
        'X-Filter': JSON.stringify(request.filters),
        'X-Sort':   JSON.stringify(request.sorting),
    };

    let params = {
        offset: request.from,
        limit:  request.limit,
    };

    return new Promise((resolve, reject) => {
        axios.get(url, { headers, params })
            .then(response => resolve({
                from:  response.data.from,
                to:    response.data.to,
                total: response.data.total,
                data:  response.data.data.map(entry => callback(entry)),
            }))
            .catch(exception => reject(exception.response.data));
    });
};
