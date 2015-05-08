<?php
/**
 * twitter-timeline-php : Twitter API 1.1 user timeline implemented with PHP, a little JavaScript, and web intents
 * 
 * @package		twitter-timeline-php
 * @author		Kim Maida <contact@kim-maida.com>
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link		http://github.com/kmaida/twitter-timeline-php
 * @credits		Based on a mish-mash of code from Rivers, Jimbo / James Mallison, and others at <http://stackoverflow.com/questions/12916539/simplest-php-example-for-retrieving-user-timeline-with-twitter-api-version-1-1>
 *
**/
if (class_exists('TwitterAPITimeline') === false) {
	class TwitterAPITimeline
	{
		private $consumer_key;
		private $consumer_secret;
		private $oauth_access_token;
		private $oauth_access_token_secret;
		private $getfield;
		protected $oauth;
		public $url;
		 
		public function __construct(array $settings)
		{
			if (!in_array('curl', get_loaded_extensions())) 
			{
				throw new Exception('You need to install cURL, see: http://curl.haxx.se/docs/install.html');
			}
			 
			if (!isset($settings['oauth_access_token'])
				|| !isset($settings['oauth_access_token_secret'])
				|| !isset($settings['consumer_key'])
				|| !isset($settings['consumer_secret']))
			{
				throw new Exception('Make sure you are passing in the correct parameters');
			}
	
			$this->oauth_access_token = $settings['oauth_access_token'];
			$this->oauth_access_token_secret = $settings['oauth_access_token_secret'];
			$this->consumer_key = $settings['consumer_key'];
			$this->consumer_secret = $settings['consumer_secret'];
		}
		 
		public function setGetfield($string)
		{
			$search = array('#', ',', '+', ':');
			$replace = array('%23', '%2C', '%2B', '%3A');
			$string = str_replace($search, $replace, $string);	 
			
			$this->getfield = $string;
			
			return $this;
		}
		
		public function getGetfield()
		{
			return $this->getfield;
		}
		
		private function buildBaseString($baseURI, $method, $params) 
		{
			$return = array();
			ksort($params);
			
			foreach($params as $key=>$value)
			{
				$return[] = "$key=" . $value;
			}
		
			return $method . "&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $return)); 
		}
		
		private function buildAuthorizationHeader($oauth) 
		{
			$return = 'Authorization: OAuth ';
			$values = array();
			
			foreach($oauth as $key => $value)
			{
				$values[] = "$key=\"" . rawurlencode($value) . "\"";
			}
			
			$return .= implode(', ', $values);
			return $return;
		}
		
		public function buildOauth($url)
		{	 
			$consumer_key = $this->consumer_key;
			$consumer_secret = $this->consumer_secret;
			$oauth_access_token = $this->oauth_access_token;
			$oauth_access_token_secret = $this->oauth_access_token_secret;
			
			$oauth = array( 
				'oauth_consumer_key' => $consumer_key,
				'oauth_nonce' => time(),
				'oauth_signature_method' => 'HMAC-SHA1',
				'oauth_token' => $oauth_access_token,
				'oauth_timestamp' => time(),
				'oauth_version' => '1.0'
			);
			
			$getfield = $this->getGetfield();
			
			if (!is_null($getfield))
			{
				$getfields = str_replace('?', '', explode('&', $getfield));
				foreach ($getfields as $g)
				{
					$split = explode('=', $g);
					$oauth[$split[0]] = $split[1];
				}
			}
			
			$base_info = $this->buildBaseString($url, 'GET', $oauth);
			$composite_key = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
			$oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
			$oauth['oauth_signature'] = $oauth_signature;
			
			$this->url = $url;
			$this->oauth = $oauth;
			
			return $this;
		}
		
		public function performRequest($return = true)
		{
			if (!is_bool($return)) 
			{ 
				throw new Exception('performRequest parameter must be true or false'); 
			}
			
			$header = array($this->buildAuthorizationHeader($this->oauth), 'Expect:');
			
			$getfield = $this->getGetfield();
			
			$options = array( 
				CURLOPT_HTTPHEADER => $header,
				CURLOPT_HEADER => false,
				CURLOPT_URL => $this->url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false
			);
			
			if ($getfield !== '')
			{
				$options[CURLOPT_URL] .= $getfield;
			}
			
			$feed = curl_init();
			curl_setopt_array($feed, $options);
			$json = curl_exec($feed);
			curl_close($feed);
			
			if ($return) { return $json; }
		}	
	}
}
/**
 * twitter-timeline-php : Twitter API 1.1 user timeline implemented with PHP, a little JavaScript, and web intents
 * 
 * @package		twitter-timeline-php
 * @author		Kim Maida <contact@kim-maida.com>
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link		http://github.com/kmaida/twitter-timeline-php
 * @credits		Thank you to <http://viralpatel.net/blogs/twitter-like-n-min-sec-ago-timestamp-in-php-mysql/> for base for "time ago" calculations 
 *
**/
$settings = array(
	'consumer_key' => "txw93sNW4Izd3QuXlmSFZeayz",
	'consumer_secret' => "LtlnTVn3s4VxToTujRFF0VQxMqrXRS6h5ouLI31DtMkuyF0ljI",
	'oauth_access_token' => "258118357-BHHFt9K8Y4Fj6nvhVg3eGnKl2jILhdIjycEVcqpZ",
	'oauth_access_token_secret' => "XOlk45BCkOn2wtCogrwaBvlyPT5vw3rILefm9qwiJz6j4"
);
// Require the OAuth class
$getfield = '?screen_name=nilesays&count=5';
$twitter = new TwitterAPITimeline($settings);
$json = $twitter->setGetfield($getfield)
			  	->buildOauth('https://api.twitter.com/1.1/statuses/user_timeline.json', 'GET')
			 	->performRequest();
