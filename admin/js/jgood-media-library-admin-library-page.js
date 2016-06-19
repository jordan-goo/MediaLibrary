// init our custom media object
var jgoodMedia = wp.media;

(function($, _, Backbone, wp) {
	'use strict';

	/**
	 * custom function to display our custom media frames
	 *
	 * @since    1.0.0
	 */
	jgoodMedia.displayLibraryView = function(attributes) {
		var MediaFrame = jgoodMedia.view.MediaFrame,
			frame;

		if ( ! MediaFrame ) {
			return;
		}

		// if modal is set to true open modal fram instead of standard frame
		if(attributes.modal == true){
			frame = new MediaFrame.JGoodModal( attributes );
		}else{
			frame = new MediaFrame.JGood( attributes );
		}

		delete attributes.frame;

		jgoodMedia.frame = frame;

		return frame;
	};


	/**
	 * Custom attachments model
	 *
	 * @since    1.0.0
	 */
	var jgoodAttachments = jgoodMedia.model.Attachments = wp.media.model.Attachments.extend({
		
		/**
		 * create our own _requery function that calls our custom ajax
		 *
		 * @access private
		 */
		_requery: function( refresh ) {
			var props, folder;
			var model = this;
			if ( this.props.get('query') ) {
				
				// if folder id is not set default to 0
				if(this.props.get('folder') > 0){
					folder = this.props.get('folder');
				}else{
					folder = 0;
				}

				// get history results from ajax
				wp.ajax.send( 'jgood-library-get-history', {
					data : {
						folder_ID: folder
					}
				} ).done( function(result){
					// assign history array to model
					model.props.set({history: result, rand:Math.random()});
					props = model.props.toJSON();
					props.cache = ( true !== refresh );
					model.mirror( jgoodQuery.get( props ) );
				});
			}
		}
		
	});

	/**
	 * create our custom query function that calls our custom attachments model
	 *
	 * @since    1.0.0
	 */
	jgoodMedia.jgoodQuery = function( props ) {
		return new jgoodAttachments( null, {
			props: _.extend( _.defaults( props || {}, { orderby: 'date' } ), { query: true } )
		});
	};

	/**
	 * create our custom query model to use our custom ajax call
	 *
	 * @since    1.0.0
	 */
	var jgoodQuery = jgoodMedia.model.jgoodQuery = wp.media.model.Query.extend({
		
		/**
		 * Overrides Backbone.Collection.sync
		 * Overrides wp.media.model.Attachments.sync
		 *
		 * @param {String} method
		 * @param {Backbone.Model} model
		 * @param {Object} [options={}]
		 * @returns {Promise}
		 */
		sync: function( method, model, options ) {
			var args, fallback, folder;

			// Overload the read method so Attachment.fetch() functions correctly.
			if ( 'read' === method ) {
				options = options || {};
				options.context = this;
				options.data = _.extend( options.data || {}, {
					action:  'jgood-query-attachments',
					post_id: jgoodMedia.model.settings.post.id
				});

				// Clone the args so manipulation is non-destructive.
				args = _.clone( this.args );

				// Determine which page to query.
				if ( -1 !== args.posts_per_page ) {
					args.paged = Math.floor( this.length / args.posts_per_page ) + 1;
				}

				if(folder > 0){
					folder = options.data.query.folder;
				}else{
					folder = 0;
				}
				
				options.data.query = args;
				options.data.folder = folder;
				
				return jgoodMedia.ajax( options );

			// Otherwise, fall back to Backbone.sync()
			} else {
				/**
				 * Call wp.media.model.Attachments.sync or Backbone.sync
				 */
				fallback = jgoodMedia.model.Attachments.prototype.sync ? jgoodMedia.model.Attachments.prototype : Backbone;
				return fallback.sync.apply( this, arguments );
			}
		}
	}, {

		/**
		 * Creates and returns an Attachments Query collection given the properties.
		 *
		 * Caches query objects and reuses where possible.
		 *
		 * @static
		 * @method
		 *
		 * @param {object} [props]
		 * @param {Object} [props.cache=true]   Whether to use the query cache or not.
		 * @param {Object} [props.order]
		 * @param {Object} [props.orderby]
		 * @param {Object} [props.include]
		 * @param {Object} [props.exclude]
		 * @param {Object} [props.s]
		 * @param {Object} [props.post_mime_type]
		 * @param {Object} [props.posts_per_page]
		 * @param {Object} [props.menu_order]
		 * @param {Object} [props.post_parent]
		 * @param {Object} [props.post_status]
		 * @param {Object} [options]
		 *
		 * @returns {wp.media.model.Query} A new Attachments Query collection.
		 */
		get: (function(){
			/**
			 * @static
			 * @type Array
			 */
			var queries = [];

			/**
			 * @returns {Query}
			 */
			return function( props, options ) {
				var args     = {},
					orderby  = jgoodQuery.orderby,
					defaults = jgoodQuery.defaultProps,
					query,
					cache    = !! props.cache || _.isUndefined( props.cache );

				// Remove the `query` property. This isn't linked to a query,
				// this *is* the query.
				delete props.query;
				delete props.cache;

				// Fill default args.
				_.defaults( props, defaults );

				// Normalize the order.
				props.order = props.order.toUpperCase();
				if ( 'DESC' !== props.order && 'ASC' !== props.order ) {
					props.order = defaults.order.toUpperCase();
				}

				// Ensure we have a valid orderby value.
				if ( ! _.contains( orderby.allowed, props.orderby ) ) {
					props.orderby = defaults.orderby;
				}

				_.each( [ 'include', 'exclude' ], function( prop ) {
					if ( props[ prop ] && ! _.isArray( props[ prop ] ) ) {
						props[ prop ] = [ props[ prop ] ];
					}
				} );

				// Generate the query `args` object.
				// Correct any differing property names.
				_.each( props, function( value, prop ) {
					if ( _.isNull( value ) ) {
						return;
					}

					args[ jgoodQuery.propmap[ prop ] || prop ] = value;
				});

				// Fill any other default query args.
				_.defaults( args, jgoodQuery.defaultArgs );

				// `props.orderby` does not always map directly to `args.orderby`.
				// Substitute exceptions specified in orderby.keymap.
				args.orderby = orderby.valuemap[ props.orderby ] || props.orderby;

				// Search the query cache for a matching query.
				if ( cache ) {
					query = _.find( queries, function( query ) {
						return _.isEqual( query.args, args );
					});
				} else {
					queries = [];
				}

				// Otherwise, create a new query and add it to the cache.
				if ( ! query ) {
					query = new jgoodQuery( [], _.extend( options || {}, {
						props: props,
						args:  args
					} ) );
					queries.push( query );
				}

				return query;
			};
		}())
	});

	/**
	 * Custom media frame for main library view
	 *
	 * @since    1.0.0
	 */
	jgoodMedia.view.MediaFrame.JGood = wp.media.view.MediaFrame.Manage.extend({
		/**
		 * @global wp.Uploader
		 */
		initialize: function() {
			var self = this;
			_.defaults( this.options, {
				title:     '',
				modal:     false,
				selection: [],
				library:   {}, // Options hash for the query to the media library.
				multiple:  'add',
				state:     'library',
				uploader:  true,
				mode:      [ 'grid', 'edit' ]
			});

			this.$body = $( document.body );
			this.$window = $( window );
			this.$adminBar = $( '#wpadminbar' );
			this.$window.on( 'scroll resize', _.debounce( _.bind( this.fixPosition, this ), 15 ) );
			$( document ).on( 'click', '.add-new-h2', _.bind( this.addNewClickHandler, this ) );

			// Ensure core and media grid view UI is enabled.
			this.$el.addClass('wp-core-ui');

			// Force the uploader off if the upload limit has been exceeded or
			// if the browser isn't supported.
			if ( wp.Uploader.limitExceeded || ! wp.Uploader.browser.supported ) {
				this.options.uploader = false;
			}

			// Initialize a window-wide uploader.
			if ( this.options.uploader ) {
				var JGoodUploader = this.uploader = new jgoodMedia.view.UploaderWindow({
					controller: this,
					uploader: {
						dropzone:  document.body,
						container: document.body
					}
				}).render();
				this.uploader.ready();
				$('body').append( this.uploader.el );

				// when uploading of item is successful call our custom ajax function to assign folder id
				this.uploader.uploader.success = function(object){
					var attachment_ID = object.id;
					var folder_ID = JGoodUploader.controller.state().get('library').props.get("folder");

					wp.ajax.send( 'jgood-library-create-file', {
						data : {
							attachment_ID: attachment_ID,
							folder_ID: folder_ID
						}
					} ).done( function(result){
						// updates query
						JGoodUploader.controller.state().get('library').props.set({newfolder: result['new_ID'], rand:Math.random()});
					});
				}

				this.options.uploader = false;
			}

			// setup our custom router
			this.gridRouter = new jgoodMedia.view.MediaFrame.JGood.Router();

			// Call 'initialize' directly on the parent class.
			jgoodMedia.view.MediaFrame.prototype.initialize.apply( this, arguments );

			// Append the frame view directly the supplied container.
			this.$el.appendTo( this.options.container );

			this.createStates();
			this.bindRegionModeHandlers();
			this.render();

			// Update the URL when entering search string (at most once per second)
			$( '#media-search-input' ).on( 'input', _.debounce( function(e) {
				var val = $( e.currentTarget ).val(), url = '';
				if ( val ) {
					url += '?search=' + val;
				}
				self.gridRouter.navigate( self.gridRouter.baseUrl( url ) );
			}, 1000 ) );

			// assign our event handlers
			$( document ).on( 'click', '.library-tool.tool-add-folder', _.bind( this.addFolder, this ) );
			$( document ).on( 'click', '.attachment .folder-option-delete', _.bind( this.deleteFolder, this ) );

			$( document ).on( 'click', '.attachment-folder .filename-label', _.bind( this.renameFolder, this ) );

			$( document ).on( 'focusout keypress', '.attachment-folder .filename-input', _.bind( this.saveFolderName, this ) );

			$( document ).on( 'dblclick', '.attachment-folder .thumbnail', _.bind( this.openFolderItem, this ) );
			$( document ).on( 'click', '.media-toolbar .history-item', _.bind( this.openFolderItem, this ) );
		},

		/**
		 * Create the default states for the frame.
		 */
		createStates: function() {
			var options = this.options;

			if ( this.options.states ) {
				return;
			}

			// Add the default states.
			this.states.add([
				new jgoodMedia.controller.Library({
					library:            jgoodMedia.jgoodQuery( options.library ),
					multiple:           options.multiple,
					title:              options.title,
					content:            'browse',
					toolbar:            'select',
					contentUserSetting: false,
					filterable:         'all',
					autoSelect:         false
				})
			]);
		},

		/**
		 * Bind region mode activation events to proper handlers.
		 */
		bindRegionModeHandlers: function() {
			this.on( 'content:create:browse', this.browseContent, this );

			// Handle a frame-level event for editing an attachment.
			this.on( 'edit:attachment', this.selectItem, this );

			this.on( 'select:activate', this.bindKeydown, this );
			this.on( 'select:deactivate', this.unbindKeydown, this );			
		},

		selectItem: function( model ) {
			if(model.attributes.type !== "folder"){
				// Create a new EditAttachment frame, passing along the library and the attachment model.
				wp.media( {
					frame:       'edit-attachments',
					controller:  this,
					library:     this.state().get('library'),
					model:       model
				} );
			}
		},

		// opens folder on double click, or on breadcrumbs click
		openFolderItem: function( event ) {
			
			var targetID = event.currentTarget.id;
			var folderID = targetID.split("-");

			// set new folder id and update query
			this.state().get('library').props.set({ folder: folderID[1], rand:Math.random() });
		},

		// opens folder rename input when folder label is clicked
		renameFolder: function( event ) {
			event.originalEvent.preventDefault();

			var targetID = event.currentTarget.id;
			
			var getID = targetID.split("-");
			
			var inputID = "#filename-input-"+getID[1];
			var labelID = "#"+targetID;

			$(inputID).val($(labelID).html());

			$(labelID).hide();
			$(inputID).show();
			$(inputID).focus();

		},

		// saves new folder name whenver the name input looses focus OR enter key is pressed
		saveFolderName: function( event ) {
			var keycode = (event.keyCode ? event.keyCode : event.which);

			if(typeof keycode == "undefined" || keycode == 0){
				keycode = "focus";
			}

			if(keycode == 13 || keycode == "focus"){
				var library = this.state().get('library');

				var targetID = event.currentTarget.id;
				
				var getID = targetID.split("-");
				
				var labelID = "#filename-"+getID[2];
				var inputID = "#"+targetID;

				wp.ajax.send( 'jgood-library-save-folder-name', {
					data : {
						term_ID: getID[2],
						new_name: $(inputID).val()
					}
				} ).done( function(result){
					// updates query
					library.props.set({ newfolder: result['new_ID'], rand:Math.random() });
				});

				$(labelID).html($(inputID).val());
				$(labelID).show();
				$(inputID).hide();
			}
		},

		// adds new folder to current folders
		addFolder: function(event) {
			var library = this.state().get('library'), folder;

			if(library.props.get("folder") > 0){
				folder = library.props.get("folder");
			}else{
				folder = 0;
			}

			wp.ajax.send( 'jgood-library-add-folder', {
				data : {
					parent_ID: folder
				}
			} ).done( function(result){
				// update query
				library.props.set({ newfolder: result['new_ID'], rand:Math.random() });
			});

			
		},

		// delete folder
		deleteFolder: function(event){
			var library = this.state().get('library');
			var targetID = event.currentTarget.id;
			var folderID = targetID.split("-");

			// show confirm dialog
			// make sure user knows all items in folder will also be deleted
			var check = confirm("Are you sure you want to delete this folder and all of it's contents?");

			if(check){
				wp.ajax.send( 'jgood-library-delete-folder', {
					data : {
						this_ID: folderID[1]
					}
				} ).done( function(result){
					// update query
					library.props.set({ oldfolder: result['old_ID'], rand:Math.random() });
				});
			}
		},


		/**
		 * Create an attachments browser view within the content region.
		 *
		 * @param {Object} contentRegion Basic object with a `view` property, which
		 *                               should be set with the proper region view.
		 * @this wp.media.controller.Region
		 */
		browseContent: function( contentRegion ) {
			var state = this.state();

			// Browse our library of attachments.
			this.browserView = contentRegion.view = new jgoodMedia.view.JGoodAttachmentBrowser({
				controller: this,
				collection: state.get('library'),
				selection:  state.get('selection'),
				model:      state,
				sortable:   state.get('sortable'),
				search:     state.get('searchable'),
				filters:    state.get('filterable'),
				display:    state.get('displaySettings'),
				dragInfo:   state.get('dragInfo'),
				sidebar:    'errors',

				suggestedWidth:  state.get('suggestedWidth'),
				suggestedHeight: state.get('suggestedHeight'),

				AttachmentView: jgoodMedia.view.JGoodAttachment,

				scrollElement: document
			});
			this.browserView.on( 'ready', _.bind( this.bindDeferred, this ) );

			this.errors = wp.Uploader.errors;
			this.sidebarVisibility();
			this.errors.on( 'add remove reset', this.sidebarVisibility, this );
		}
	});

	/**
	 * create our custom modal library
	 *
	 * @since    1.0.0
	 */
	jgoodMedia.view.MediaFrame.JGoodModal = jgoodMedia.view.MediaFrame.Post.extend({
		/**
		 * Create the default states.
		 */
		createStates: function() {
			var options = this.options;

			this.states.add([
				// Main states.
				new jgoodMedia.controller.Library({
					id:         'insert',
					title:      'Insert Media',
					priority:   20,
					toolbar:    'main-insert',
					filterable: 'all',
					library:    jgoodMedia.jgoodQuery( options.library ),
					multiple:   options.multiple ? 'reset' : false,
					editable:   true,

					// If the user isn't allowed to edit fields,
					// can they still edit it locally?
					allowLocalEdits: true,

					// Show the attachment display settings.
					displaySettings: true,
					// Update user settings when users adjust the
					// attachment display settings.
					displayUserSettings: true
				}),

				// Embed states.
				new jgoodMedia.controller.Embed( { metadata: options.metadata } ),

			]);

			// add history item event handle
			$( document ).on( 'click', '.media-toolbar .history-item', _.bind( this.openMenuItem, this ) );
		},

		browseRouter: function( routerView ) {
			routerView.set({
				browse: {
					text:     'mediaLibraryTitle',
					priority: 40
				}
			});
		},

		// open history item folder when history item is clicked
		openMenuItem: function( event ) {
			
			var targetID = event.currentTarget.id;
			var folderID = targetID.split("-");
			// update query
			this.state().get('library').props.set({ folder: folderID[1], rand:Math.random() });

		},

		/**
		 * @param {wp.Backbone.View} view
		 */
		mainInsertToolbar: function( view ) {
			var controller = this;

			this.selectionStatusToolbar( view );

			view.set( 'insert', {
				style:    'primary',
				priority: 80,
				text:     'Insert into page',
				requires: { selection: true },

				/**
				 * @fires wp.media.controller.State#insert
				 */
				click: function() {
					var state = controller.state(),
						selection = state.get('selection');

					controller.close();
					state.trigger( 'insert', selection ).reset();
				}
			});
		},

		browseContent: function( contentRegion ) {
			var state = this.state();

			this.$el.removeClass('hide-toolbar');

			// Browse our library of attachments.
			contentRegion.view = new jgoodMedia.view.JGoodAttachmentBrowser({
				controller: this,
				collection: state.get('library'),
				selection:  state.get('selection'),
				model:      state,
				sortable:   state.get('sortable'),
				search:     state.get('searchable'),
				filters:    state.get('filterable'),
				date:       state.get('date'),
				display:    state.has('display') ? state.get('display') : state.get('displaySettings'),
				dragInfo:   state.get('dragInfo'),
				inserting: true,
				uploading: false,

				idealColumnWidth: state.get('idealColumnWidth'),
				suggestedWidth:   state.get('suggestedWidth'),
				suggestedHeight:  state.get('suggestedHeight'),

				AttachmentView: jgoodMedia.view.JGoodAttachment
			});
		}
	});

	/**
	 * create our custom attachment view
	 *
	 * @since    1.0.0
	 */
	jgoodMedia.view.JGoodAttachment = wp.media.view.Attachment.extend({
		// assign our custom template to attachments
		template:  jgoodMedia.template('jgood-attachment'),

		// assign event handlers
		events: {
			'click .js--select-attachment':   'toggleSelectionHandler',
			'click .js--select-folder':       'toggleFolderHandler',
			'change [data-setting]':          'updateSetting',
			'change [data-setting] input':    'updateSetting',
			'change [data-setting] select':   'updateSetting',
			'change [data-setting] textarea': 'updateSetting',
			'click .close':                   'removeFromLibrary',
			'click .check':                   'checkClickHandler',
			'click a':                        'preventDefault',
			'keydown .close':                 'removeFromLibrary',
			'keydown':                        'toggleSelectionHandler'
		},
		
		/**
		 * custom event to browse folders for modal view
		 *
		 * @since    1.0.0
		 */
		toggleFolderHandler: function( event ) {
			var method;

			if(this.controller.state().id === "insert"){
				var targetID = event.currentTarget.id;
				var folderID = targetID.split("-");
				// set new folder id and update query
				this.controller.state().get('library').props.set({ folder: folderID[1], rand:Math.random() });				
			}else{
				// Don't do anything inside inputs.
				if ( 'INPUT' === event.target.nodeName ) {
					return;
				}

				// Catch arrow events
				if ( 37 === event.keyCode || 38 === event.keyCode || 39 === event.keyCode || 40 === event.keyCode ) {
					this.controller.trigger( 'attachment:keydown:arrow', event );
					return;
				}

				// Catch enter and space events
				if ( 'keydown' === event.type && 13 !== event.keyCode && 32 !== event.keyCode ) {
					return;
				}

				event.preventDefault();

				// In the grid view, bubble up an edit:attachment event to the controller.
				if ( this.controller.isModeActive( 'grid' ) ) {
					if ( this.controller.isModeActive( 'edit' ) ) {
						// Pass the current target to restore focus when closing
						this.controller.trigger( 'edit:attachment', this.model, event.currentTarget );
						return;
					}

					if ( this.controller.isModeActive( 'select' ) ) {
						method = 'toggle';
					}
				}

				if ( event.shiftKey ) {
					method = 'between';
				} else if ( event.ctrlKey || event.metaKey ) {
					method = 'toggle';
				}

				this.toggleSelection({
					method: method
				});

				this.controller.trigger( 'selection:toggle' );
			}
		}
	});

	/**
	 * custom attachmentsbrowser view
	 *
	 * @since    1.0.0
	 */
	jgoodMedia.view.JGoodAttachmentBrowser = wp.media.view.AttachmentsBrowser.extend({
		initialize: function() {
			_.defaults( this.options, {
				filters: false,
				search:  true,
				date:    true,
				display: false,
				sidebar: true,
				inserting: false,
				uploading: true,
				AttachmentView: jgoodMedia.view.Attachment.Library
			});

			this.listenTo( this.controller, 'toggle:upload:attachment', _.bind( this.toggleUploader, this ) );
			
			this.controller.on( 'edit:selection', this.editSelection );
			this.createToolbar();

			if(!this.options.inserting){
				// if standard library view then insert library features
				this.displayLibraryFeatures();
			}else{
				// if modal view then insert modal features
				this.displayModalFeatures();
			}

			if ( this.options.sidebar ) {
				this.createSidebar();
			}
			
			this.createUploader();

			this.createAttachments();
			this.updateContent();


			if ( ! this.options.sidebar || 'errors' === this.options.sidebar ) {
				this.$el.addClass( 'hide-sidebar' );

				if ( 'errors' === this.options.sidebar ) {
					this.$el.addClass( 'sidebar-for-errors' );
				}
			}



			this.collection.on( 'add remove reset', this.updateContent, this );
			
			if(!this.options.inserting){
				// when standard library query changes then update toolbar
				this.controller.state().get("library").props.on( 'change', this.displayLibraryFeatures, this );
			}else{
				// when modal query changes then update modal toolbar
				this.controller.state().get("library").props.on( 'change', this.displayModalFeatures, this );
			}
			
			
		},

		updateContent: function() {
			var view = this,
				noItemsView;

			if ( this.controller.isModeActive( 'grid' ) || !this.options.uploading ) {
				noItemsView = view.attachmentsNoResults;
			} else {
				noItemsView = view.uploader;
			}

			if ( ! this.collection.length ) {
				this.toolbar.get( 'spinner' ).show();
				this.dfd = this.collection.more().done( function() {
					if ( ! view.collection.length ) {
						noItemsView.$el.removeClass( 'hidden' );
					} else {
						noItemsView.$el.addClass( 'hidden' );
					}
					view.toolbar.get( 'spinner' ).hide();
				} );
			} else {
				noItemsView.$el.addClass( 'hidden' );
				view.toolbar.get( 'spinner' ).hide();
			}


		},

		createAttachments: function() {
			this.attachments = new jgoodMedia.view.Attachments({
				controller:           this.controller,
				collection:           this.collection,
				selection:            this.options.selection,
				model:                this.model,
				sortable:             this.options.sortable,
				scrollElement:        this.options.scrollElement,
				idealColumnWidth:     this.options.idealColumnWidth,

				// The single `Attachment` view to be used in the `Attachments` view.
				AttachmentView: this.options.AttachmentView
			});

			// Add keydown listener to the instance of the Attachments view
			this.attachments.listenTo( this.controller, 'attachment:keydown:arrow',     this.attachments.arrowEvent );
			this.attachments.listenTo( this.controller, 'attachment:details:shift-tab', this.attachments.restoreFocus );

			this.views.add( this.attachments );


			if ( this.controller.isModeActive( 'grid' ) || !this.options.uploading ) {
				this.attachmentsNoResults = new jgoodMedia.View({
					controller: this.controller,
					tagName: 'p'
				});

				this.attachmentsNoResults.$el.addClass( 'hidden no-media attachments' );
				this.attachmentsNoResults.$el.html( 'No media attachments found.' );

				this.views.add( this.attachmentsNoResults );
			}
		},

		displayLibraryFeatures: function(){
			// if toolbar already exists then remove it first
			if(typeof this.libraryToolbar != "undefined"){
				this.libraryToolbar.remove();
			}
			
			var folder, history;

			// get current folder from library proerties
			if(typeof this.controller.state().get("library").props.get("folder") != "undefined"){
				folder = this.controller.state().get("library").props.get("folder");
			}else{
				// default to 0
				folder = 0;
			}

			// get current history array from library proerties
			if(typeof this.controller.state().get("library").props.get("history") != "undefined"){
				history = this.controller.state().get("library").props.get("history");
			}else{
				// default to base level
				history = Array( {'id': 0, 'name': 'Library'} );
			}

			// display breadcrumbs and add folder button
			if(history.length > 0){
				var isDisabled, name, url;

				var toolbarOptions = {
					controller: this.controller
				};

				toolbarOptions.className = 'media-toolbar wp-filter library-tools';

				/**
				* @member {wp.media.view.Toolbar}
				*/
				this.libraryToolbar = new jgoodMedia.view.Toolbar( toolbarOptions );

				this.views.add( this.libraryToolbar );

				for (var i = 0; i < history.length; ++i) {
					isDisabled = false;
					name = history[i]['name'];
					url = '#';

					if(history[i]['id'] == folder){
						isDisabled = true;
					}
					// breadcrumb item
					this.libraryToolbar.set( 'history-'+history[i]['id'], new jgoodMedia.view.Button({
						attributes: { href: url },
						className:  'media-button history-item',
						id: 'history-'+history[i]['id'],
						text:    name,
						style:    '',
						size:     'small',
						disabled: isDisabled
					}) );
				}

				// add folder button
				this.libraryToolbar.set( 'tool-add-folder', new jgoodMedia.view.Button({
					attributes: { href: '#' },
					className:  'media-button library-tool tool-add-folder',
					text:    'New Folder',
					style:    '',
					size:     'large',
					disabled: false
				}) );
			}
		},

		displayModalFeatures: function(){
			// if this toolbar exists then remove it first
			if(typeof this.libraryToolbar != "undefined"){
				this.libraryToolbar.remove();
			}

			var folder, history;

			// get current folder from library proerties
			if(typeof this.controller.state().get("library").props.get("folder") != "undefined"){
				folder = this.controller.state().get("library").props.get("folder");
			}else{
				folder = 0;
			}

			// get current history from library proerties
			if(typeof this.controller.state().get("library").props.get("history") != "undefined"){
				history = this.controller.state().get("library").props.get("history");
			}else{
				history = Array( {'id': 0, 'name': 'Library'} );
			}

			// display breadcrumbs
			if(history.length > 0){
				var isDisabled, name, url;

				var toolbarOptions = {
					controller: this.controller
				};

				toolbarOptions.className = 'media-toolbar wp-filter insert-tools';

				/**
				* @member {wp.media.view.Toolbar}
				*/
				this.libraryToolbar = new jgoodMedia.view.Toolbar( toolbarOptions );

				this.views.add( this.libraryToolbar );

				for (var i = 0; i < history.length; ++i) {
					isDisabled = false;
					name = history[i]['name'];
					url = '#';

					if(history[i]['id'] == folder){
						isDisabled = true;
					}

					this.libraryToolbar.set( 'history-'+history[i]['id'], new jgoodMedia.view.Button({
						attributes: { href: url },
						className:  'media-button history-item',
						id: 'history-'+history[i]['id'],
						text:    name,
						style:    '',
						size:     'small',
						disabled: isDisabled
					}) );
				}
			}
		}
	});

	/**
	 * custom router to make sure we stay on our custom media library page
	 *
	 * @since    1.0.0
	 */
	jgoodMedia.view.MediaFrame.JGood.Router = wp.media.view.MediaFrame.Manage.Router.extend({
		routes: {
			'upload.php?item=:slug&page=jgood-media-library-page':    'showItem',
			'upload.php?search=:query&page=jgood-media-library-page': 'search'
		},

		// Map routes against the page URL
		baseUrl: function( url ) {
			if(url){
				return 'upload.php' + url + '&page=jgood-media-library-page';
			}else{
				return 'upload.php?page=jgood-media-library-page';
			}
		}
	});
	

}(jQuery, _, Backbone, wp));
