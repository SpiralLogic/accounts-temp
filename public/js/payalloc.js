/**********************************************************************
 Copyright (C) Advanced Group PTY LTD
 Released under the terms of the GNU General Public License, GPL,
 as published by the Free Software Foundation, either version 3
 of the License, or (at your option) any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
 ***********************************************************************/

Adv.extend({alloc:{
  focus:function (i) {
    Adv.Forms.saveFocus(i);
    i.setAttribute('_last', Adv.Forms.getAmount(i.name));
  },
  blur: function (i) {
    var change = Adv.Forms.getAmount(i.name);
    if (i.name != 'amount' && i.name != 'charge' && i.name != 'discount') {
      change = Math.min(change, Adv.Forms.getAmount('maxval' + i.name.substr(6), 1));
    }
    Adv.Forms.priceFormat(i.name, change, user.pdec);
    if (i.name != 'amount' && i.name != 'charge') {
      if (change < 0) {
        change = 0;
      }
      change -= i.getAttribute('_last');
      if (i.name == 'discount') {
        change = -change;
      }
      var total = Adv.Forms.getAmount('amount') + change;
      Adv.Forms.priceFormat('amount', total, user.pdec, 0);
    }
  },
  all:  function (doc) {
    var amount = Adv.Forms.getAmount('amount' + doc);
    var unallocated = Adv.Forms.getAmount('un_allocated' + doc);
    var total = Adv.Forms.getAmount('amount');
    var left = 0;
    total -= (amount - unallocated);
    left -= (amount - unallocated);
    amount = unallocated;
    if (left < 0) {
      total += left;
      amount += left;
      left = 0;
    }
    Adv.Forms.priceFormat('amount' + doc, amount, user.pdec);
    Adv.Forms.priceFormat('amount', total, user.pdec);
  },
  none: function (doc) {
    var amount = Adv.Forms.getAmount('amount' + doc);
    var total = Adv.Forms.getAmount('amount');
    Adv.Forms.priceFormat('amount' + doc, 0, user.pdec);
    Adv.Forms.priceFormat('amount', total - amount, user.pdec);
  }
}});
Behaviour.register({
                     '.amount':      function (e) {
                       e.onblur = function () {
                         Adv.alloc.blur(this);
                       };
                       e.onfocus = function () {
                         Adv.alloc.focus(this);
                       };
                     },
                     '.allocateAll': function (e) {
                       e.onclick = function () {
                         Adv.alloc.all(this.name.substr(5));
                         return false;
                       }
                     },
                     '.allocateNone':function (e) {
                       e.onclick = function () {
                         Adv.alloc.none(this.name.substr(5));
                         return false;
                       }
                     }
                   });
