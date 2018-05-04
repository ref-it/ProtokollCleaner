// this following widget is under MIT License https://tldrlegal.com/license/mit-license#fulltext
// found on http://jqueryui.com/autocomplete/#combobox
jQuery.ui.autocomplete.prototype._resizeMenu = function () {
	  var ul = this.menu.element;
	  ul.outerWidth(this.element.outerWidth());
}

$.widget( "custom.combobox", {
	_create: function() {
          this.element.hide();
          this._createAutocomplete();
          this._createShowAllButton();
	},
   
	_createAutocomplete: function() {
		this.classrenamedone = false;
		var selected = this.element.children( ":selected" ),
		value = selected.val() ? selected.text() : "";
   
		this.input = $( "<input>" )
	      	.addClass( "form-control" )
	        .insertBefore( this.element )
	        .val( value )
	        .attr( "title", "" )
	        .addClass( "custom-combobox-input ui-widget ui-widget-content ui-state-default ui-corner-left" )
	        .autocomplete({
				delay: 0,
				minLength: 0,
				classes: {
					"ui-autocomplete": "silmph_top_autocomplete_resort list-group mt-1 mb-1",
				},
				source: $.proxy( this, "_source" )
	        });
     
          this._on( this.input, {
        	  autocompleteselect: function( event, ui ) {
        		  ui.item.option.selected = true;
        		  this._trigger( "select", event, {
        			  item: ui.item.option
        		  });
        	  },

        	  autocompletechange: "_removeIfInvalid"
          });
	},
   
	_createShowAllButton: function() {
		var input = this.input,
		wasOpen = false;
          
		input.on('focusin', function(){
			input.autocomplete( "search", "" );
			input.select();
		});
      
		this.element.parent().find( '.btnselect_link' )
			.attr( "title", "Show All Items" )
			.on( "mousedown", function() {
				wasOpen = input.autocomplete( "widget" ).is( ":visible" );
			})
			.on( "click", function() {
				input.trigger( "focus" );
   
				// Close if already visible
				if ( wasOpen ) {
					return;
				}
   
				// Pass empty string as value to search for, displaying all results
				input.autocomplete( "search", "" );
			});
	},
   
	_source: function( request, response ) {
		var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
		response( this.element.children( "option" ).map(function() {
			var text = $( this ).text();
			if ( this.value && ( !request.term || matcher.test(text) ) )
				return {
					label: text,
					value: text,
					option: this
				};
		}) );
	},
    
	_removeIfInvalid: function( event, ui ) {
		// Selected an item, nothing to do
		if ( ui.item ) {
			return;
		}
   
		// Search for a match (case-insensitive)
		var value = this.input.val(),
		valueLowerCase = value.toLowerCase(),
        valid = false;
		this.element.children( "option" ).each(function() {
			if ( $( this ).text().toLowerCase() === valueLowerCase ) {
				this.selected = valid = true;
				return false;
			}
		});
   
		// Found a match, nothing to do
		if ( valid ) {
            return;
		}
   
		// Remove invalid value
		this.input
	        .val( "" )
	        .attr( "title", value + " didn't match any item" )
	    this.element.val( "" );
		this._delay(function() {
			this.input.attr( "title", "" );
		}, 2500 );
		this.input.autocomplete( "instance" ).term = "";
	},
   
	_destroy: function() {
		this.wrapper.remove();
		this.element.show();
	}
});
