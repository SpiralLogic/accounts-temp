	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 11/07/11
	 * Time: 10:57 AM
	 * To change this template use File | Settings | File Templates.
	 */
	$(function () {
		var btnSend = $("#btnSend").button();
		btnSend.click(function () {
			var data = {
				'user_id':$('[name="user_id"]').val(),
				'message':$('#message').val(),
				'subject':$('#subject').val()
			};
			$.post('#', data, function (data) {
			})
		})
	});
