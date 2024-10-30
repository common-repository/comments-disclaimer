<?php
/**
 * Plugin Name: Comments Disclaimer
 * Description: A minimalist plugin that will add a public comments disclaimer to your site. Protect yourself from liabilities for user-generated comments.
 * Version: 1.0
 * Author: Alan Jacob Mathew
 * Author URI: https://profiles.wordpress.org/alanjacobmathew/
 * License: 
 * Plugin URI: https://github.com/alanjacobmathew/comments-disclaimer
 * Requires at least: 5.8
 * Requires PHP: 7.3
 * Tested up to: 6.2
 * Text Domain: comments-disclaimer
 * Domain Path: /languages/

 */

class Comments_Disclaimer_Plugin {
    public static function init_cdwp() {
        add_action('admin_menu', array(__CLASS__, 'add_custom_meta_box_cdwp'));
        add_action('save_post', array(__CLASS__, 'save_comment_disclaimer_checkbox_cdwp'));
        add_action('comment_form_before', array(__CLASS__, 'add_comments_disclaimer_cdwp'), 15);
        register_uninstall_hook(__FILE__, array(__CLASS__, 'uninstall_cdwp'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_comments_disclaimer_assets_cdwp'));
        add_action('admin_menu', array(__CLASS__, 'add_plugin_page_cdwp'));
        add_filter('plugin_action_links', array(__CLASS__, 'add_plugin_page_link_cdwp'), 10, 2);
        add_action('admin_init', array(__CLASS__, 'add_disclaimer_settings_cdwp'));
        add_action('admin_post_add_comments_disclaimer_to_existing_posts', array(__CLASS__, 'handle_add_comments_disclaimer_to_existing_posts_cdwp'));
		add_action('plugins_loaded', array(__CLASS__, 'load_comments_disclaimer_textdomain_cdwp'));
    }
	
	
// Add plugin page
    public static function add_plugin_page_cdwp() {
        add_submenu_page(
            'edit-comments.php',
            __('Comments Disclaimer Plugin', 'comments-disclaimer'),
            __('Comments Disclaimer', 'comments-disclaimer'),
            'manage_options',
            'comments-disclaimer-plugin',
            array(__CLASS__, 'render_plugin_page_cdwp')
        );
    }
	
// Add link to plugin page from plugin-install.php
    public static function add_plugin_page_link_cdwp($actions, $plugin_file) {
        if ($plugin_file === plugin_basename(__FILE__)) {
            $settings_link = '<a href="' . esc_url(admin_url('edit-comments.php?page=comments-disclaimer-plugin')) . '">' . esc_html__('Settings', 'comments-disclaimer') . '</a>';
            $actions['settings'] = $settings_link;
        }
        return $actions;
    }
	
// Contents inside plugin page
    public static function render_plugin_page_cdwp() {
    
    echo '<h1>' . esc_html__('Comments Disclaimer Settings', 'comments-disclaimer') . '</h1>';

    
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="add_comments_disclaimer_to_existing_posts">';

    echo '<em><h4 class="description" style="color:#767676; padding:15px; margin-right:10px; font-weight: bold;">' . esc_html__('Note: This process is irreversible. Can be used only once', 'comments-disclaimer') . '</h4></em>';

    
    

    echo '<p><input type="submit" class="button-primary" value="' . esc_attr__('Generate', 'comments-disclaimer') . '"></p>';
    echo '</form>';
	self::render_disclaimer_settings_section_cdwp();
    
}



    public static function add_disclaimer_settings_cdwp() {
        add_settings_section('comments_disclaimer_settings', __('Comments Disclaimer Settings', 'comments-disclaimer'), array(__CLASS__, 'render_disclaimer_settings_section_cdwp'), 'comments-disclaimer-plugin');
    }
// Extra contents that comes after the generate button on Comments Disclaimer Plugin page
   public static function render_disclaimer_settings_section_cdwp() {
        echo '<p>' . esc_html__('For all posts where comments are enabled, the above setting will add the disclaimer content, right above the comments form.', 'comments-disclaimer') . '</p>';
		echo '<p>' . esc_html__('* You can individually continue enabling / disabling the setting inside the Post Editor.', 'comments-disclaimer') . '</p>';
		echo '<p>' . esc_html__('* If you want to remove the disclaimer from all the posts, simply delete this plugin.', 'comments-disclaimer') . '</p>';
    }

// Function to add Comments Disclaimer to exisitng functions when Generate button is clicked. It checks for *posts , *published_content.
    public static function handle_add_comments_disclaimer_to_existing_posts_cdwp() {
		if (isset($_POST['action']) && $_POST['action'] === 'add_comments_disclaimer_to_existing_posts') {
		$args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'meta_query' => array(
            array(
                'key' => '_comment_disclaimer_checkbox',
                'compare' => 'NOT EXISTS',
            ),
        ),
		);

		$posts = new WP_Query($args);

		if ($posts->have_posts()) {
        while ($posts->have_posts()) {
            $posts->the_post();
            $post_id = get_the_ID();
            update_post_meta($post_id, '_comment_disclaimer_checkbox', 1);
        }
		}

		wp_reset_postdata();
		}

		wp_safe_redirect(admin_url('edit-comments.php?page=comments-disclaimer-plugin'));

		exit;

	}
    





   
//Function to load css and js files.
    public static function enqueue_comments_disclaimer_assets_cdwp() {
        wp_enqueue_style('comments-disclaimer-style', plugin_dir_url(__FILE__) . 'comments-disclaimer.css', array(), '1.0');
        wp_enqueue_script('comments-disclaimer-script', plugin_dir_url(__FILE__) . 'comments-disclaimer.js', array('jquery'), '1.0', true);
    }
//Add meta box inside post editors
    public static function add_custom_meta_box_cdwp() {
        add_meta_box('comment_disclaimer_meta_box', __('Comment Disclaimer', 'comments-disclaimer'), array(__CLASS__, 'render_comment_disclaimer_checkbox_cdwp'), 'post', 'side', 'high');
    }

    public static function render_comment_disclaimer_checkbox_cdwp($post) {
        $checkbox_value = get_post_meta($post->ID, '_comment_disclaimer_checkbox', true);
        wp_nonce_field('comment_disclaimer_nonce', 'comment_disclaimer_nonce');
        ?>
        <div class="misc-pub-section">
            <label for="comment_disclaimer_checkbox">
                <input type="checkbox" id="comment_disclaimer_checkbox" name="comment_disclaimer_checkbox" value="1" <?php checked($checkbox_value, 1); ?>>
                <?php _e('Display Comment Disclaimer', 'comments-disclaimer'); ?>
            </label>
        </div>
        <?php
    }
// Check for status of the meta box, and saves the data. Depends on this data Comments Diclaimer content is shown
    public static function save_comment_disclaimer_checkbox_cdwp($post_id) {
        if (!isset($_POST['comment_disclaimer_nonce']) || !wp_verify_nonce($_POST['comment_disclaimer_nonce'], 'comment_disclaimer_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $checkbox_value = isset($_POST['comment_disclaimer_checkbox']) ? 1 : 0;
        update_post_meta($post_id, '_comment_disclaimer_checkbox', $checkbox_value);
    }
//Comment Disclaimer content is shows depending on the conditions above.
    public static function add_comments_disclaimer_cdwp() {
        $post_id = get_the_ID();
        $checkbox_value = get_post_meta($post_id, '_comment_disclaimer_checkbox', true);

        if ($checkbox_value) {
            echo '<div class="comments-message">';
            
			
           echo '<p class="message-content">' . __('Disclaimer: The comments section is a public platform. The views expressed in the comments section belong to the individual commenters and do not necessarily reflect the official policy or position of the site or its authors. The site and its authors disclaim any liability for the comments posted.', 'comments-disclaimer') . '</p>';
			echo '<br>';
			echo '<p class="message-content" style="color:inherit;">' . __('Keep the comment section civil, focussed and respectful.', 'comments-disclaimer') . '</p>';
           
            echo '</div>';
        }
    }
	
	//Load other languages
	public static function load_comments_disclaimer_textdomain_cdwp() {
        load_plugin_textdomain('comments-disclaimer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }


	
	
    public static function uninstall_cdwp() {
    // Remove the custom text added after the comments section
    remove_action('comment_form_before', array(__CLASS__, 'add_comments_disclaimer_cdwp'), 15);

    // Delete comment disclaimers from all posts
    $args = array(
        'post_type'      => 'post',
        'posts_per_page' => -1,
        'meta_key'       => '_comment_disclaimer_checkbox',
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            delete_post_meta(get_the_ID(), '_comment_disclaimer_checkbox');
        }
        wp_reset_postdata();
    }

    // Remove the plugin page
    remove_submenu_page('edit-comments.php', 'comments-disclaimer-plugin');
}


	
}

Comments_Disclaimer_Plugin::init_cdwp();