$twitter_data = json_decode($json, true);	// Create an array with the fetched JSON data

function formatTweet($tweet) {
	$linkified = '@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@';
	$hashified = '/(^|[\n\s])#([^\s"\t\n\r<:]*)/is';
	$mentionified = '/(^|[\n\s])@([^\s"\t\n\r<:]*)/is';
	$prettyTweet = preg_replace(
		array(
			$linkified,
			$hashified,
			$mentionified
		), 
		array(
			'<a href="$1" class="link-tweet" target="_blank">$1</a>',
			'$1<a class="link-hashtag" href="https://twitter.com/search?q=$2&src=hash" target="_blank">#$2</a>',
			'$1<a class="link-mention" href="http://twitter.com/$2" target="_blank">@$2</a>'
		), 
		$tweet
	);
	return $prettyTweet;
}?>
<!DOCTYPE html>
<!--[if lt IE 9]> <html lang="en" class="lt-ie9 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="en" class="no-js"> <!--<![endif]-->
<head>
	<meta charset="utf-8">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<title>Nile | An experience and service design consultancy</title>
	<meta name="keywords" content="Experience design, service design, usability, usability testing, user experience, Information architecture, accessibility audits" />
	<meta name="description" content="Nile is an experience and service design consultancy. We design services that are easy and effective for everyone." />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="stylesheet" href="css/style.css">
	<script src="js/modernizr.min.js"></script>
	<script src="//use.typekit.net/jwv1yje.js"></script>
	<script>try{Typekit.load();}catch(e){}</script>
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
  <link rel="apple-touch-icon" href="favicon.gif"/>
</head>
<body>

<header class="clearfix">
	<div class="container">
		<p class="full_site_notice">Full site launching Summer 2015</p>
		<img src="img/nile_logo.svg" id="logo" alt="Nile logo"/>
		<ul class="menu">
			<li><a href="#" title="View our archived site">Archived site</a></li>
			<li><a href="#contact" title="Contact us" id="contact_link">Contact</a></li>
		</ul>
	</div>
</header>

<section id="intro">
  <div class="background_image blur_bg"></div>
  <h1>We design services that are easy <span class="break">and effective for everyone</span></h1>
	<img class="fullbackground" src="img/banner.jpg" />
	<div class="gradient"></div>
</section>

<section class="blk_section blk_white" id="new_website">
	<div class="container">
		<h2 class="heading">We&rsquo;re working on a new website</h2>
		<p>We&rsquo;ve been very busy designing innovative new services for our clients, so our website is now a little out of date. We&rsquo;re working on a shiny new one. Meanwhile, the <a href="#" title="View our old website">old one is here</a> if you need it.</p>
	</div>	
</section>

<section class="blk_section blk_blue" id="what_we_do">
	<div class="container">
		<h2 class="heading">What we do</h2>
		<p>Nile is an <b>experience and service design consultancy</b>. We design in partnership with people, businesses and governments to bring services to life - services built to thrive in an increasingly digital world. And we ensure the development of culture and capabilities to sustain them.</p>
	</div>	
	<div class="semicircle"><img src="img/arrow.svg" alt="Arrow icon"/></div>
</section>

<section class="blk_section blk_white" id="work_at_nile">
	<div class="container">
		<h2 class="heading">Work at Nile</h2>
		<p>We value individuality and teamwork in equal measure, so we are always on the lookout for skilled and motivated team members. Is that you? Check out <a href="#" title="View our current job vacancies">our job vacancies</a>. We&rsquo;d love to hear from you.</p>
	</div>	
