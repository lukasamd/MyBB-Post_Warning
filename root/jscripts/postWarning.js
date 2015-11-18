/**
 * This file is part of Post Warning plugin for MyBB.
 * Copyright (C) Lukasz Tkacz <lukasamd@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

$.fn.preBind = function(type, data, fn) {
  this.each(function() {
    var $this = $(this);

    $this.bind(type, data, fn);

    var currentBindings = $._data($this[0], 'events');
    if ($.isArray(currentBindings[type])) {
      currentBindings[type].unshift(currentBindings[type].pop());
    }
  });

  return this;
};	
	
	
$(document).ready(function() {
    $('#quick_reply_submit').preBind('click', function(e) {
     
        var post_body = $('#quick_reply_form').serialize();
        $.ajax( {
        	url: 'xmlhttp.php?postwarning=1',
        	type: 'post',
            async: false,
        	data: post_body,
        	dataType: 'html'
        }).done(function(data, e) {
            if (data == 1) {
                e.stopImmediatePropagation();
            }
        });
    });
});