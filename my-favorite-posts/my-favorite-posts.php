<?php
/*
  Plugin Name: My Favorite Posts
  Description: Allows registeres users to add posts to favorite
  Version: 1.0
  Author: Yurii Pavlov
  Author URI: http://we.co.ua
  License: GPLv2 or later
 */

//No direct access
if ( !function_exists( 'add_action' ) ) {
	exit;
}

class MyFavoritePosts {

	function __construct() {
		
		//Activation/deactivation hooks
		register_activation_hook(__FILE__, array($this,'mfp_activate'));
		register_deactivation_hook(__FILE__, array($this,'mfp_deactivate'));
		
		//Add button to post
		add_filter( 'the_content', array($this, 'mfp_add_button' ));
		
		//Shortcode to display faforite posts everywhere ([my-favorite-posts])
		add_shortcode( 'my-favorite-posts', array($this, 'mfp_shortcode'));
		
		add_action('init', array($this, 'mfp_init'));
	}

	//Activation and add table to db
	function mfp_activate() {
		global $wpdb;
		$sql = "CREATE TABLE `".$wpdb->prefix."my_favorite_posts` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
		  `post_id` int(11) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id`),
		  KEY `user_id` (`user_id`),
		  KEY `post_id` (`post_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

		$wpdb->query( $sql );
	}

	//Nothing
	function mfp_deactivate() {

	}

	//Add button to post
	function mfp_add_button($content){
		global $post;
		
		$user_id = get_current_user_id();

		if (!$user_id) {
			return $content;
		}
		global $post;
		if ($post->post_type == 'post') {
			$check_post = $this->mfp_check_post($post->ID, $user_id);
			$mfp_button = '<form action="" method="post" enctype="multipart/form-data">'.
			'<input type="hidden" name="mfp_post_id" value="'.$post->ID.'">';
			if ($check_post) {
				$mfp_button .='<input type="hidden" name="mfp_action" value="remove">'.
				'<input type="submit" id="mfp_button" value="'.__( 'Remove from favorite', 'my-favorite-posts' ).'">';
			} else {
				$mfp_button .='<input type="hidden" name="mfp_action" value="add">'.
				'<input type="submit" id="mfp_button" value="'.__( 'Add to favorite', 'my-favorite-posts' ).'">';
			}
			$mfp_button .= '</form>';
			$content .= $mfp_button;
		}
		return $content;
	}

	//Check if post in favorite
	function mfp_check_post($post_id, $user_id){
		global $wpdb;
		$sql = "SELECT post_id FROM `".$wpdb->prefix."my_favorite_posts` WHERE post_id = %d AND user_id = %d";
		$result = $wpdb->get_row( $wpdb->prepare( $sql, $post_id, $user_id ) );
		return $result;
	}

	//Get favorite posts array 
	function mfp_get_posts($user_id){
		global $wpdb;
		$sql = "SELECT post_id FROM `".$wpdb->prefix."my_favorite_posts` WHERE user_id = %d";
		$result = $wpdb->get_results( $wpdb->prepare( $sql, $user_id ) );
		return $result;
	}

	//Add post to favorite
	function mfp_add_post($post_id) {
		global $wpdb;

		$user_id = get_current_user_id();

		if (!$user_id) {
			return;
		}
		return $wpdb->insert($wpdb->prefix."my_favorite_posts", array('post_id' => $post_id, 'user_id' => $user_id), array('%d','%d'));
	}

	//Remove post from favorite
	function mfp_remove_post($post_id) {
		global $wpdb;
		
		$user_id = get_current_user_id();
		if (!$user_id) {
			return;
		}
		$wpdb->delete($wpdb->prefix."my_favorite_posts", array('post_id' => $post_id, 'user_id' => $user_id), array('%d','%d'));
	}

	//Shortcode to display faforite posts everywhere ([my-favorite-posts])
	function mfp_shortcode() {
		global $post;

		$user_id = get_current_user_id();
		if (!$user_id) {
			return;
		}
		$posts = $this->mfp_get_posts($user_id);
		if ($posts) { ?>
			<ul>
			<?php 
			foreach ($posts as $mf_post) {
				$s_post = get_post($mf_post->post_id);
				?>
				<li>
						<p class="mfp_title"><a href="<?php echo get_permalink($mf_post->post_id) ?>" rel="bookmark"><?php echo $s_post->post_title; ?></a></p>

						<div class="mfp_thumbnail">
							<a href="<?php echo get_permalink($mf_post->post_id); ?>">
								<?php echo  get_the_post_thumbnail($mf_post->post_id, 'medium'); ?>
							</a>
						</div>
					
					<div class="mfp_content">
						<p><?php echo $s_post->post_content; ?></p>
						<form action="" method="post" enctype="multipart/form-data">
							<input type="hidden" name="mfp_post_id" value="<?php echo $mf_post->post_id; ?>">
							<input type="hidden" name="mfp_action" value="remove">
							<input type="submit" id="mfp_button" value="<?php echo __( 'Remove from favorite', 'my-favorite-posts' ); ?>">
						</form>
					</div>
				</li>
				<?php 
			} ?>
			</ul>
			<?php 
		} else {
			echo __( 'No entries found', 'my-favorite-posts' );
		}

		return $html;
	}


	//Init
	function mfp_init(){
		load_plugin_textdomain( 'my-favorite-posts', false, dirname(plugin_basename( __FILE__ )).'/lang/');

		if (isset($_POST['mfp_post_id'])) {
			if ($_POST['mfp_action'] == 'add') {
				$this->mfp_add_post((int)$_POST['mfp_post_id']);
			}
			if ($_POST['mfp_action'] == 'remove') {
				$this->mfp_remove_post((int)$_POST['mfp_post_id']);
			}	
		}
	}

}

$MyFavoritePosts = new MyFavoritePosts();



?>