</section>

<section class="blk_section blk_twitter" id="nile_on_twitter">
	<div class="container">
		<img src="img/twitter_bird.svg" alt="Arrow icon"/>
		<p class="heading">@NileSays on Twitter</p>
	    <div class="swiper-container">
	        <ul class="swiper-wrapper" id="swiper-wrapper2">
				<?php
				foreach ($twitter_data as $tweet):
					$user =  $tweet['user'];	
					$userScreenName = $user['screen_name'];
					$userAccountURL = 'http://twitter.com/' . $userScreenName;
					# The tweet
					$id = $tweet['id'];
					$formattedTweet = formatTweet($tweet['text']);
					$statusURL = 'http://twitter.com/' . $userScreenName . '/status/' . $id;
				?>
					<li class="swiper-slide">
						<?php echo '<p>' . $formattedTweet . '</p>'; ?>		
					</li>			
				<?php 
				endforeach; ?>
	        </ul>
	        <div class="swiper-button-next"></div>
	        <div class="swiper-button-prev"></div>
	    </div>
	</div>	
</section>

<section class="blk_section" id="contact">
	<div class="container">
		<h2 class="heading">Get in touch</h2>
		<p>Find out how we can help your business. Drop us an <a href="mailto:hello@nilehq.com" title="Send us an email">email</a> or pop in to see us.</p>
	</div>	
</section>


<footer class="container">
	<div class="clearfix">
		<div class="column">
				<h3>London</h3>
					<ul>
					<li>The Trampery</li>
					<li>13-19 Bevenden Street</li>
					<li>London</li>
					<li><a href="https://maps.google.com/maps?q=N1 6AS" target="_blank" title="Search for us on Google Maps">N1 6AS</a></li>
					<li><a href="mailto:hello@nilehq.com" title="Send us an email" class="email">hello@nilehq.com</a></li>
					<li><a href="tel:+44 20 3393 0930" title="Give us a call">+44 20 3393 0930</a></li>
				</ul>
			</div>
			<div class="column">
				<h3>Edinburgh</h3>
					<ul>
					<li>13-15 Circus Lane</li>
					<li>Edinburgh</li>
					<li><a href="https://maps.google.com/maps?q=EH36SU" target="_blank" title="Search for us on Google Maps">EH3 6SU</a></li>
					<li><a href="mailto:hello@nilehq.com" title="Send us an email" class="email">hello@nilehq.com</a></li>
					<li><a href="tel:+44 131 220 5671" title="Give us a call">+44 131 220 5671</a></li>
				</ul>
			</div>
			<div class="column">
				<h3>Get social with us</h3>
				<ul class="social_list">
					<li><a href="https://twitter.com/nilesays" title="Visit us on Twitter" target="_blank" class="twitter"><img src="img/twitter_bird_white.svg" alt="Twitter icon"/></a></li>
					<li><a href="https://www.linkedin.com/company/nile-experience-&-service-design" title="Visit us on Linkedin" target="_blank" class="linkedin"><img src="img/linkedin_logo.svg" alt="Linkedin icon"/></a></li>
					<li><a href="https://www.facebook.com/NileHQ" title="Visit us on Facebook" target="_blank" class="facebook"><img src="img/facebook_icon.svg" alt="Facebook icon"/></a></li>
				</ul>
			</div>
			<div class="column">
				<h3>Join our mailing list</h3>
				<p>We'll let you know when our new site is live plus add you to our newsletter list</p>
				<!-- Mailchimp Embed -->
				<div id="mc_embed_signup" class="clearfix">
					<form action="//nilehq.us5.list-manage.com/subscribe/post-json?u=7a53a942e5b89e502038ff725&amp;id=c7ac0817a4&amp;c=?" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
				    <div id="mc_embed_signup_scroll clearfix">
							<div class="mc-field-group">
								<input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL">
							</div>
				    <div style="position: absolute; left: -5000px;"><input type="text" name="b_7a53a942e5b89e502038ff725_c7ac0817a4" tabindex="-1" value=""></div>
				    	<div class="clear"><input type="submit" value="Go" name="go" id="mc-embedded-subscribe" class="button"></div>
				    </div>
					</form>
				</div>
				<!-- Mailchimp Embed End -->
				<div id="notification_container"></div>
			</div>
	</div>
	<small>&copy; 2015 Nile all rights reserved</small>
</footer>


<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script type='text/javascript' src='//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js'></script>
<script src="js/script.min.js"></script>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-54514951-1', 'auto');
  ga('send', 'pageview');

</script>

</body>
</html>