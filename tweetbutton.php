<?php
/*
Plugin Name: TweetButton
Plugin URI: http://dcgws.com/resources/plugins-software/tweetbutton-wordpress/
Description: This plugin places the official Twitter Tweet Button on your posts, pages or shortcode.
Version: 3.1.0
Author: DCGWS Internet Solutions
Author URI: http://dcgws.com
*/

function tb_options() {
	add_menu_page('TweetButton', 'TweetButton', 8, basename(__FILE__), 'tb_options_page',plugin_dir_url( __FILE__ ).'tweetbutton-icon.png');
	add_submenu_page(basename(__FILE__), 'Settings', 'Settings', 8, basename(__FILE__), 'tb_options_page');
}
/**
* Build up all the options for the button
*/
function tb_add_params() {
	// get the post object
	global $post;
	// get the permalink
    if (get_post_status($post->ID) == 'publish') {
		$url = get_permalink();
	}
    $button .= ' data-url="' .$url.'"';
	$title = preg_replace('/\s\s+/','%20',get_the_title($post->ID));
	$arrIni = array("%","?","#");
	$arrEnd = array("%25","%3f","%23");
	$title = str_replace($arrIni, $arrEnd, $title);
	$button .= ' data-text="'.$title.'"';
	if (get_option('tb_via') != '')
	{
		$button .= ' data-via="'.get_option('tb_via').'"';
	}
	if (get_option('tb_hashtag') != '')
	{
		$button .= ' data-hashtags="'.get_option('tb_hashtag').'"';
	}
	if (get_option('tb_lang'))
	{
		$button .= ' data-lang="'.get_option('tb_lang').'"';
	}
	if (get_option('tb_related') != 'no') {
		// first lets see if the post has the custom field
		if (($related = get_post_meta($post->ID, 'tb_related')) != false) {
			// first split them out
			$related = explode(',', $related[0]);
			// go through and urlencode
			foreach($related as $row => $tag) {
				$related[$row] = urlencode(trim($tag));
			}
			// nope so lets use them
			$button .= ' data-related="' . implode(',', $related).'"';
		} else if (($tags = get_the_tags()) != false) {
			// ok, grab them off the post tags
			$related = array();
			foreach ($tags as $tag) {
				$related[] = urlencode($tag->name);
			}
			$button .= ' data-related="' . implode(',', $related).'"';
		} else if (($related = get_option('tb_related_accounts')) != '') {
			// first split them out
			$related = explode(',', $related);
			// go through and urlencode
			foreach($related as $row => $tag) {
				$related[$row] = urlencode(trim($tag));
			}
			// add them all back together
			$button .= ' data-related="' . implode(',', $related).'"';
		}
	}
	return $button;
}
/**
* Generate the anchor render of the button
*/
function tb_generate_tweetbutton() {
	// build up the outer style
	$button = '<div class="TweetButton_button" style="' . get_option('tb_style') . '">';
	$button .= '<a href="https://twitter.com/share" class="twitter-share-button" '.tb_add_params().'>Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></div>';
	// return the iframe code
    return $button;
}
function tb_generate_static_button() {

	if (get_post_status($post->ID) == 'publish') {

        $url = get_permalink();

		return

		'<div class="TweetButton_button" style="' . get_option('tb_style') . ';height:20px;margin-bottom:5px;"><a href="http://twitter.com/share' .tb_add_params() . '"><img src="'.plugin_dir_url(__FILE__).'images/tweet.png" style="border:none" /></a></div>';

	} else {

		return;

	}

}

