<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<div id="consent-screen-cover" style="background-image: url(<?php echo G\starts_with('http', CHV\getSetting('consent_screen_cover_image')) ? CHV\getSetting('consent_screen_cover_image') : CHV\get_system_image_url(CHV\getSetting('consent_screen_cover_image')); ?>);">
	<div id="consent-screen-cover-inner">
		<div id="consent-screen-content" class="c16 center-box">
			<h1 class="margin-bottom-20"><img class="logo" src="<?php echo CHV\get_system_image_url(CHV\getSetting('favicon_image')); ?>"><?php echo CHV\getSetting('website_name'); ?></h1>
			<p><?php _se('Please read and comply with the following conditions before you continue:'); ?></p>
			<div class="input-label">
			<textarea class="r4 resize-none" readonly><?php _se("This website contains information, links and images of sexually explicit material. If you are under the age of %s, if such material offends you or if it's illegal to view such material in your community please do not continue.\n\nI am at least %s years of age and I believe that as an adult it is my inalienable right to receive/view sexually explicit material. I desire to receive/view sexually explicit material. \n\nI believe that sexual acts between consenting adults are neither offensive nor obscene. The viewing, reading and downloading of sexually explicit materials does not violate the standards of my community, town, city, state or country.\n\nThe sexually explicit material I am viewing is for my own personal use and I will not expose minors to the material.\n\nI am solely responsible for any false disclosures or legal ramifications of viewing, reading or downloading any material in this site. Furthermore this website nor its affiliates will be held responsible for any legal ramifications arising from fraudulent entry into or use of this website.\n\nThis consent screen constitutes a legal agreement between this website and you and/or any business in which you have any legal or equitable interest. If any portion of this agreement is deemed unenforceable by a court of competent jurisdiction it shall not affect the enforceability of the other portions of the agreement.", ['%s' => CHV\getSetting('user_minimum_age') ?: 21]); ?></textarea>
			</div>
			<p class="font-size-small"><?php _se('By clicking in "I Agree" you declare that you have read and understood all the conditions mentioned above.'); ?></p>
			<div class="btn-container margin-bottom-0"><a class="btn btn-input red" href="<?php echo get_consent_accept_url(); ?>"><?php _se('I Agree'); ?></a> <span class="btn-alt">or<a class="cancel" href="about:blank">cancel</a></span></div>
		</div>
	</div>
</div>

<?php G\Render\include_theme_footer(); ?>