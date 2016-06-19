<?php



/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       http://jgoodesign@gmail.com
 * @since      1.0.0
 *
 * @package    JGood_Media_Library
 * @subpackage JGood_Media_Library/admin
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    JGood_Media_Library
 * @subpackage JGood_Media_Library/admin
 * @author     Jordan Good <jgoodesign@gmail.com>
 */
class JGood_Media_Library_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $jgood_media_library    The ID of this plugin.
	 */
	private $jgood_media_library;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @var      string    $jgood_media_library       The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $jgood_media_library, $version ) {

		$this->jgood_media_library = $jgood_media_library;
		$this->version = $version;

		// include admin display file to access html functions
		require_once plugin_dir_path( __FILE__ ) . 'partials/jgood-media-library-admin-display.php';
	}

	/**
	 * Register the stylesheets for the Dashboard.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		// enqueue our custom css
		wp_enqueue_style( $this->jgood_media_library, plugin_dir_url( __FILE__ ) . 'css/jgood-media-library-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the dashboard.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		// load wp default media scripts
		wp_enqueue_media();
		wp_enqueue_script( 'media-grid' );
		wp_enqueue_script( 'media' );
		wp_localize_script( 'media-grid', '_wpMediaGridSettings', array(
			'adminUrl' => parse_url( self_admin_url(), PHP_URL_PATH ),
		) );

		// enqueue our custom scripts
		wp_enqueue_script( 'library-page-script', plugin_dir_url( __FILE__ ) . 'js/jgood-media-library-admin-library-page.js', array( 'underscore', 'backbone', 'media-grid', 'media-models', 'media-views', 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->jgood_media_library, plugin_dir_url( __FILE__ ) . 'js/jgood-media-library-admin.js', array( 'library-page-script' ), $this->version, false );
		
	}

	/**
	 * Ajax Function to get current folder history.
	 * used to display breadcrumbs
	 *
	 * @since    1.0.0
	 */
	public function jgood_media_ajax_get_history(){
		$return = array();

		// get current folder id from ajax request
		$folderID = (int)$_REQUEST['folder_ID'];

		// loop through attachment categories until there are no more parent terms
		while(is_array(term_exists($folderID, 'jgood_media_library_category'))){
			// term exists so get term details
			$term = get_terms(array('jgood_media_library_category'), array('hide_empty'=>false, 'include' => array($folderID)));

			// assign term details to return array
			$return[] = array('id'=>$term[0]->term_id, 'name'=>$term[0]->name);

			// if term has no parent break out of loop
			if($term[0]->parent == 0){
				break;
			}else{
			// set id to parent id to continue loop
				$folderID = (int)$term[0]->parent;
			}
		}

		// add base history item to every call
		// makes sure the base link always exists
		$return[] = array(
			'id' => 0,
			'name' => 'Library'
		);

		// reverse array so it appears in a logical order
		$return = array_reverse($return);

		// send json array of result
		wp_send_json_success( $return );
		wp_die();
	}

	/**
	 * add new media button to edit page
	 * used to access our custom media library modal
	 *
	 * @since    1.0.0
	 */
	public function jgood_add_media_button(){
		// set media icon img
		$img = '<span class="wp-media-buttons-icon"></span> ';

		// print link
		printf( '<a href="#" id="%s" class="button jgood-insert-media add_media" data-editor="%s" title="%s">%s</a>',
			'jgood-insert-media-button',
			'content',
			esc_attr__( 'JGood Media Library' ),
			$img . __( 'JGood Media Library' )
		);
	}

	/**
	 * add new media menu item to wp dashboard
	 * used to access our custom media library
	 *
	 * @since    1.0.0
	 */
	public function jgood_media_menu(){
		add_submenu_page('upload.php', 'JGood Media Library', 'JGood Media Library', 'upload_files', 'jgood-media-library-page', 'jgood_display_media_library');
	}
	
	/**
	 * creates our new underscore templates to be used with our custom media views
	 * inserts into admin footer with the rest of the underscore templates
	 *
	 * @since    1.0.0
	 */
	public function jgood_media_templates() {
		//template: jgood-attachment 
		?>
			<script type="text/html" id="tmpl-jgood-attachment">
			<# if ( 'folder' === data.type ) { #>
				<button id="removeID-{{ data.id }}" class="dashicons folder-option folder-option-delete"></button>
				<div id="folderID-{{ data.id }}" class="attachment-preview attachment-folder folder-closed js--select-folder type-{{ data.type }} subtype-{{ data.subtype }} {{ data.orientation }}">
					<div id="thumbnailID-{{ data.id }}" class="thumbnail">
					</div>
					<div class="filename">
						<div id="filename-{{ data.id }}" class="filename-label">{{ data.title }}</div>
						<input class="filename-input" id="filename-input-{{ data.id }}" name="filename-input-{{ data.id }}" value="{{ data.title }}" type="text" />
					</div>
				</div>
			<# } else { #>
				<div class="attachment-preview ui-sortable js--select-attachment type-{{ data.type }} subtype-{{ data.subtype }} {{ data.orientation }}">
					<div class="thumbnail">
						<# if ( data.uploading ) { #>
							<div class="media-progress-bar"><div style="width: {{ data.percent }}%"></div></div>
						<# } else if ( 'image' === data.type && data.sizes ) { #>
							<div class="centered">
								<img src="{{ data.size.url }}" draggable="true" alt="" />
							</div>
							<div class="filename">
								<div>{{ data.filename }}</div>
							</div>
						<# } else { #>
							<div class="centered">
								<# if ( data.image && data.image.src && data.image.src !== data.icon ) { #>
									<img src="{{ data.image.src }}" class="thumbnail" draggable="true" />
								<# } else { #>
									<img src="{{ data.icon }}" class="icon" draggable="true" />
								<# } #>
							</div>
							<div class="filename">
								<div>{{ data.filename }}</div>
							</div>
						<# } #>
					</div>
					<# if ( data.buttons.close ) { #>
						<a class="close media-modal-icon" href="#" title="<?php esc_attr_e('Remove'); ?>"></a>
					<# } #>
				</div>
			<# } #>
				<# if ( data.buttons.check ) { #>
					<a class="check" href="#" title="<?php esc_attr_e('Deselect'); ?>" tabindex="-1"><div class="media-modal-icon"></div></a>
				<# } #>
				<#
				var maybeReadOnly = data.can.save || data.allowLocalEdits ? '' : 'readonly';
				if ( data.describe ) {
					if ( 'image' === data.type ) { #>
						<input type="text" value="{{ data.caption }}" class="describe" data-setting="caption"
							placeholder="<?php esc_attr_e('Caption this image&hellip;'); ?>" {{ maybeReadOnly }} />
					<# } else { #>
						<input type="text" value="{{ data.title }}" class="describe" data-setting="title"
							<# if ( 'video' === data.type ) { #>
								placeholder="<?php esc_attr_e('Describe this video&hellip;'); ?>"
							<# } else if ( 'audio' === data.type ) { #>
								placeholder="<?php esc_attr_e('Describe this audio file&hellip;'); ?>"
							<# } else { #>
								placeholder="<?php esc_attr_e('Describe this media file&hellip;'); ?>"
							<# } #> {{ maybeReadOnly }} />
					<# }
				} #>
			</script>
		<?php 
	}

	/**
	 * Ajax Function to query attachments
	 * returns valid attachments and folders for viewing
	 *
	 * @since    1.0.0
	 */
	public function jgood_media_ajax_query_attachments() {

		// send error if user is not allowed to see page
	    if ( ! current_user_can( 'upload_files' ) )
			wp_send_json_error();

		// setup attachment query
		$query = isset( $_REQUEST['query'] ) ? (array) $_REQUEST['query'] : array();
		$query = array_intersect_key( $query, array_flip( array(
			's', 'order', 'orderby', 'posts_per_page', 'paged', 'post_mime_type',
			'post_parent', 'post__in', 'post__not_in', 'year', 'monthnum'
		) ) );

		$query['post_type'] = 'attachment';
		if ( MEDIA_TRASH
			&& ! empty( $_REQUEST['query']['post_status'] )
			&& 'trash' === $_REQUEST['query']['post_status'] ) {
			$query['post_status'] = 'trash';
		} else {
			$query['post_status'] = 'inherit';
		}

		if ( current_user_can( get_post_type_object( 'attachment' )->cap->read_private_posts ) )
			$query['post_status'] .= ',private';

		$query = apply_filters( 'ajax_query_attachments_args', $query );
		$query = new WP_Query( $query );

		// retrieve current folder id from ajax request
		if(isset($_REQUEST['query']['folder'])){
			$folder = $_REQUEST['query']['folder'];
		}else{
		// if folder id is not set default to 0
			$folder = 0;
		}

		// get all folders that have our current folder as a parent
		$tags = get_terms( array('jgood_media_library_category'), array('hide_empty' => false, 'parent' => $folder) );

		// loop through all attachments and check if they belong in our folder
		foreach($query->posts as $id=>$postObject){
			// get folder term for this attachment
			$terms = wp_get_post_terms( $postObject->ID, 'jgood_media_library_category' );

			if(isset($terms[0])){
				// if attachment has a folder assigned but is not our current folder remove it from the results
				if($terms[0]->term_id != $folder){
					unset($query->posts[$id]);
				}
			}else if($folder != 0){
			// if attachment has no folder assigned only remove it from results if we are not at the top level view
				unset($query->posts[$id]);
			}
		}

		// merge attachments and folders into one return array
		$items_array = array_merge($query->posts, $tags);

		// pepare data object from results to send to js scripts
		$posts = array_map( array($this,'jgood_media_prepare_attachment_for_js'), $items_array );
		$posts = array_filter( $posts );

		// sort results
		usort($posts, array($this,'sort_type') );

		// return all folders and attachments to be displayed
		wp_send_json_success( $posts );
		wp_die();
	}

	/**
	 * sort function to force folders to appear first
	 *
	 * @since    1.0.0
	 */
	function sort_type($a,$b) {
          return strcmp($b['type'],$a['type']);
    }

    /**
	 * Ajax Function to add a new folder
	 *
	 * @since    1.0.0
	 */
	public function jgood_media_ajax_add_folder(){
		
		// get current folder view from ajax request and set it as the parent for our new folder
		$args = array(
			'parent' => $_REQUEST['parent_ID']
		);

		// set default name of new folder
		$defaultName = "New Folder";

		// check if default name has already been taken by another term
		if(is_array(term_exists($defaultName, 'jgood_media_library_category'))){
			
			// if default name is already taken append a number on the end until the term is available
			$nameAppendage = 1;
			$defaultName .= " ".$nameAppendage;
			while(is_array(term_exists($defaultName, 'jgood_media_library_category'))){
				$nameAppendage++;
				$defaultName = "New Folder";
				$defaultName .= " ".$nameAppendage;
			}
		}

		// create our new folder term
		$newterm = wp_insert_term( $defaultName, 'jgood_media_library_category', $args );

		// send newly created folder id as a result
		wp_send_json_success( array('new_ID'=>$newterm['term_id']) );
		wp_die();
	}

	/**
	 * Ajax Function to delete a folder
	 *
	 * @since    1.0.0
	 */
	public function jgood_media_ajax_delete_folder(){
		$query = array();
		$deleteArray = array();

		// initialize our attachment query
		$query['post_type'] = 'attachment';
		$query['post_status'] = 'inherit';
		// set posts per page incredibly high to make sure we grab all attachments needed
		$query['posts_per_page'] = 99999999;

		// create attachment query
		$query = new WP_Query( $query );

		// get all folders
		$tags = get_terms( array('jgood_media_library_category'), array('hide_empty' => false) );

		// get the initial folder to be deleted from the ajax request and insert it into our delete array
		$deleteArray['terms'][$_REQUEST['this_ID']] = false;

		// loop through all folders
		foreach($tags as $num=>$term){
			// if folder is already in our delete array or if folder is a child of an item in our delete array add it to delete array
			if(array_key_exists($term->term_id, $deleteArray['terms']) || array_key_exists($term->parent, $deleteArray['terms'])){
				$deleteArray['terms'][$term->term_id] = false;
				// once we add a folder to be deleted loop through attachments and add all related attachments to be deleted
				foreach($query->posts as $id=>$postObject){
					$terms = wp_get_post_terms( $postObject->ID, 'jgood_media_library_category' );

					if(isset($terms[0])){
						if($terms[0]->term_id == $term->term_id){
							$deleteArray['posts'][$postObject->ID] = false;
						}
					}
				}
			}
		}

		// loop through delete array and delete all items
		foreach ($deleteArray as $type => $ids) {
			if($type == "posts"){
				foreach ($ids as $id => $deleted) {
					if($deleted === false){
						// delete attachment
						wp_delete_attachment( $id );
						$deleteArray['posts'][$id] = true;
					}
				}
				
			}
			else if($type == "terms"){
				foreach ($ids as $id => $deleted) {
					if($deleted === false){
						// delete folder
						wp_delete_term( $id, 'jgood_media_library_category' );
						$deleteArray['terms'][$id] = true;
					}
				}
				
			}
		}

		// send id of deleted folder as result
		wp_send_json_success( array('old_ID'=>$_REQUEST['this_ID']) );
		wp_die();
	}

	/**
	 * Ajax Function to save a folder name change
	 *
	 * @since    1.0.0
	 */
	public function jgood_media_ajax_save_folder_name(){
		// generate new slug from new name
		$new_slug = sanitize_title($_REQUEST['new_name']);
		
		// update folder name and slug
		wp_update_term( $_REQUEST['term_ID'], 'jgood_media_library_category', array('name' => $_REQUEST['new_name'], 'slug'=> $new_slug) );

		// send folder id as result
		wp_send_json_success( array('new_ID'=>$new_slug) );
		wp_die();
	}

	/**
	 * Ajax Function to assign new media item to current folder
	 * gets called after uploading a media item
	 *
	 * @since    1.0.0
	 */
	public function jgood_media_ajax_create_file(){
		// get current fodler id from ajax request
		if(isset($_REQUEST['folder_ID'])){
			$folder = $_REQUEST['folder_ID'];
		}else{
		// if folder id is not set default to 0
			$folder = 0;
		}

		// if folder id is set assign current folder to that attachment
		if($folder != 0){
			wp_set_object_terms( $_REQUEST['attachment_ID'], (int)$_REQUEST['folder_ID'], 'jgood_media_library_category', false );
		}
		
		// send attachment id as result
		wp_send_json_success( array('new_ID'=>$_REQUEST['attachment_ID']) );
		wp_die();
	}
	
	/**
	 * prepare attachment/folder objects for js useage
	 *
	 * @since    1.0.0
	 */
	public function jgood_media_prepare_attachment_for_js( $attachment ) {

		// if object is stdClass it is a folder
		if(get_class($attachment) == "stdClass"){

			// set up js object using folder details
			$response = array(
				'id'          => $attachment->term_id,
				'title'       => $attachment->name,
				'filename'    => '',
				'url'         => '',
				'link'        => '',
				'alt'         => $attachment->slug,
				'author'      => '',
				'description' => '',
				'caption'     => '',
				'name'        => $attachment->name,
				'status'      => '',
				'uploadedTo'  => '',
				'date'        => '',
				'modified'    => '',
				'menuOrder'   => '',
				'mime'        => '',
				'type'        => 'folder',
				'subtype'     => '',
				'icon'        => plugin_dir_url( __FILE__ ) . 'images/folder.png',
				'dateFormatted' => '',
				'nonces'      => array(
					'update' => false,
					'delete' => false,
					'edit'   => false
				),
				'editLink'   => false,
				'meta'       => false
			);
			$meta = array();

		}else if(get_class($attachment) == "WP_Post"){
		// if object is WP_Post it is an attachment
		
			// assign js object details from attachment
			$meta = wp_get_attachment_metadata( $attachment->ID );

			if ( false !== strpos( $attachment->post_mime_type, '/' ) )
				list( $type, $subtype ) = explode( '/', $attachment->post_mime_type );
			else
				list( $type, $subtype ) = array( $attachment->post_mime_type, '' );

			$attachment_url = wp_get_attachment_url( $attachment->ID );

			$terms = wp_get_post_terms( $attachment->ID, 'jgood_media_library_category' );

			$response = array(
				'id'          => $attachment->ID,
				'title'       => $attachment->post_title,
				'filename'    => wp_basename( $attachment->guid ),
				'url'         => $attachment_url,
				'link'        => get_attachment_link( $attachment->ID ),
				'alt'         => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
				'author'      => $attachment->post_author,
				'description' => $attachment->post_content,
				'caption'     => $attachment->post_excerpt,
				'name'        => $attachment->post_name,
				'status'      => $attachment->post_status,
				'uploadedTo'  => $attachment->post_parent,
				'date'        => strtotime( $attachment->post_date_gmt ) * 1000,
				'modified'    => strtotime( $attachment->post_modified_gmt ) * 1000,
				'menuOrder'   => $attachment->menu_order,
				'mime'        => $attachment->post_mime_type,
				'type'        => $type,
				'buttons'     => array(
					'check' => true
				),
				'subtype'     => $subtype,
				'icon'        => wp_mime_type_icon( $attachment->ID ),
				'dateFormatted' => mysql2date( get_option('date_format'), $attachment->post_date ),
				'nonces'      => array(
					'update' => false,
					'delete' => false,
					'edit'   => false
				),
				'editLink'   => false,
				'meta'       => false,
				'terms'      => $terms,
			);

			$author = new WP_User( $attachment->post_author );
			$response['authorName'] = $author->display_name;

			if ( $attachment->post_parent ) {
				$post_parent = get_post( $attachment->post_parent );
			} else {
				$post_parent = false;
			}

			if ( $post_parent ) {
				$parent_type = get_post_type_object( $post_parent->post_type );
				if ( $parent_type && $parent_type->show_ui && current_user_can( 'edit_post', $attachment->post_parent ) ) {
					$response['uploadedToLink'] = get_edit_post_link( $attachment->post_parent, 'raw' );
				}
				$response['uploadedToTitle'] = $post_parent->post_title ? $post_parent->post_title : __( '(no title)' );
			}

			$attached_file = get_attached_file( $attachment->ID );
			if ( file_exists( $attached_file ) ) {
				$bytes = filesize( $attached_file );
				$response['filesizeInBytes'] = $bytes;
				$response['filesizeHumanReadable'] = size_format( $bytes );
			}

			if ( current_user_can( 'edit_post', $attachment->ID ) ) {
				$response['nonces']['update'] = wp_create_nonce( 'update-post_' . $attachment->ID );
				$response['nonces']['edit'] = wp_create_nonce( 'image_editor-' . $attachment->ID );
				$response['editLink'] = get_edit_post_link( $attachment->ID, 'raw' );
			}

			if ( current_user_can( 'delete_post', $attachment->ID ) )
				$response['nonces']['delete'] = wp_create_nonce( 'delete-post_' . $attachment->ID );

			if ( $meta && 'image' === $type ) {
				$sizes = array();

				/** This filter is documented in wp-admin/includes/media.php */
				$possible_sizes = apply_filters( 'image_size_names_choose', array(
					'thumbnail' => __('Thumbnail'),
					'medium'    => __('Medium'),
					'large'     => __('Large'),
					'full'      => __('Full Size'),
				) );
				unset( $possible_sizes['full'] );

				// Loop through all potential sizes that may be chosen. Try to do this with some efficiency.
				// First: run the image_downsize filter. If it returns something, we can use its data.
				// If the filter does not return something, then image_downsize() is just an expensive
				// way to check the image metadata, which we do second.
				foreach ( $possible_sizes as $size => $label ) {

					/** This filter is documented in wp-includes/media.php */
					if ( $downsize = apply_filters( 'image_downsize', false, $attachment->ID, $size ) ) {
						if ( ! $downsize[3] )
							continue;
						$sizes[ $size ] = array(
							'height'      => $downsize[2],
							'width'       => $downsize[1],
							'url'         => $downsize[0],
							'orientation' => $downsize[2] > $downsize[1] ? 'portrait' : 'landscape',
						);
					} elseif ( isset( $meta['sizes'][ $size ] ) ) {
						if ( ! isset( $base_url ) )
							$base_url = str_replace( wp_basename( $attachment_url ), '', $attachment_url );

						// Nothing from the filter, so consult image metadata if we have it.
						$size_meta = $meta['sizes'][ $size ];

						// We have the actual image size, but might need to further constrain it if content_width is narrower.
						// Thumbnail, medium, and full sizes are also checked against the site's height/width options.
						list( $width, $height ) = image_constrain_size_for_editor( $size_meta['width'], $size_meta['height'], $size, 'edit' );

						$sizes[ $size ] = array(
							'height'      => $height,
							'width'       => $width,
							'url'         => $base_url . $size_meta['file'],
							'orientation' => $height > $width ? 'portrait' : 'landscape',
						);
					}
				}

				$sizes['full'] = array( 'url' => $attachment_url );

				if ( isset( $meta['height'], $meta['width'] ) ) {
					$sizes['full']['height'] = $meta['height'];
					$sizes['full']['width'] = $meta['width'];
					$sizes['full']['orientation'] = $meta['height'] > $meta['width'] ? 'portrait' : 'landscape';
				}

				$response = array_merge( $response, array( 'sizes' => $sizes ), $sizes['full'] );
			} elseif ( $meta && 'video' === $type ) {
				if ( isset( $meta['width'] ) )
					$response['width'] = (int) $meta['width'];
				if ( isset( $meta['height'] ) )
					$response['height'] = (int) $meta['height'];
			}

			if ( $meta && ( 'audio' === $type || 'video' === $type ) ) {
				if ( isset( $meta['length_formatted'] ) )
					$response['fileLength'] = $meta['length_formatted'];

				$response['meta'] = array();
				foreach ( wp_get_attachment_id3_keys( $attachment, 'js' ) as $key => $label ) {
					$response['meta'][ $key ] = false;

					if ( ! empty( $meta[ $key ] ) ) {
						$response['meta'][ $key ] = $meta[ $key ];
					}
				}

				$id = get_post_thumbnail_id( $attachment->ID );
				if ( ! empty( $id ) ) {
					list( $src, $width, $height ) = wp_get_attachment_image_src( $id, 'full' );
					$response['image'] = compact( 'src', 'width', 'height' );
					list( $src, $width, $height ) = wp_get_attachment_image_src( $id, 'thumbnail' );
					$response['thumb'] = compact( 'src', 'width', 'height' );
				} else {
					$src = wp_mime_type_icon( $attachment->ID );
					$width = 48;
					$height = 64;
					$response['image'] = compact( 'src', 'width', 'height' );
					$response['thumb'] = compact( 'src', 'width', 'height' );
				}
			}

			if ( function_exists('get_compat_media_markup') )
				$response['compat'] = get_compat_media_markup( $attachment->ID, array( 'in_modal' => true ) );
		}

		/**
		 * Filter the attachment data prepared for JavaScript.
		 */
		return apply_filters( 'jgood_media_prepare_attachment_for_js', $response, $attachment, $meta );
	}
}
