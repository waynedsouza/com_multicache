<?php
/*
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 */
// No direct access
defined('_JEXEC') or die();
$memcache_loaded = extension_loaded('memcache') ? 'memcache is enabled on your server' : 'memcache is not available on your server';
$memcache_loaded_color = extension_loaded('memcache') ? 'green' : 'red';
$memcached_loaded = extension_loaded('memcached') ? 'memcached is enabled on your server' : 'memcached is not available on your server';
$memcached_loaded_color = extension_loaded('memcached') ? 'green' : 'red';
$ion_cube_loaded = extension_loaded('ionCube Loader') ? 'ionCube loader is enabled' : 'ionCubde loader is not available';
$ion_cube_loaded_color = extension_loaded('ionCube Loader') ? 'green' : 'red';
$curl_enabled = function_exists('curl_version') ? 'Curl extension is enabled' : 'Curl is not available on your server';
$curl_enabled_color = function_exists('curl_version') ? 'green' : 'red';
if (file_exists(JPATH_ROOT . '/' . 'plugins' . '/' . 'system' . '/' . 'multicache' . '/' . 'multicache.php'))
:
    ?>

<div class="span12">
	<div style="text-align: justify;">
		<h1>Multicache Package for Joomla 3 installed successfully!</h1>
		<h1>Please read the following</h1>
		<p>
			<strong>The package installer has installed the following</strong>
		</p>
		<ol>
			<li><strong>com_multicache</strong><strong><sup><strong>�</strong></sup>
					- Component</strong></li>
			<li><strong>fastcache - library</strong></li>
			<li><strong>Multicache</strong><strong><sup><strong>�</strong></sup>
					- System Plugin</strong></li>
			<li><strong>Multicache</strong><strong><sup><strong>�</strong></sup>
					- User Plugin</strong></li>
		</ol>
		<strong>This component is <br /></strong>
		<ul>
			<li><strong>is a fast cache for your Joomla site</strong></li>
			<li><strong>it performs continuous assessment and speed optimization
			</strong></li>
			<li><strong>provides granular reports derived from GTmetrix.com </strong></li>
			<li><strong>performs Javascript Tweaks to get the best speed.</strong></li>
			<li><strong>performs Simulation Testing on your website to determine
					the best load time settings. </strong></li>
			<li><strong>uses Google Analytics to ensure that the most important
					pages of your site are stored to memcache and the remaining pages
					stored to file cache.</strong></li>
			<li><strong>Allows eCommerce carts and currencies to be page cached.
			</strong></li>
		</ul>
		<br /> If it is the first time you are using Multicache<sup>�</sup>,
		please note the following
	</div>
	<div style="text-align: justify;">
		<ol>
			<li style="text-align: justify;"><strong>It requires the <a
					href="http://memcached.org/" target="_blank">memcached daemon</a>
					to be installed and running on your server
			</strong></li>
			<li style="text-align: justify;"><strong>It requires the php
					extension of <a href="http://pecl.php.net/package/memcache"
					target="_blank">memcache</a> to be enabled on your server
			</strong> -<span style="color:<?php echo $memcache_loaded_color;?>"> <?php echo $memcache_loaded;?></span></li>
			<li style="text-align: justify;"><strong>It requires the php
					extension of <a href="http://pecl.php.net/package/memcached"
					target="_blank">memcached</a> to be enabled on your server
			</strong> -<span style="color:<?php echo $memcached_loaded_color;?>"> <?php echo $memcached_loaded;?></span></li>
			<li style="text-align: justify;"><strong>It requires the <a
					href="http://php.net/manual/en/curl.installation.php"
					target="_blank">curl extension</a> to be enabled. -<span style="color:<?php echo $curl_enabled_color;?>"> <?php echo $curl_enabled;?>
			</span></strong></li>
			<li style="text-align: justify;"><strong>It requires the <a
					href="https://www.ioncube.com/loaders.php" target="_blank">ionCube
						loader</a> to be enabled. CPanel users can refer to <a
					href="http://docs.whmcs.com/Ioncube_Installation_Tutorial"
					target="_blank">these instructions</a> -<span style="color:<?php echo $ion_cube_loaded_color;?>"> <?php echo $ion_cube_loaded;?></span>.
			</strong></li>
			<li style="text-align: justify;"><strong>If you enable simulation, it
					requires to be cron&#39;d. Please navigate to <a
					href="index.php?option=com_multicache&amp;view=config&amp;layout=edit&amp;id=1#page-optimisation"
					target="_blank">config</a> for more information of the cron url.<br />
			</strong></li>
			<li style="text-align: justify;"><strong>Please test the product with
					the multicache debug before placing in production.</strong></li>
		</ol>
	</div>

	<div style="text-align: justify;">
		Although Multicache<sup>�</sup> has been installed, most features are
		<strong>disabled</strong> right now. You must first use the config
		panel to enable whichever part you want to use and save it. Please <strong><a
			href="http://multicache.org/documentation/all/">read the
				documentation</a> </strong>completely before you start using
		Multicache.
	</div>
	<div style="text-align: justify;">
		<h3>
			<strong>Benchmark Testing</strong>
		</h3>
	</div>
	<div style="text-align: justify;">
		To perform continuous benchmarking of your site, you will need to
		configure Multicache<sup>�</sup> <a
			href="index.php?option=com_multicache&amp;view=config&amp;layout=edit&amp;id=1#page-optimisation"
			target="_blank">optimization</a> . This requires that you have a <a
			href="http://gtmetrix.com/" target="_blank">GTMetrix account </a>.
	</div>
	<div style="text-align: justify;">
		<h3>
			<strong>Page Distribution</strong>
		</h3>
	</div>
	<div style="text-align: justify;">
		Multicache uses Google Analytics to perform the website page
		distribution analysis. You will need to configure <a
			href="index.php?option=com_multicache&amp;view=config&amp;layout=edit&amp;id=1#page-parta">page
			distribution</a> with your Google API credentials. If you do not have
		an API account you can always sign up with <a
			href="https://code.google.com/apis/console/" target="_blank">Google</a>.
	</div>
	<div style="text-align: justify;">
		If you do not use Google Analytics you can manually tell Multicache
		which pages to memcache. Please use the <a
			href="index.php?option=com_multicache&amp;view=config&amp;layout=edit&amp;id=1#page-partb"
			target="_blank">additional page cache urls</a> on the Advanced tab to
		enter the urls. The urls may be either entered one per line or comma
		separated or tab separated.
	</div>
	<div style="text-align: justify;">
		<strong><span style="color: red;">IMPORTANT</span></strong> : If you
		do not initialize Page Distribution with either Google Analytics or
		Additional Page Cache urls or both and set the cache handler to
		fastcache, your pages will be file cached as default.
	</div>
	<div style="text-align: justify;">
		<h3>
			<strong>Multicache Plugin</strong>
		</h3>
	</div>
	<div style="text-align: justify;">
		Multicache requires that the system multicache plugin be enabled to
		operate. Please browse to your admin console and <a
			href="index.php?option=com_plugins&amp;view=plugins" target="_blank">enable
			the multicache plugin</a>.
	</div>
	<div style="text-align: justify;">
		<strong><span style="color: red;">IMPORTANT LOGS :</span></strong>:
		Please inspect the logs to determine that your setup procedure is
		working appropriately
	</div>
	<div style="text-align: justify;">
		<strong><span style="color: red;">IMPORTANT UNINSTALL INFORMATION :</span></strong>
		If you need to uninstall Multicache please ensure that cache handler
		is not set to fastcache, prior to uninstalling. If for some reason you
		uninstall multicache with the cache handler set to fastcache &#39;DO
		NOT PANIC&#39;, simply open configuration.php on your site root and
		set cache_handler to &#39;file&#39;.<br />
	</div>
	<div style="text-align: justify;">
		<strong><span style="color: red;">Debug :</span> </strong>
		<p>Multicache provides a debug separate from Joomla Global debug. This
			is due to the fact that page caching is not active by default in
			Joomla global debug. If you do set debug in Javascript tweaks then
			the necessary changes will only be administered to the specified url,
			thereby allowing you to test differences without hampering the entire
			site.</p>
		<h3>
			<strong>Advanced Users</strong>
		</h3>
		Advanced users can find more information and hacks on further
		improving site speeds on our website <a
			href="http://multicache.org/table/documentation/multicache-hacks/"
			target="_blank">Multicache.org</a>
	</div>


</div>




<?php
endif;

?>