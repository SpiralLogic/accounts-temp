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
    var last = +i.getAttribute('_last'), left, cur , change , total;
    left = Adv.Forms.getAmount('left_to_allocate', 1);
    cur = Math.min(Adv.Forms.getAmount(i.name), Adv.Forms.getAmount('maxval' + i.name.substr(6), 1), last + left);
    Adv.Forms.priceFormat(i.name, cur, user.pdec);
    change = cur - last;
    total = Adv.Forms.getAmount('total_allocated', 1) + change;
    left -= change;
    Adv.Forms.priceFormat('left_to_allocate', left, user.pdec, 1, 1);
    Adv.Forms.priceFormat('total_allocated', total, user.pdec, 1, 1);
  },
  all:  function (doc) {
    var amount = Adv.Forms.getAmount('amount' + doc), unallocated = Adv.Forms.getAmount('un_allocated' + doc), total = Adv.Forms.getAmount('total_allocated', 1), left = Adv.Forms.getAmount('left_to_allocate', 1);
    total -= (amount - unallocated);
    left += (amount - unallocated);
    amount = unallocated;
    if (left < 0) {
      total += left;
      amount += left;
      left = 0;
    }
    Adv.Forms.priceFormat('amount' + doc, amount, user.pdec);
    Adv.Forms.priceFormat('left_to_allocate', left, user.pdec, 1, 1);
    Adv.Forms.priceFormat('total_allocated', total, user.pdec, 1, 1);
  },
  none: function (doc) {
    var amount = Adv.Forms.getAmount('amount' + doc), left = Adv.Forms.getAmount('left_to_allocate', 1), total = Adv.Forms.getAmount('total_allocated', 1);
    Adv.Forms.priceFormat('left_to_allocate', amount + left, user.pdec, 1, 1);
    Adv.Forms.priceFormat('amount' + doc, 0, user.pdec);
    Adv.Forms.priceFormat('total_allocated', total - amount, user.pdec, 1, 1);
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
                       };
                     },
                     '.allocateNone':function (e) {
                       e.onclick = function () {
                         Adv.alloc.none(this.name.substr(5));
                         return false;
                       };
                     }
                   });
