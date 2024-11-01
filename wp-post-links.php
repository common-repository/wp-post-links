<?php
/*
Plugin Name: WP Post Links
Plugin Script: wp-post-links.php
Plugin URI: http://marto.lazarov.org/plugins/wp-post-links
Description:
Version: 0.0.1
Author: mlazarov
Author URI: http://marto.lazarov.org
*/

if (!class_exists('wp_post_links')) {
	class wp_post_links {

		function wp_post_links() {
			$this->__construct();

		}
		function __construct() {

			$this->plugin_url = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
			$stored_options = get_option('wp_post_links_options');
			$this->options = (array)(is_serialized($stored_options)) ? unserialize($stored_options) : $stored_options;

			if($this->options['lastupdate']<strtotime('-1 hour')){

			}
			if(!$this->options['cnt']) $this->options['cnt'] = 3;

			add_action("admin_menu", array (&$this,"admin_menu_link"));
			add_filter('the_content', array (&$this,"postContent"));

		}
		function admin_menu_link() {
			add_management_page('WP Post Links', 'WP Post Links', 8, basename(__FILE__), array (&$this, 'admin_options_page'));
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), array (&$this,'filter_plugin_actions'), 10, 2);
		}
		function filter_plugin_actions($links, $file) {

			$settings_link = '<a href="tools.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
			array_unshift($links, $settings_link); // before other links

			return $links;
		}

		function postContent($content){

			if(is_single()){
				$links = $this->getLinks();
				if(count($links)){
					$links_html = '<ul class="wp-post-links">';
					foreach($links as $link){
						$links_html.= '<li><a href="'.htmlspecialchars($link['url']).'">'.htmlspecialchars($link['title']).'</a></li>';
					}
					$content.= $links_html;
				}
			}
			return $content;
		}
		function getLinks(){
			global $posts;

			$post_id = $posts[0]->ID;

			$meta_values = get_post_meta($post_id, 'wp-post-links');
			if(count($meta_values)){
				return $meta_values[0];
			}

			$links = array();
			for($i=1;$i<=$this->options['cnt'];$i++){
				$link = $this->options['links'][rand(0,count($this->options['links'])-1)];
				if(!in_array($link,$links))
					$links[] = $link;
			}
			add_post_meta($post_id, 'wp-post-links',$links,1);
			return $links;

		}

		function admin_options_page() {
			if ($_POST['wp-posts-links']) {
				$this->options['url'] = $_POST['url'];
				$this->options['cnt'] = $_POST['cnt']>0?(int)$_POST['cnt']:3;

				if($_POST['getnew']){
					$this->updateLinks(1);
				}
				//var_dump($this->options);
				update_option('wp_post_links_options', serialize($this->options));
			}
			?>
			<div class="wrap">
				<div id="dashboard" style="width:650px;padding:10px;">
					<h3>WP Post Links (<?php echo count($this->options['links']);?> - <?php echo date('d.m.Y H:i:s',$this->options['lastupdate']);?>)</h3>
					<form method="post">
						<div  style="">
							Text file URL:<br/>
							<input type="text" name="url" value="<?php echo $this->options['url'];?>" size="100"/><br/><br/>
							Number of links to show:<br/>
							<input type="text" name="cnt" value="<?php echo $this->options['cnt'];?>" size="5"/><br/><br/>
							<input type="checkbox" name="getnew" value="1"/> Get new links<br/><br/>
							<input type="submit" name="wp-posts-links" class="button-primary" value="Save" />
						</div>
					</form>
				</div>
			</div>
			<?php
		}
		function updateLinks($print=false){
			if($print) echo "Geting new links...<br/>";
			$data = explode("\n",file_get_contents($this->options['url'], FILE_BINARY));
			if($print) echo "Found ".count($data)." links</br>";
			$i=0;
			$links = array();
			foreach($data as $line){
				$info = explode("|",$line);
				if($info[1] && $info[0]){
					$links[$i]['url'] = trim($info[1]);
					$links[$i]['title'] = trim($info[0]);
					$i++;
				}
			}
			$this->options['links'] = $links;
			$this->options['lastupdate'] = time();

		}
	}
	if (class_exists('wp_post_links')) {
		$wp_post_links_var = new wp_post_links();
	}
}


?>
