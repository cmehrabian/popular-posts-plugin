<?php
/*
Plugin Name: LiveCareer Blog Popular Posts
Description: A simple plugin that tracks popular posts based on views
Version:     0.1
Author:      Cameron Mehrabian
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if( !defined('ABSPATH') ) exit;

/**
 * Post Popularity Counter
 */

function lcb_popular_post_views($postID) {
	$total_key = 'views';
	// Get current views fields
	$total = get_post_meta( $postID, $total_key, true );
	// Check if current 'views' fields is empty
	if ( $total == '' ) {
		delete_post_meta( $postID, $total_key);
		add_post_meta( $postID, $total_key, '0');
	} else {
		// If current 'views' fields has a value, add 1 to that value
		$total += 1;
		update_post_meta( $postID, $total_key, $total );
	}

}

/**
 * Dynamically inject counter based on user
 */
function lcb_count_popular_posts($post_id) {
	// Check if single post and that user is a visitor
	if( !is_single() ) return;

	if( !is_user_logged_in() ) {
		// Get Post ID
		if ( empty($post_id) ) {
			global $post;
			$post_id = $post->ID;
		}
		// Run Post Popularity Counter on post
		lcb_popular_post_views($post_id);
	}
}

add_action( 'wp_head', 'lcb_count_popular_posts');

/**
 * Adds Popular posts widget.
 */
class Popular_Posts extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'popular_posts', // Base ID
			esc_html__( 'Popular Posts', 'text_domain' ), // Name
			array( 'description' => esc_html__( 'Displays top 5 popular posts', 'text_domain' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
		
		// Popular Posts Query
		$lcb_args = array(
			'post_type' => 'post',
			'posts_per_page' => 5,
			'meta_key' => 'views',
			'orderby' => 'meta_value_num',
			'order' => 'DESC',
			'ignore_sticky_posts' => true
		);

		$the_query = new WP_Query( $lcb_args );

		// The Loop
		if ( $the_query->have_posts() ) {
			echo '<ul>';
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				echo '<li>';
				echo '<a href="'. get_the_permalink() .' rel="bookmark">';
				echo get_the_title();
				echo ' (' . get_post_meta( get_the_ID(), 'views', true  ) . ')';
				echo '</a>';
				echo '</li>';
			}
			echo '</ul>';
			/* Restore original Post Data */
			wp_reset_postdata();
		} else {
			// no posts found
		}

		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Popular Posts', 'text_domain' );
		?>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'text_domain' ); ?></label> 
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}

} // class Popular_Posts


// register popular_posts widget
function register_popular_posts_widget() {
    register_widget( 'Popular_Posts' );
}
add_action( 'widgets_init', 'register_popular_posts_widget' );