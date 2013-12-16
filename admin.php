<?php

class BeltwayPCOAdmin {
	function __construct() {
		if (!is_admin()) return; // this file only necessary for admin users
		
		add_action('init', array(&$this, 'handlePCOActions'));
		add_action('admin_menu', array(&$this, 'createAdminPage'));
		add_action('admin_init', array(&$this, 'registerSettings'));
	}
	
	function handlePCOActions() {
		if (isset($_GET['pco-action']) && current_user_can('manage_options')) {
			if ($_GET['pco-action'] == 'logout') {
				PCOAccess::deleteAccessToken();
			} else if ($_GET['pco-action'] == 'auth') {
				PCOAccess::deleteAccessToken();
				$pco = new PCOAccess();
				if ($pco->hasCredentials()) {
					$thisURL  = (is_ssl()) ? "https://" : "http://";
					$thisURL .= $_SERVER['HTTP_HOST'];
					$thisURL .= $_SERVER['REQUEST_URI'];
					$auth = $pco->authenticate($thisURL);
					
					// if we're still here, there was no redirect, so this must be the callback
					header('Location: ?page=pco_connect');
				}
			} else if ($_GET['pco-action'] == 'finalizeauth') {
				$pco = new PCOAccess();
			}
		}
	}
	
	function createAdminPage() {
		add_options_page('PCO Connection', 'PCO Connection', 'manage_options', 'pco_connect', array($this, 'adminPageContent'));
	}
	
	function adminPageContent() {
		$pco = new PCOAccess();
		?><div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>Planning Center Online Connection</h2>
			<form method="post" action="options.php">
				<?php
				settings_fields('pco-connection-group'); // This prints out all hidden setting fields
				do_settings_sections('pco-connection-admin'); // prints out form options
				?>
				<?php submit_button(); ?>
			</form>
			<div style="background-color: #EEE; padding: 10px;">
				<?php
				if ($pco->okay()) {
					echo '<h3>Connection Status: <span style="color: #090">Connected!</span></h3>';
					$o = $pco->api->getOrganization();
					echo '<p>';
					echo 'Organization: '.$o->name."<br />\n";
					echo 'Account Owner: '.$o->owner_name."\n";
					echo '</p>';
					echo '<a class="button" href="?page=pco_connect&amp;pco-action=logout">Log out</a>';
				} else {
					echo '<h3>Connection Status: <span style="color: #900">Disconnected</span></h3>';
					if ($pco->hasCredentials()) {
						echo '<p>Press the button below to authenticate with the above key and secret:</p>';
						echo '<a class="button" href="?page=pco_connect&amp;pco-action=auth">Authenticate</a>';
					} else {
						echo '<p>Please enter a consumer key and secret to authenticate.</p>';
					}
				}
				?>
			</div>
		</div>
		<?php
	}
	
	public function registerSettings() {
		register_setting(
			'pco-connection-group', // the group of settings
			PCOAccess::PCO_CONSUMER_OPTION, // the DB & HTML input name of the settings
			array(&$this, 'sanitizeCreds') // the sanitizing callback
		);
		
		add_settings_section(
			'consumer-credentials',
			'Consumer Credentials',
			array(&$this, 'sectionCredentials'),
			'pco-connection-admin'
		);      
		
		// Consumer Key
		add_settings_field(
			'pco_consumer_key', 
			'Consumer Key', 
			array(&$this, 'fieldConsumerKey'), 
			'pco-connection-admin',
			'consumer-credentials'                 
		);
		
		// Consumer Secret
		add_settings_field(
			'pco_consumer_secret', 
			'Consumer Secret', 
			array(&$this, 'fieldConsumerSecret'), 
			'pco-connection-admin',
			'consumer-credentials'                 
		);
	}
	
	public function sanitizeCreds($input) {
		if (is_object($input)) return $input;
		$obj = (object)array(
			'key' => substr(@$input['key'], 0, 100),
			'secret' => substr(@$input['secret'], 0, 100),
		);
		return $obj;
	}
	
	public function sectionCredentials() {
		?>
		<p>
			You'll need to request a Consumer Key and Secret from Planning Center (see <a href="http://get.planningcenteronline.com/api/">the PCO API documentation</a>). You can do this by emailing <a href="mailto:support@planningcenteronline.com">support@planningcenteronline.com</a>.
		</p>
		<p>
			Once you have these, enter them here.
		</p>
		<?php
	}
	
	public function fieldConsumerKey() {
		$creds = maybe_unserialize(get_option(PCOAccess::PCO_CONSUMER_OPTION));
		?>
		<input type="text" id="pco-key" name="<?php echo PCOAccess::PCO_CONSUMER_OPTION; ?>[key]" value="<?php echo @$creds->key; ?>" />
		<?php
	}
	
	public function fieldConsumerSecret() {
		$creds = maybe_unserialize(get_option(PCOAccess::PCO_CONSUMER_OPTION));
		?>
		<input type="text" id="pco-secret" name="<?php echo PCOAccess::PCO_CONSUMER_OPTION; ?>[secret]" value="<?php echo @$creds->secret; ?>" />
		<?php
	}
}
new BeltwayPCOAdmin();