/**
* Gets run when the content is loaded in the loop
*/
function tb_update($content) {
    global $post;
    // add the manual option, code added by kovshenin
    if (get_option('tb_where') == 'manual') {
        return $content;
	}
    // is it a page
    if (get_option('tb_display_page') == null && is_page()) {
        return $content;
    }
	// are we on the front page
    if (get_option('tb_display_front') == null && is_home()) {
        return $content;
    }
	// are we in a feed
    if (is_feed()) {
		$button = tb_generate_static_button();
		$where = 'tb_rss_where';
    } else {
		$button = tb_generate_tweetbutton();
		$where = 'tb_where';
	}
	// are we displaying in a feed
	if (is_feed() && get_option('tb_display_rss') == null) {
		return $content;
	}
	// are we just using the shortcode
	if (get_option($where) == 'shortcode') {
		return str_replace('[TweetButton]', $button, $content);
	} else {
		// if we have switched the button off
		if (get_post_meta($post->ID, 'TweetButton') == null) {
			if (get_option($where) == 'beforeandafter') {
				// adding it before and after
				return $button . $content . $button;
			} else if (get_option($where) == 'before') {
				// just before
				return $button . $content;
			} else {
				// just after
				return $content . $button;
			}
		} else {
			// not at all
			return $content;
		}
	}
}
// Manual output
function TweetButton() {
    if (get_option('tb_where') == 'manual') {
        return tb_generate_tweetbutton();
    } else {
        return false;
    }
}
// Remove the filter excerpts
// Code added by Soccer Dad
function tb_remove_filter($content) {
	if (!is_feed()) {
    	remove_action('the_content', 'tb_update');
	}
    return $content;
}
/**
* Adds a TweetButton-title meta title, provides a much more accurate title for the button
*/
function tb_head() {
	// if its a post page
	if (is_single()) {
		global $post;
		$title = get_the_title($post->ID);
		echo '<meta name="TweetButton-title" content="' . strip_tags($title) . '" />';
	}
}
function tb_options_page() {
?>

<div class="wrap">
  <div class="icon32" id="icon-options-general"><br/>
  </div>
  <h2>Settings for TweetButton Integration</h2>
  <p>This plugin will install the TweetButton widget for each of your blog posts in both the content of your posts and the RSS feed.
    It can be easily styles in your blog posts and is referenced by the id <code>TweetButton_button</code>. </p>
  <form method="post" action="options.php">
    <?php
        // New way of setting the fields, for WP 2.7 and newer
        if(function_exists('settings_fields')){
            settings_fields('tb-options');
        } else {
            wp_nonce_field('update-options');
            ?>
    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="tb_lang,tb_ping,tb_where,tb_style,tb_count,tb_display_page,tb_display_front,tb_display_rss,tb_display_feed,tb_via,tb_hashtag,tb_related,tb_related_accounts" />
    <?php
        }
    ?>
    <table class="form-table">
      <tr>
      <tr>
        <th scope="row" valign="top"> Display </th>
        <td><input type="checkbox" value="1" <?php if (get_option('tb_display_page') == '1') echo 'checked="checked"'; ?> name="tb_display_page" id="tb_display_page" group="tb_display"/>
          <label for="tb_display_page">Display the button on pages</label>
          <br/>
          <input type="checkbox" value="1" <?php if (get_option('tb_display_front') == '1') echo 'checked="checked"'; ?> name="tb_display_front" id="tb_display_front" group="tb_display"/>
          <label for="tb_display_front">Display the button on the front page (home)</label>
          <br/>
          <input type="checkbox" value="1" <?php if (get_option('tb_display_rss') == '1') echo 'checked="checked"'; ?> name="tb_display_rss" id="tb_display_rss" group="tb_display"/>
          <label for="tb_display_rss">Display the image button in your feed, only available as <strong>a small size</strong> widget without retweet count.</label></td>
      </tr>
      
        <th scope="row" valign="top"> Position </th>
        <td><select name="tb_where">
            <option <?php if (get_option('tb_where') == 'before') echo 'selected="selected"'; ?> value="before">Before</option>
            <option <?php if (get_option('tb_where') == 'after') echo 'selected="selected"'; ?> value="after">After</option>
            <option <?php if (get_option('tb_where') == 'beforeandafter') echo 'selected="selected"'; ?> value="beforeandafter">Before and After</option>
            <option <?php if (get_option('tb_where') == 'shortcode') echo 'selected="selected"'; ?> value="shortcode">Shortcode [TweetButton]</option>
            <option <?php if (get_option('tb_where') == 'manual') echo 'selected="selected"'; ?> value="manual">Manual</option>
          </select></td>
      </tr>
      <tr>
        <th scope="row" valign="top"> RSS Position </th>
        <td><select name="tb_rss_where">
            <option <?php if (get_option('tb_rss_where') == 'before') echo 'selected="selected"'; ?> value="before">Before</option>
            <option <?php if (get_option('tb_rss_where') == 'after') echo 'selected="selected"'; ?> value="after">After</option>
            <option <?php if (get_option('tb_rss_where') == 'beforeandafter') echo 'selected="selected"'; ?> value="beforeandafter">Before and After</option>
            <option <?php if (get_option('tb_where') == 'shortcode') echo 'selected="selected"'; ?> value="shortcode">Shortcode [TweetButton]</option>
          </select></td>
      </tr>
      <tr>
        <th scope="row" valign="top"><label for="tb_style">Styling</label></th>
        <td><input type="text" value="<?php echo htmlspecialchars(get_option('tb_style')); ?>" name="tb_style" id="tb_style" />
          <span class="description">Add style to the div that surrounds the button E.g. <code>float: left; margin-right: 10px;</code></span></td>
      </tr>
      <tr>
        <th scope="row" valign="top"> <label for="tb_via">Via</label>
        </th>
        <td> Via @
          <input type="text" value="<?php echo get_option('tb_via'); ?>" name="tb_via" id="tb_via" />
          <span class="description">Please use the format of 'yourname', not 'via @yourname'. Leave blank to disable.</span></td>
      </tr>
      <tr>
        <th scope="row" valign="top"> <label for="tb_hashtag">Hashtag</label>
        </th>
        <td> Hashtag #
        	<input type="text" value="<?php echo get_option('tb_hashtag'); ?>" name="tb_hashtag" id="tb_hashtag" />
          <span class="description">Please use the format of 'hashtag', not 'hashtag #hashtag'. Leave blank to disable.</span></td>
      </tr>
      <tr>
        <th scope="row" valigh="top"> Related Account</th>
        <td><input type="radio" value="yes" name="tb_related" group="tb_related" id="tb_related_on" <?php if (get_option('tb_related') == 'yes') echo 'checked="checked"'; ?> />
          <label for="tb_related_oon">Use a related account</label>
          <br/>
          <input type="radio" value="no" name="tb_related" group="tb_related" id="tb_related_off" <?php if (get_option('tb_related') == 'no') echo 'checked="checked"'; ?> />
          <label for="tb_related_off">Don't use a related account.</label>
          <br/>
          <label for="tb_related_accounts">Use this related accounts.</label>
          <input type="text" value="<?php echo get_option('tb_related_accounts'); ?>" name="tb_related_accounts" />
          You should use the format <strong>account:description</strong>. For example, <strong>dcgws:Web Development Company</strong><br/>
          <br/>
          <span class="description">Recommend your favorite twitter account to your readers. You can override this by specifying a related account on a per post basis, by using the custom field tb_related (seperated the account and description with a colon (:),).</span></td>
      </tr>
      <tr><th scope="row" valigh="top">Language</th>
        <td><select id="tb_lang" name="tb_lang" style="font-size: 13px;padding:4px 5px;">
            <option value="en" <?php if (get_option('tb_lang') == 'en') echo 'selected="selected"'; ?>>English</option>
            <option value="fr" <?php if (get_option('tb_lang') == 'fr') echo 'selected="selected"'; ?>>French</option>
            <option value="de" <?php if (get_option('tb_lang') == 'de') echo 'selected="selected"'; ?>>German</option>
            <option value="es" <?php if (get_option('tb_lang') == 'es') echo 'selected="selected"'; ?>>Spanish</option>
            <option value="ja" <?php if (get_option('tb_lang') == 'ja') echo 'selected="selected"'; ?>>Japanese</option>
          </select>
          <br />
          <span class="description">This is the language that the button will be displayed in on your website.</span></td>
      </tr>
    </table>
    <p class="submit">
      <input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
    </p>
  </form>
</div>
<?php
}
// On access of the admin page, register these variables (required for WP 2.7 & newer)
function tb_init(){
    if(function_exists('register_setting')){
        register_setting('tb-options', 'tb_display_page');
        register_setting('tb-options', 'tb_display_front');
        register_setting('tb-options', 'tb_display_rss');
        register_setting('tb-options', 'tb_via', 'tb_sanitize_username');
        register_setting('tb-options', 'tb_hashtag');
        register_setting('tb-options', 'tb_style');
        register_setting('tb-options', 'tb_where');
        register_setting('tb-options', 'tb_rss_where');
        register_setting('tb-options', 'tb_ping');
        register_setting('tb-options', 'tb_related');
        register_setting('tb-options', 'tb_related_accounts');
        register_setting('tb-options', 'tb_lang');
    }
}
function tb_sanitize_username($username){
    return preg_replace('/[^A-Za-z0-9_]/','',$username);
}
// Only all the admin options if the user is an admin
if(is_admin()){
    add_action('admin_menu', 'tb_options');
    add_action('admin_init', 'tb_init');
}
// Set the default options when the plugin is activated

function tb_activate(){
	add_option('tb_where', 'before');
    add_option('tb_rss_where', 'before');
    add_option('tb_via');
    add_option('tb_hashtag');
    add_option('tb_style', 'float: right; margin-left: 10px;');
    add_option('tb_display_page', '1');
    add_option('tb_display_front', '1');
    add_option('tb_display_rss', '1');
    add_option('tb_ping', 'on');
    add_option('tb_related', 'off');
    add_option('tb_lang', 'en');
}
add_filter('the_content', 'tb_update', 8);
add_filter('get_the_excerpt', 'tb_remove_filter', 9);
add_action('wp_head', 'tb_head');
// load in the other files
register_activation_hook( __FILE__, 'tb_activate');

