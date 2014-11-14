<?php
/**
 * Featured Custom Post Type Widget For Genesis
 *
 * @package FeaturedCustomPostTypeWidgetForGenesis
 * @author  StudioPress
 * @author  Jo Waltham
 * @author  Pete Favelle
 * @author  Robin Cornett
 * @license GPL-2.0+
 *
 */

 /**
* Please note that most of this code is from the Genesis Featured Post Widget included in the Genesis Framework.
* I have just added support for Custom Post Types.
* Pete has added support for Custom Taxonomies.
*/


class Genesis_Featured_Custom_Post_Type extends WP_Widget {

	/**
	 * Holds widget settings defaults, populated in constructor.
	 *
	 * @var array
	 */
	protected $defaults;

	/**
	 * Constructor. Set the default widget options and create widget.
	 *
	 * @since 0.1.8
	 */
	function __construct() {

		$this->defaults = array(
			'title'                   => '',
			'post_type'               => 'post',
			'tax_term'                => '',
			'posts_num'               => 1,
			'posts_offset'            => 0,
			'orderby'                 => '',
			'order'                   => '',
			'exclude_displayed'       => 0,
			'show_image'              => 0,
			'image_alignment'         => '',
			'image_size'              => '',
			'show_gravatar'           => 0,
			'gravatar_alignment'      => '',
			'gravatar_size'           => '',
			'show_title'              => 0,
			'show_byline'             => 0,
			'post_info'               => '[post_date] ' . __( 'By', 'genesis-featured-custom-post-type-widget' ) . ' [post_author_posts_link] [post_comments]',
			'show_content'            => 'excerpt',
			'content_limit'           => '',
			'more_text'               => __( '[Read More...]', 'genesis-featured-custom-post-type-widget' ),
			'extra_num'               => '',
			'extra_title'             => '',
			'more_from_category'      => '',
			'more_from_category_text' => __( 'More Posts from this Category', 'genesis-featured-custom-post-type-widget' ),
			'archive_link'            => '',
			'archive_text'            => __( 'View Custom Post Type Archive', 'genesis-featured-custom-post-type-widget' ),
		);

		$widget_ops = array(
			'classname'   => 'featured-content featuredpost',
			'description' => __( 'Displays featured custom post types with thumbnails', 'genesis-featured-custom-post-type-widget' ),
		);

		$control_ops = array(
			'id_base' => 'featured-custom-post-type',
			'width'   => 505,
			'height'  => 350,
		);

		parent::__construct( 'featured-custom-post-type', __( 'Featured Custom Post Types for Genesis', 'genesis-featured-custom-post-type-widget' ), $widget_ops, $control_ops );

		// Register our Ajax handler
		add_action( 'wp_ajax_tax_term_action', array( $this, 'tax_term_action_callback' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );
	}

	/**
	 * Echo the widget content.
	 *
	 * @since 0.1.8
	 *
	 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget
	 */
	function widget( $args, $instance ) {

		global $wp_query, $_genesis_displayed_ids;

		extract( $args );

		//* Merge with defaults
		$instance = wp_parse_args( (array) $instance, $this->defaults );

		echo $before_widget;

		//* Set up the author bio
		if ( ! empty( $instance['title'] ) )
			echo $before_title . apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) . $after_title;

		$query_args = array(
			'post_type' => $instance['post_type'],
			'showposts' => $instance['posts_num'],
			'offset'    => $instance['posts_offset'],
			'orderby'   => $instance['orderby'],
			'order'     => $instance['order'],
		);

		// Extract the custom tax term, if provided
		if ( 'any' != $instance['tax_term'] ) {
			list( $post_tax, $post_term ) = explode( '/', $instance['tax_term'], 2 );
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => $post_tax,
					'field'    => 'slug',
					'terms'    => $post_term,
				)
			);
		}

		//* Exclude displayed IDs from this loop?
		if ( $instance['exclude_displayed'] )
			$query_args['post__not_in'] = (array) $_genesis_displayed_ids;

		$wp_query = new WP_Query( $query_args );

		if ( have_posts() ) : while ( have_posts() ) : the_post();

			$_genesis_displayed_ids[] = get_the_ID();

			genesis_markup( array(
				'html5'   => '<article %s>',
				'xhtml'   => sprintf( '<div class="%s">', implode( ' ', get_post_class() ) ),
				'context' => 'entry',
			) );

			$image = genesis_get_image( array(
				'format'  => 'html',
				'size'    => $instance['image_size'],
				'context' => 'featured-post-widget',
				'attr'    => genesis_parse_attr( 'entry-image-widget' ),
			) );

			if ( $instance['show_image'] && $image )
				printf( '<a href="%s" title="%s" class="%s">%s</a>', get_permalink(), the_title_attribute( 'echo=0' ), esc_attr( $instance['image_alignment'] ), $image );

			if ( ! empty( $instance['show_gravatar'] ) ) {
				echo '<span class="' . esc_attr( $instance['gravatar_alignment'] ) . '">';
				echo get_avatar( get_the_author_meta( 'ID' ), $instance['gravatar_size'] );
				echo '</span>';
			}

			if ( $instance['show_title'] )
				echo genesis_html5() ? '<header class="entry-header">' : '';

				if ( ! empty( $instance['show_title'] ) ) {

					if ( genesis_html5() )
						printf( '<h2 class="entry-title"><a href="%s" title="%s">%s</a></h2>', get_permalink(), the_title_attribute( 'echo=0' ), get_the_title() );
					else
						printf( '<h2><a href="%s" title="%s">%s</a></h2>', get_permalink(), the_title_attribute( 'echo=0' ), get_the_title() );

				}

				if ( ! empty( $instance['show_byline'] ) && ! empty( $instance['post_info'] ) )
					printf( genesis_html5() ? '<p class="entry-meta">%s</p>' : '<p class="byline post-info">%s</p>', do_shortcode( $instance['post_info'] ) );

			if ( $instance['show_title'] )
				echo genesis_html5() ? '</header>' : '';

			if ( ! empty( $instance['show_content'] ) ) {

				echo genesis_html5() ? '<div class="entry-content">' : '';

				if ( 'excerpt' == $instance['show_content'] ) {
					the_excerpt();
				}
				elseif ( 'content-limit' == $instance['show_content'] ) {
					the_content_limit( (int) $instance['content_limit'], esc_html( $instance['more_text'] ) );
				}
				else {

					global $more;

					$orig_more = $more;
					$more = 0;

					the_content( esc_html( $instance['more_text'] ) );

					$more = $orig_more;

				}

				echo genesis_html5() ? '</div>' : '';

			}

			genesis_markup( array(
				'html5' => '</article>',
				'xhtml' => '</div>',
			) );

		endwhile; endif;

		//* Restore original query
		wp_reset_query();

		//* The EXTRA Posts (list)
		if ( ! empty( $instance['extra_num'] ) ) {
			if ( ! empty( $instance['extra_title'] ) )
				echo $before_title . esc_html( $instance['extra_title'] ) . $after_title;

			$offset = intval( $instance['posts_num'] ) + intval( $instance['posts_offset'] );

			$query_args = array(
				'post_type' => $instance['post_type'],
				'showposts' => $instance['extra_num'],
				'offset'    => $offset,
			);

			// Extract the custom tax term, if provided
			if ( 'any' != $instance['tax_term'] ) {
				list( $post_tax, $post_term ) = explode( '/', $instance['tax_term'], 2 );
				$query_args['tax_query'] = array(
					array(
						'taxonomy' => $post_tax,
						'field'    => 'slug',
						'terms'    => $post_term,
					)
				);
			 }

			$wp_query = new WP_Query( $query_args );

			$listitems = '';

			if ( have_posts() ) {
				while ( have_posts() ) {
					the_post();
					$_genesis_displayed_ids[] = get_the_ID();
					$listitems .= sprintf( '<li><a href="%s" title="%s">%s</a></li>', get_permalink(), the_title_attribute( 'echo=0' ), get_the_title() );
				}

				if ( mb_strlen( $listitems ) > 0 )
					printf( '<ul>%s</ul>', $listitems );
			}

			//* Restore original query
			wp_reset_query();
		}

		if ( ! empty( $instance['more_from_category'] )
		&& ( 'category' == substr( $instance['tax_term'], 0, 8 ) ) ) {
			$post_cat = get_cat_ID( substr( $instance['tax_term'], 9 ) );
			printf(
				'<p class="more-from-category"><a href="%1$s" title="%2$s">%3$s</a></p>',
				esc_url( get_category_link( $post_cat ) ),
				esc_attr( get_cat_name( $post_cat ) ),
				esc_html( $instance['more_from_category_text'] )
			);
		}

		if ( ! empty( $instance['archive_link'] ) && ! empty( $instance['archive_text'] ) ) {
			printf(
				'<p class="more-from-category"><a href="%1$s">%2$s</a></p>',
				get_post_type_archive_link( $instance['post_type'] ),
				esc_html( $instance['archive_text'] )
			);

		}

		echo $after_widget;

	}

	/**
	 * Update a particular instance.
	 *
	 * This function should check that $new_instance is set correctly.
	 * The newly calculated value of $instance should be returned.
	 * If "false" is returned, the instance won't be saved/updated.
	 *
	 * @since 0.1.8
	 *
	 * @param array $new_instance New settings for this instance as input by the user via form()
	 * @param array $old_instance Old settings for this instance
	 * @return array Settings to save or bool false to cancel saving
	 */
	function update( $new_instance, $old_instance ) {

		$new_instance['title']     = strip_tags( $new_instance['title'] );
		$new_instance['more_text'] = strip_tags( $new_instance['more_text'] );
		$new_instance['post_info'] = wp_kses_post( $new_instance['post_info'] );
		return $new_instance;

	}

	/**
	 * Echo the settings update form.
	 *
	 * @since 0.1.8
	 *
	 * @param array $instance Current settings
	 */
	function form( $instance ) {

		//* Merge with defaults
		$instance = wp_parse_args( (array) $instance, $this->defaults );

		// Fetch a list of possible post types
		$args = array(
			'public'   => true,
			'_builtin' => false,
		);
		$output = 'names';
		$operator = 'and';
		$post_type_list = get_post_types( $args, $output, $operator );

		// Add posts to that post_type_list
		$post_type_list['post'] = 'post';

		// And a list of available taxonomies for the current post type
		if ( 'any' == $instance['post_type'] ) {
			$taxonomies = get_taxonomies();
		} else {
			$taxonomies = get_object_taxonomies( $instance['post_type'] );
		}

		// And from there, a list of available terms in that tax
		$tax_args = array(
			'hide_empty' => 0,
		);
		$tax_term_list = get_terms( $taxonomies, $tax_args );
		usort( $tax_term_list, array( $this, 'tax_term_compare' ) );

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'genesis-featured-custom-post-type-widget' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" />
		</p>

		<div class="genesis-widget-column">

			<div class="genesis-widget-column-box genesis-widget-column-box-top">

				<p>
					<label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php _e( 'Post Type', 'genesis-featured-custom-post-type-widget' ); ?>:</label>
					<select id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>" onchange="tax_term_postback('<?php echo $this->get_field_id( 'tax_term' ); ?>', this.value);" >

						<?php
						foreach ( $post_type_list as $post_type_item )
							echo '<option style="padding-right:10px;" value="'. esc_attr( $post_type_item ) .'" '. selected( esc_attr( $post_type_item ), $instance['post_type'], false ) .'>'. esc_attr( $post_type_item ) .'</option>';

						echo '<option style="padding-right:10px;" value="any" '. selected( 'any', $instance['post_type'], false ) .'>'. __( 'any', 'genesis-featured-custom-post-type-widget' ) .'</option>';
						?>
					</select>
				</p>

				<p>
					<label for="<?php echo $this->get_field_id( 'tax_term' ); ?>"><?php _e( 'Category/Term', 'genesis-featured-custom-post-type-widget' ); ?>:</label>
					<select id="<?php echo $this->get_field_id( 'tax_term' ); ?>" name="<?php echo $this->get_field_name( 'tax_term' ); ?>">

						<?php
						foreach ( $tax_term_list as $tax_term_item ) {
							$tax_term_desc = $tax_term_item->taxonomy . '/' . $tax_term_item->name;
							$tax_term_slug = $tax_term_item->taxonomy . '/' . $tax_term_item->slug;
							echo '<option style="padding-right:10px;" value="'. esc_attr( $tax_term_slug ) .'" '. selected( esc_attr( $tax_term_slug ), $instance['tax_term'], false ) .'>'. esc_attr( $tax_term_desc ) .'</option>';
						}

						echo '<option style="padding-right:10px;" value="any" '. selected( 'any', $instance['tax_term'], false ) .'>'. __( 'any', 'genesis-featured-custom-post-type-widget' ) .'</option>';
						?>
					</select>
				</p>

				<p>
					<label for="<?php echo $this->get_field_id( 'posts_num' ); ?>"><?php _e( 'Number of Posts to Show', 'genesis-featured-custom-post-type-widget' ); ?>:</label>
					<input type="text" id="<?php echo $this->get_field_id( 'posts_num' ); ?>" name="<?php echo $this->get_field_name( 'posts_num' ); ?>" value="<?php echo esc_attr( $instance['posts_num'] ); ?>" size="2" />
				</p>

				<p>
					<label for="<?php echo $this->get_field_id( 'posts_offset' ); ?>"><?php _e( 'Number of Posts to Offset', 'genesis-featured-custom-post-type-widget' ); ?>:</label>
					<input type="text" id="<?php echo $this->get_field_id( 'posts_offset' ); ?>" name="<?php echo $this->get_field_name( 'posts_offset' ); ?>" value="<?php echo esc_attr( $instance['posts_offset'] ); ?>" size="2" />
				</p>

				<p>
					<label for="<?php echo $this->get_field_id( 'orderby' ); ?>"><?php _e( 'Order By', 'genesis-featured-custom-post-type-widget' ); ?>:</label>
					<select id="<?php echo $this->get_field_id( 'orderby' ); ?>" name="<?php echo $this->get_field_name( 'orderby' ); ?>">
						<option value="date" <?php selected( 'date', $instance['orderby'] ); ?>><?php _e( 'Date', 'genesis-featured-custom-post-type-widget' ); ?></option>
						<option value="title" <?php selected( 'title', $instance['orderby'] ); ?>><?php _e( 'Title', 'genesis-featured-custom-post-type-widget' ); ?></option>
						<option value="parent" <?php selected( 'parent', $instance['orderby'] ); ?>><?php _e( 'Parent', 'genesis-featured-custom-post-type-widget' ); ?></option>
						<option value="ID" <?php selected( 'ID', $instance['orderby'] ); ?>><?php _e( 'ID', 'genesis-featured-custom-post-type-widget' ); ?></option>
						<option value="comment_count" <?php selected( 'comment_count', $instance['orderby'] ); ?>><?php _e( 'Comment Count', 'genesis-featured-custom-post-type-widget' ); ?></option>
						<option value="rand" <?php selected( 'rand', $instance['orderby'] ); ?>><?php _e( 'Random', 'genesis-featured-custom-post-type-widget' ); ?></option>
					</select>
				</p>

				<p>
					<label for="<?php echo $this->get_field_id( 'order' ); ?>"><?php _e( 'Sort Order', 'genesis-featured-custom-post-type-widget' ); ?>:</label>
					<select id="<?php echo $this->get_field_id( 'order' ); ?>" name="<?php echo $this->get_field_name( 'order' ); ?>">
						<option value="DESC" <?php selected( 'DESC', $instance['order'] ); ?>><?php _e( 'Descending (3, 2, 1)', 'genesis-featured-custom-post-type-widget' ); ?></option>
						<option value="ASC" <?php selected( 'ASC', $instance['order'] ); ?>><?php _e( 'Ascending (1, 2, 3)', 'genesis-featured-custom-post-type-widget' ); ?></option>
					</select>
				</p>

				<p>
					<input id="<?php echo $this->get_field_id( 'exclude_displayed' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'exclude_displayed' ); ?>" value="1" <?php checked( $instance['exclude_displayed'] ); ?>/>
					<label for="<?php echo $this->get_field_id( 'exclude_displayed' ); ?>"><?php _e( 'Exclude Previously Displayed Posts?', 'genesis-featured-custom-post-type-widget' ); ?></label>
				</p>

			</div>

			<div class="genesis-widget-column-box">

				<p>
					<input id="<?php echo $this->get_field_id( 'show_gravatar' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'show_gravatar' ); ?>" value="1" <?php checked( $instance['show_gravatar'] ); ?>/>
					<label for="<?php echo $this->get_field_id( 'show_gravatar' ); ?>"><?php _e( 'Show Author Gravatar', 'genesis-featured-custom-post-type-widget' ); ?></label>
				</p>

				<p>
					<label for="<?php echo $this->get_field_id( 'gravatar_size' ); ?>"><?php _e( 'Gravatar Size', 'genesis-featured-custom-post-type-widget' ); ?>:</label>
					<select id="<?php echo $this->get_field_id( 'gravatar_size' ); ?>" name="<?php echo $this->get_field_name( 'gravatar_size' ); ?>">
						<option value="45" <?php selected( 45, $instance['gravatar_size'] ); ?>><?php _e( 'Small (45px)', 'genesis-featured-custom-post-type-widget' ); ?></option>
						<option value="65" <?php selected( 65, $instance['gravatar_size'] ); ?>><?php _e( 'Medium (65px)', 'genesis-featured-custom-post-type-widget' ); ?></option>
						<option value="85" <?php selected( 85, $instance['gravatar_size'] ); ?>><?php _e( 'Large (85px)', 'genesis-featured-custom-post-type-widget' ); ?></option>
						<option value="125" <?php selected( 105, $instance['gravatar_size'] ); ?>><?php _e( 'Extra Large (125px)', 'genesis-featured-custom-post-type-widget' ); ?></option>
					</select>
				</p>

				<p>
					<label for="<?php echo $this->get_field_id( 'gravatar_alignment' ); ?>"><?php _e( 'Gravatar Alignment', 'genesis-featured-custom-post-type-widget' ); ?>:</label>
					<select id="<?php echo $this->get_field_id( 'gravatar_alignment' ); ?>" name="<?php echo $this->get_field_name( 'gravatar_alignment' ); ?>">
						<option value="alignnone">- <?php _e( 'None', 'genesis-featured-custom-post-type-widget' ); ?> -</option>
						<option value="alignleft" <?php selected( 'alignleft', $instance['gravatar_alignment'] ); ?>><?php _e( 'Left', 'genesis-featured-custom-post-type-widget' ); ?></option>
						<option value="alignright" <?php selected( 'alignright', $instance['gravatar_alignment'] ); ?>><?php _e( 'Right', 'genesis-featured-custom-post-type-widget' ); ?></option>
						<option value="aligncenter" <?php selected( 'aligncenter', $instance['gravatar_alignment'] ); ?>><?php _e( 'Center', 'genesis-featured-custom-post-type-widget' ); ?></option>
					</select>
				</p>

			</div>

			<div class="genesis-widget-column-box">

				<p>
					<input id="<?php echo $this->get_field_id( 'show_image' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'show_image' ); ?>" value="1" <?php checked( $instance['show_image'] ); ?>/>
					<label for="<?php echo $this->get_field_id( 'show_image' ); ?>"><?php _e( 'Show Featured Image', 'genesis-featured-custom-post-type-widget' ); ?></label>
				</p>

				<p>
					<label for="<?php echo $this->get_field_id( 'image_size' ); ?>"><?php _e( 'Image Size', 'genesis-featured-custom-post-type-widget' ); ?>:</label>
					<select id="<?php echo $this->get_field_id( 'image_size' ); ?>" class="genesis-image-size-selector" name="<?php echo $this->get_field_name( 'image_size' ); ?>">
						<?php
						$sizes = genesis_get_image_sizes();
						foreach( (array) $sizes as $name => $size )
							echo '<option value="'.esc_attr( $name ).'" '.selected( $name, $instance['image_size'], FALSE ).'>'.esc_html( $name ).' ( '.$size['width'].'x'.$size['height'].' )</option>';
						?>
					</select>
				</p>

				<p>
					<label for="<?php echo $this->get_field_id( 'image_alignment' ); ?>"><?php _e( 'Image Alignment', 'genesis-featured-custom-post-type-widget' ); ?>:</label>
					<select id="<?php echo $this->get_field_id( 'image_alignment' ); ?>" name="<?php echo $this->get_field_name( 'image_alignment' ); ?>">
						<option value="alignnone">- <?php _e( 'None', 'genesis-featured-custom-post-type-widget' ); ?> -</option>
						<option value="alignleft" <?php selected( 'alignleft', $instance['image_alignment'] ); ?>><?php _e( 'Left', 'genesis-featured-custom-post-type-widget' ); ?></option>
						<option value="alignright" <?php selected( 'alignright', $instance['image_alignment'] ); ?>><?php _e( 'Right', 'genesis-featured-custom-post-type-widget' ); ?></option>
						<option value="aligncenter" <?php selected( 'aligncenter', $instance['image_alignment'] ); ?>><?php _e( 'Center', 'genesis-featured-custom-post-type-widget' ); ?></option>
					</select>
				</p>

			</div>

		</div>

		<div class="genesis-widget-column genesis-widget-column-right">

			<div class="genesis-widget-column-box genesis-widget-column-box-top">

				<p>
					<input id="<?php echo $this->get_field_id( 'show_title' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'show_title' ); ?>" value="1" <?php checked( $instance['show_title'] ); ?>/>
					<label for="<?php echo $this->get_field_id( 'show_title' ); ?>"><?php _e( 'Show Post Title', 'genesis-featured-custom-post-type-widget' ); ?></label>
				</p>

				<p>
					<input id="<?php echo $this->get_field_id( 'show_byline' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'show_byline' ); ?>" value="1" <?php checked( $instance['show_byline'] ); ?>/>
					<label for="<?php echo $this->get_field_id( 'show_byline' ); ?>"><?php _e( 'Show Post Info', 'genesis-featured-custom-post-type-widget' ); ?></label>
					<input type="text" id="<?php echo $this->get_field_id( 'post_info' ); ?>" name="<?php echo $this->get_field_name( 'post_info' ); ?>" value="<?php echo esc_attr( $instance['post_info'] ); ?>" class="widefat" />
				</p>

				<p>
					<label for="<?php echo $this->get_field_id( 'show_content' ); ?>"><?php _e( 'Content Type', 'genesis-featured-custom-post-type-widget' ); ?>:</label>
					<select id="<?php echo $this->get_field_id( 'show_content' ); ?>" name="<?php echo $this->get_field_name( 'show_content' ); ?>">
						<option value="content" <?php selected( 'content', $instance['show_content'] ); ?>><?php _e( 'Show Content', 'genesis-featured-custom-post-type-widget' ); ?></option>
						<option value="excerpt" <?php selected( 'excerpt', $instance['show_content'] ); ?>><?php _e( 'Show Excerpt', 'genesis-featured-custom-post-type-widget' ); ?></option>
						<option value="content-limit" <?php selected( 'content-limit', $instance['show_content'] ); ?>><?php _e( 'Show Content Limit', 'genesis-featured-custom-post-type-widget' ); ?></option>
						<option value="" <?php selected( '', $instance['show_content'] ); ?>><?php _e( 'No Content', 'genesis-featured-custom-post-type-widget' ); ?></option>
					</select>
					<br />
					<label for="<?php echo $this->get_field_id( 'content_limit' ); ?>"><?php _e( 'Limit content to', 'genesis-featured-custom-post-type-widget' ); ?>
						<input type="text" id="<?php echo $this->get_field_id( 'image_alignment' ); ?>" name="<?php echo $this->get_field_name( 'content_limit' ); ?>" value="<?php echo esc_attr( intval( $instance['content_limit'] ) ); ?>" size="3" />
						<?php _e( 'characters', 'genesis-featured-custom-post-type-widget' ); ?>
					</label>
				</p>

				<p>
					<label for="<?php echo $this->get_field_id( 'more_text' ); ?>"><?php _e( 'More Text (if applicable)', 'genesis-featured-custom-post-type-widget' ); ?>:</label>
					<input type="text" id="<?php echo $this->get_field_id( 'more_text' ); ?>" name="<?php echo $this->get_field_name( 'more_text' ); ?>" value="<?php echo esc_attr( $instance['more_text'] ); ?>" />
				</p>

			</div>

			<div class="genesis-widget-column-box">

				<p><?php _e( 'To display an unordered list of more posts from this category, please fill out the information below', 'genesis-featured-custom-post-type-widget' ); ?>:</p>

				<p>
					<label for="<?php echo $this->get_field_id( 'extra_title' ); ?>"><?php _e( 'Title', 'genesis-featured-custom-post-type-widget' ); ?>:</label>
					<input type="text" id="<?php echo $this->get_field_id( 'extra_title' ); ?>" name="<?php echo $this->get_field_name( 'extra_title' ); ?>" value="<?php echo esc_attr( $instance['extra_title'] ); ?>" class="widefat" />
				</p>

				<p>
					<label for="<?php echo $this->get_field_id( 'extra_num' ); ?>"><?php _e( 'Number of Posts to Show', 'genesis-featured-custom-post-type-widget' ); ?>:</label>
					<input type="text" id="<?php echo $this->get_field_id( 'extra_num' ); ?>" name="<?php echo $this->get_field_name( 'extra_num' ); ?>" value="<?php echo esc_attr( $instance['extra_num'] ); ?>" size="2" />
				</p>

			</div>

			<div class="genesis-widget-column-box">

				<p>
					<input id="<?php echo $this->get_field_id( 'more_from_category' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'more_from_category' ); ?>" value="1" <?php checked( $instance['more_from_category'] ); ?>/>
					<label for="<?php echo $this->get_field_id( 'more_from_category' ); ?>"><?php _e( 'Show Category Archive Link', 'genesis-featured-custom-post-type-widget' ); ?></label>
				</p>

				<p>
					<label for="<?php echo $this->get_field_id( 'more_from_category_text' ); ?>"><?php _e( 'Link Text', 'genesis-featured-custom-post-type-widget' ); ?>:</label>
					<input type="text" id="<?php echo $this->get_field_id( 'more_from_category_text' ); ?>" name="<?php echo $this->get_field_name( 'more_from_category_text' ); ?>" value="<?php echo esc_attr( $instance['more_from_category_text'] ); ?>" class="widefat" />
				</p>

				<p>
					<input id="<?php echo $this->get_field_id( 'archive_link' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'archive_link' ); ?>" value="1" <?php checked( $instance['archive_link'] ); ?>/>
					<label for="<?php echo $this->get_field_id( 'archive_link' ); ?>"><?php _e( 'Show Archive Link', 'genesis-featured-sermon-widget' ); ?></label>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id( 'archive_text' ); ?>"><?php _e( 'Link Text', 'genesis-featured-custom-post-type-widget' ); ?>:</label>
					<input type="text" id="<?php echo $this->get_field_id( 'archive_text' ); ?>" name="<?php echo $this->get_field_name( 'archive_text' ); ?>" value="<?php echo esc_attr( $instance['archive_text'] ); ?>" class="widefat" />
				</p>

			</div>

		</div>
		<?php

	}

	/**
	 * Comparison function to allow custom taxonomy terms to be displayed
	 * alphabetically. Required because the display is a compound of term
	 * *and* taxonomy.
	 */
	function tax_term_compare( $a, $b ) {
		if ( $a->taxonomy == $b->taxonomy ) {
			return ($a->name < $b->name) ? -1 : 1;
		}
		return ($a->taxonomy <  $b->taxonomy)? -1 : 1;
	}

	/**
	 * Enqueues the small bit of Javascript which will handle the Ajax
	 * callback to correctly populate the custom term dropdown.
	 */
	function admin_enqueue() {
		$screen = get_current_screen()->id;
		if ( $screen === 'widgets' || $screen === 'customize' ) {
			wp_enqueue_script( 'tax-term-ajax-script', plugins_url( '/ajax_handler.js', __FILE__ ), array('jquery') );
			wp_localize_script( 'tax-term-ajax-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		}
	}

	/**
	 * Handles the callback to populate the custom term dropdown. The
	 * selected post type is provided in $_POST['post_type'], and the
	 * calling script expects a JSON array of term objects.
	 */
	function tax_term_action_callback() {

		// Fetch a list of available taxonomies for the current post type
		if ( 'any' == $_POST['post_type'] ) {
			$taxonomies = get_taxonomies();
		} else {
			$taxonomies = get_object_taxonomies( $_POST['post_type'] );
		}

		// And from there, a list of available terms in that tax
		$tax_args = array(
			'hide_empty'	=> 0,
		);
		$tax_term_list = get_terms( $taxonomies, $tax_args );

		// Build an appropriate JSON response containing this info
		foreach ( $tax_term_list as $tax_term_item ) {
			$taxes [$tax_term_item->taxonomy . '/' . $tax_term_item->slug] =
				$tax_term_item->taxonomy . '/' . $tax_term_item->name;
		}
		$taxes['any'] = 'any';

		// And emit it
		echo json_encode( $taxes );
		die();
	}
}
