/*global jQuery, ace*/
(function($) {
	// Plugin interface
	$.fn.wpized_post_custom_content = function(options) {
		var settings = $.extend({
			'row'          : 'li',
			'add_row'      : '#add-custom-content',
			'help_message' : '',
			'bind_remove'  : function($el) {
				$('.remove-custom-content', $el).on('click', function(e) {
					var $row = $(this).closest(settings.row);
					$row.fadeOut(500, function() {
						$row.remove();
					});

					e.preventDefault();
					return false;
				});
			},
			'bind_ace'     : function($el) {
				var index           = $el.attr('data-index'),
						$wrapper        = $('.ace-editor-wrapper', $el),
						$textarea       = $('textarea', $wrapper),
						$editor_element = $('<div class="ace-editor-content" id="ace-editor-content-' + index + '" />'),
						editor,
						editor_session;

				$wrapper.append($editor_element);
				editor = ace.edit( $editor_element.attr('id') );
				$('#title').focus(); // Ensures the post/page title field is given focus on load
				editor.setPrintMarginColumn(9999);
				editor_session = editor.getSession();
				editor_session.setValue($textarea.val());
				editor_session.setMode('ace/mode/html');
				editor_session.on('change', function() {
					$textarea.val(editor_session.getValue());
				});
				$textarea.hide();
			}
		}, options);

		return this.each(function() {
			var $container = $(this),
					$metabox   = $container.parent(),
					$add       = $(settings.add_row, $metabox),
					$row       = $(settings.row, $container),
					$row_tpl   = $(settings.row + ':first', $container).clone(),
					help_msg   = '<p class="help-message">' + settings.help_message + '<p>';

			$row.each(function() {
				settings.bind_ace($(this)); // Enable ace editor
				settings.bind_remove($(this)); // Remove row handler
			});

			// Remove ace editor from template
			$('.ace-editor-content', $row_tpl).remove();

			// Inject help message into thead
			$metabox.prepend(help_msg);

			// Makes rows sortable
			$container.sortable({
				placeholder : 'ui-state-highlight',
				handle      : '.hndle'
			});

			// Add new row handler
			$add.on('click', function(e) {
				var $new_row = $row_tpl.clone(),
						index = 0;

				$(settings.row, $container).each(function() {
					index = Math.max( index, $(this).data('index') );
				});
				index += 1;
				$new_row.attr('data-index', index);

				// Clear content on new row and increase the index
				$('[name^="' + settings.field_render + '["]', $new_row)
					.attr('name', settings.field_render + '[' + index + ']')
					.attr('id', function() {
						return settings.field_render + '_' + index + '_' + this.value;
					});
				$('[name^="' + settings.field_content + '["]', $new_row)
					.attr('name', settings.field_content + '[' + index + ']')
					.attr('id', settings.field_content + '_' + index)
					.val('').show();
				$('.shortcode', $new_row).text( '[' + settings.shortcode_tag + ' id=' + (index+1) + ']' );
				$('.row-index', $new_row).text(index+1);

				// Inject into the list
				$container.append($new_row);

				// Ace editor for new row
				settings.bind_ace($new_row);

				// Remove handler
				settings.bind_remove($new_row);

				e.preventDefault();
				return false;
			});
		});
	};

	$(function() {
		$('#custom-content-list').wpized_post_custom_content({
			help_message  : wpized_post_custom_content_i18n.help_message,
			field_render  : wpized_post_custom_content_i18n.field_render,
			field_content : wpized_post_custom_content_i18n.field_content,
			shortcode_tag : wpized_post_custom_content_i18n.shortcode_tag
		});
	});
}(jQuery));
