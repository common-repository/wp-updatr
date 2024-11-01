<div class='wrap'>
	<div class='wpupdatr_welcome'>
		<div class='logo'>
			<img src='<?php echo plugins_url( 'assets/images/wp-updatr-logo.png', WPUPDATR_PLUGIN_URL ); ?>' title='' alt=''/>
		</div>
		<div class='getting_started'>
			<iframe width="560" height="315" src="https://www.youtube.com/embed/bvauZVuD2Ss" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
		</div>
		<div class='intro'>
			<div class='heading'>
				<h2>What Is WP Updatr?</h2>
			</div>
			<p>WP Updatr is focused on making WordPress product owners lives easier by allowing them to maintain and release updates for their plugins and themes using our platform.</p>
		</div>
		<div class='features'>
			<div class='heading'>
				<h2>Why Use WP Updatr?</h2>
			</div>
			<div class='feat2'>
				<h2>Unlimited Products</h2>
				<p>Create as many WordPress Plugins and Themes in the WP Updatr Dashboard as you want. We won't limit you to how many products you offer updates for through WP Updatr.</p>
			</div>
			<div class='feat2'>
				<h2>Unlimited Updates</h2>
				<p>We won't limit you to the number of updates you release. Some products require only occassional updates while others require frequent updates. Our service handles both with ease. </p>
			</div>			
		</div>	
		<div class='cta'>
			<h2>Sign Up For Your FREE WP Updatr Account Today!</h2>
			<p>Load your WordPress product and release your first update in a matter of minutes.</p>
			<form method='GET' action='https://app.wpupdatr.com/wp-admin/admin-ajax.php'>
				<input type='text' name='email' value='<?php echo get_option('admin_email'); ?>' />
				<input type='submit' name='wpupdatr_signup' />
			</form>
		</div>	
		<div class='features'>
			<div class='heading'>
				<h2>Integrates Seamlessly With</h2>
			</div>
			<div class='feat1'>
				<h2>Easy Digital Downloads</h2>
				<p>Easy Digital Downloads makes it so easy to sell digital products along with their Software Licenses Add On. Let us host your product updates through this. 
			</div>
			<div class='feat1'>
				<h2>Woocommerce</h2>
				<p>Sell digital products through Woocommerce and we'll generate API keys for each purchase on the fly.</p>
			</div>
			<div class='feat1'>
				<h2>Paid Memberships Pro</h2>
				<p>Selling a membership with access to your WordPress products? We'll generate an API key for them upon checkout for them to receive updates.</p>
			</div>			
		</div>	
		
		<div class='skiptosettings'>
			<a href='<?php echo admin_url( '/options-general.php?page=wp-updatr&ignore=true' ); ?>'>Configure WP Updatr</a>
		</div>
	</div>
</div>
