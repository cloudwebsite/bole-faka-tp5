var MyModal = (function() {
	function modal(fn) {
		this.fn = fn; //点击确定后的回调函数
		this._addClickListen();
	}
	modal.prototype = {
		show: function() {
			$('.m-modal').fadeIn(100);
			$('.m-modal').children('.m-modal-dialog').animate({
				"margin-top": "180px"
			}, 250);
		},
		_addClickListen: function() {
			var that = this;
			$(".m-modal").find('*').on("click", function(event) {
				event.stopPropagation(); //阻止事件冒泡
			});
			$(".m-modal,.m-modal-close,.m-btn-cancel").on("click", function(event) {
				that.hide();
			});
			$(".m-btn-sure").on("click", function(event) {
				that.fn();
				that.hide();
			});
		},
		hide: function() {
			var $modal = $('.m-modal');
			$modal.children('.m-modal-dialog').animate({
				"margin-top": "-100%"
			}, 500);
			$modal.fadeOut(100);
		}

	};
	return {
		modal: modal
	}
})();


var MyModal2 = (function() {
	function modal(fn) {
		this.fn = fn; //点击确定后的回调函数
		this._addClickListen();
	}
	modal.prototype = {
		show: function() {
			$('.m-modal2').fadeIn(100);
			$('.m-modal2').children('.m-modal-dialog2').animate({
				"margin-top": "150px"
			}, 250);
		},
		_addClickListen: function() {
			var that = this;
			$(".m-modal2").find('*').on("click", function(event) {
				event.stopPropagation(); //阻止事件冒泡
			});
			$(".m-modal2,.m-modal-close,.m-btn-cancel").on("click", function(event) {
				that.hide();
			});
			$(".m-btn-sure").on("click", function(event) {
				that.fn();
				that.hide();
			});
		},
		hide: function() {
			var $modal = $('.m-modal2');
			$modal.children('.m-modal-dialog2').animate({
				"margin-top": "-100%"
			}, 500);
			$modal.fadeOut(100);
		}

	};
	return {
		modal: modal
	}
})();
var MyModal3 = (function() {
	function modal(fn) {
		this.fn = fn; //点击确定后的回调函数
		this._addClickListen();
	}
	modal.prototype = {
			show: function() {
		$('.m-modal3').fadeIn(100);
		$('.m-modal3').children('.m-modal-dialog3').animate({
			"margin-top": "150px"
		}, 250);
	},
	_addClickListen: function() {
		var that = this;
		$(".m-modal3").find('*').on("click", function(event) {
			event.stopPropagation(); //阻止事件冒泡
		});
		$(".m-modal3,.m-modal-close,.m-btn-cancel").on("click", function(event) {
			that.hide();
		});
		$(".m-btn-sure").on("click", function(event) {
			that.fn();
			that.hide();
		});
	},
	hide: function() {
		var $modal = $('.m-modal3');
		$modal.children('.m-modal-dialog3').animate({
			"margin-top": "-100%"
		}, 500);
		$modal.fadeOut(100);
	}
	
	};
	return {
		modal: modal
	}
})();