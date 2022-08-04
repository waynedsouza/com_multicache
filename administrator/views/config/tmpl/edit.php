<?php
/**
 *
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 */
// no direct access
defined('_JEXEC') or die();

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('bootstrap.framework');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.keepalive');

// Import CSS
$document = JFactory::getDocument();
$document->addStyleSheet(JUri::root(true) . '/media/com_multicache/assets/css/multicache.css');
?>
<script type="text/javascript">
    jmulticache = jQuery.noConflict();
    jmulticache(document).ready(function() {

    //jform_simulation_advanced selector
     jmulticache('#allow_simulation').click(function() {


 if(jmulticache('#simulation_advanced_control').is(':visible') && jmulticache('input#jform_simulation_advanced:checked').val() == 1 && jmulticache('#simulation_parse_control').is(':hidden') && jmulticache('#allow_simulation').find('label.active').text() == 'Yes'){

    jmulticache('#simulation_parse_control').show(1000);


        }
else if( jmulticache('#allow_simulation').find('label.active').text() == 'No'){
//a joomla transitions googly: the second visible gets the second transition(aka hidden) state wheras the first on get the pretransion state.

    jmulticache('#simulation_parse_control').hide(1000);


    }

        });
   //end

    var checkbox = jmulticache('input#jform_simulation_advanced:checked').val();
    if(checkbox != 1){

    jmulticache('#simulation_parse_control').hide();
    }
    jmulticache('input#jform_simulation_advanced').change(function() {

    if(jmulticache('input#jform_simulation_advanced:checked').val() == 1 && jmulticache('#allow_simulation').find('label.active').text() == 'Yes' ){
    jmulticache('#simulation_parse_control').show(1000);
    }
    else{
    jmulticache('#simulation_parse_control').hide(1000);
    }
    });

    //library selector
    jmulticache('.library_selector select').change(function(e , f) {
    var p =  jmulticache( this ).val();
    var q =  this.id;
    if(p == 1){
    jmulticache('.library_selector select').each(function(event  ) {
    if(this.id != q){
    jmulticache(this).parent().addClass('invisible');//fadeOut('slow');
    }
    });
    }
    if(p == 0){

   jmulticache('.library_selector').removeClass('invisible');//fadeIn('slow');
    }
    });
    //delay selector
    jmulticache('.delay_selector select').change(function(e , f) {
    var p =  jmulticache( this ).val();
    var q =  this.id;
    var typeofdelay = jmulticache(this).parent().siblings('.delaytype_selector').find('select');
    var promise_state = jmulticache(this).parent().siblings('.promises_selector').find('select');
        
    if(p == 1){
   var s =  jmulticache(this).parent().siblings('.delaytype_selector').fadeIn();//nextUntil('.delaytype_selector');
   if(typeofdelay.val() == 'onload')
	{
	jmulticache(this).parent().siblings('.ident_selector').fadeIn();
	//no promises for onload delays
	jmulticache(this).parent().siblings('.promises_selector').fadeOut();
	jmulticache(this).parent().siblings('.mau_selector').fadeOut();//nextUntil('.delaytype_selector');
   jmulticache(this).parent().siblings('.checktype_selector').fadeOut();
   jmulticache(this).parent().siblings('.thenBack_selector').fadeOut();
	}
else{
	jmulticache(this).parent().siblings('.ident_selector').fadeOut();
	//no promises for onload delays
	jmulticache(this).parent().siblings('.promises_selector').fadeIn();
	if(promise_state.val() == 1)
		{
		multicache_show_promisessub(this);
		}
	else{
		multicache_hide_promisessub(this);
	}
	
}

    }
    if(p == 0){

   jmulticache(this).parent().siblings('.delaytype_selector').fadeOut();
   jmulticache(this).parent().siblings('.ident_selector').fadeOut();
 //no promises for onload delays
 jmulticache(this).parent().siblings('.promises_selector').fadeIn();

 if(promise_state.val() == 1)
 {
 multicache_show_promisessub(this);
 }
 else{
 	multicache_hide_promisessub(this);
 }
    }
    });
  //delaytype selector
    jmulticache('.delaytype_selector select').change(function(e , f) {
    var p =  jmulticache( this ).val();
    console.log('value of p ' + p);
    var q =  this.id;
    var promise_state = jmulticache(this).parent().siblings('.promises_selector').find('select');

    if(p == 'onload'){
    var s =  jmulticache(this).parent().siblings('.ident_selector').fadeIn();//nextUntil('.delaytype_selector');

    //no promises for onload delays
    jmulticache(this).parent().siblings('.promises_selector').fadeOut();
    multicache_hide_promisessub(this);
    }
    if(p !== 'onload'){

    jmulticache(this).parent().siblings('.ident_selector').fadeOut();
    //no promises for onload delays

    jmulticache(this).parent().siblings('.promises_selector').fadeIn();


    if(promise_state.val() == 1)
    	{
    	multicache_show_promisessub(this);
    	}
    else
    	{
    	multicache_hide_promisessub(this);
    	}

    }
    });
  //wrap promise selector
    jmulticache('.promises_selector select').change(function(e , f) {
    	var p =  jmulticache( this ).val();
    	var q =  this.id;
    	//var typeofdelay = jmulticache(this).parent().siblings('.delaytype_selector').find('select');
    	//toggleAsyncUtility(this);
    	if(p == 1){
      
    		multicache_show_promisessub(this);
    	}
    	if(p == 0){
    		multicache_hide_promisessub(this);
    	
    	
    	}
    	});
    //mau selector
    jmulticache('.mau_selector select').change(function(e , f) {
    	//var p =  jmulticache( this ).val();
    	//var q =  this.id;
    	//var typeofdelay = jmulticache(this).parent().siblings('.delaytype_selector').find('select');
    	
    	toggleAsyncUtility(this , true);
    	
    	});
    //start delay slector css
     jmulticache('.delay_selector_css select').change(function(e , f) {
    var p =  jmulticache( this ).val();
    var q =  this.id;
    if(p == 1){
   var s =  jmulticache(this).parent().siblings('.delaytype_selector_css').fadeIn();//nextUntil('.delaytype_selector');


    }
    if(p == 0){

   jmulticache(this).parent().siblings('.delaytype_selector_css').fadeOut();
    }
    });
    //start grouping selector
      jmulticache('.grouping_selector select').change(function(e , f) {
    var p =  jmulticache( this ).val();
    var q =  this.id;
    if(p == 1){
   var s =  jmulticache(this).parent().siblings('.group_number_selector').fadeIn();//nextUntil('.delaytype_selector');
    }
    if(p == 0){

   jmulticache(this).parent().siblings('.group_number_selector').fadeOut();
    }
    });
    //end grouping selector
    //cdn selector
    jmulticache('.cdnalias_selector select').change(function(e , f) {
    var p =  jmulticache( this ).val();
    var q =  this.id;
    if(p == 1){
   jmulticache(this).parents('.content-fluid').find('.cdnurl_selector').removeClass('hidden').parent().removeClass('hidden');



    }
    if(p == 0){

   jmulticache(this).parents('.content-fluid').find('.cdnurl_selector').addClass('hidden').parent().addClass('hidden');
    }
    });
    //end
    jmulticache('.cdnalias_selector_css select').change(function(e , f) {
    var p =  jmulticache( this ).val();
    var q =  this.id;
    if(p == 1){
   jmulticache(this).parents('.content-fluid').find('.cdnurl_selector_css').removeClass('hidden').parent().removeClass('hidden');



    }
    if(p == 0){

   jmulticache(this).parents('.content-fluid').find('.cdnurl_selector_css').addClass('hidden').parent().addClass('hidden');
    }
    });

       var query = location.href.split('#');
       if(query[1] ){
       var fragment = '#' + query[1];
       removeActive();
       //var z = jmulticache('a[href="#page-js-tweaks"]').parent().addClass('active');
       var z = jmulticache('a[href="'+ fragment +'"]').parent().addClass('active');
              jmulticache("div"+ fragment).addClass('active')
       }

       //loadsection selector reset_loadsection
        jmulticache('.com_multicache_loadsection').change(function(e , f) {
        if(jmulticache('#reset_loadsection').hasClass('hidden')){
        jmulticache('#reset_loadsection').removeClass('hidden');
        }
       jmulticache('#reset_loadsection').hasClass('hidden').removeClass('hidden');
        });
        //start css loadsection reset
        jmulticache('.com_multicache_cssloadsection').change(function(e , f) {
        if(jmulticache('#reset_cssloadsection').hasClass('hidden')){
        jmulticache('#reset_cssloadsection').removeClass('hidden');
        }
       jmulticache('#reset_cssloadsection').hasClass('hidden').removeClass('hidden');
        });
        //end css loadsection reset

        //loadsection reset opertaion
        jmulticache('#reset_loadsection').on('click', function(event){
event.preventDefault();

       // jmulticache('.com_multicache_loadsection').chosen();
jmulticache('.com_multicache_loadsection').val("0").trigger("liszt:updated");//later versions use chosen:updated
       jmulticache('#reset_loadsection').addClass("hidden");
       jmulticache('#toolbar-cancel').fadeOut();//to ensure save before closing

        });


        //cssloadsection reset opertaion
        jmulticache('#reset_cssloadsection').on('click', function(event){
event.preventDefault();

       // jmulticache('.com_multicache_loadsection').chosen();
jmulticache('.com_multicache_cssloadsection').val("0").trigger("liszt:updated");//later versions use chosen:updated
       jmulticache('#reset_cssloadsection').addClass("hidden");
       jmulticache('#toolbar-cancel').fadeOut();//to ensure save before closing

        });



        //begin
 if( jmulticache('input#jform_simulation_advanced:checked').val() == 1 &&  jmulticache('#allow_simulation').find('label.active').text() == 'Yes'){

    jmulticache('#simulation_parse_control').show(1000);
}
//end
    });
    //start
    function toggleAsyncUtility(t , a )
 {
	 a = typeof a === 'undefined' ? false: a;
	 if(a)
		 {
		 var mau = jmulticache(t); 
		 }
	 else{
		 var mau = jmulticache(t).parent().siblings('.mau_selector').find('select');
	 }
	//since were using siblings we need to construct this separately when testing the mau_selector as its self not siblings
	 
	
	if(mau.val() == 1)
		{
		jmulticache(t).parent().siblings('.mautime_selector').fadeIn();
		}
	else{
		jmulticache(t).parent().siblings('.mautime_selector').fadeOut();
	}
 }
 function multicache_show_promisessub(t)
 {
	
	jmulticache(t).parent().siblings('.mau_selector').fadeIn();//nextUntil('.delaytype_selector');
	jmulticache(t).parent().siblings('.checktype_selector').fadeIn();
	jmulticache(t).parent().siblings('.thenBack_selector').fadeIn();
	toggleAsyncUtility(t);
 }
 function multicache_hide_promisessub(t)
 {
	 
	 jmulticache(t).parent().siblings('.mau_selector').fadeOut();
	 jmulticache(t).parent().siblings('.checktype_selector').fadeOut();
	 jmulticache(t).parent().siblings('.thenBack_selector').fadeOut(); 
	 jmulticache(t).parent().siblings('.mautime_selector').fadeOut();
 }
    //stop
    function removeActive(){

    jmulticache('li').removeClass('active');
    jmulticache('div').removeClass('active');


    }

    Joomla.submitbutton = function(task)
    {
        if (task == 'config.cancel') {
            Joomla.submitform(task, document.getElementById('config-form'));
        }
        else {

            if (task != 'config.cancel' && document.formvalidator.isValid(document.id('config-form'))) {

                Joomla.submitform(task, document.getElementById('config-form'));
            }
            else {
                alert('<?php
                echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));
                ?>');
            }
        }
    }
</script>

<form
	action="<?php
echo JRoute::_('index.php?option=com_multicache&view=config&layout=edit&id=' . (int) $this->item->id);
?>"
	method="post" enctype="multipart/form-data" name="adminForm"
	id="config-form" class="form-validate">



	<?php
echo JLayoutHelper::render('joomla.edit.title_alias', $this);
?>
    <div class="form-horizontal">
        <?php
        echo JHtml::_('bootstrap.startTabSet', 'myTab', array(
            'active' => 'page-settings'
        ));
        ?>

        <?php
        echo JHtml::_('bootstrap.addTab', 'myTab', 'page-settings', JText::_('COM_MULTICACHE_TITLE_CONFIG_SETTINGS', true));
        ?>
         <div class="row-fluid">
			<div class="span10 form-horizontal">
				<fieldset class="adminform">
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('caching');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('caching');
    ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('cache_handler');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('cache_handler');
    ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('cachetime');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('cachetime');
    ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('multicache_persist');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('multicache_persist');
    ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('multicache_compress');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('multicache_compress');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('multicache_server_host');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('multicache_server_host');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('multicache_server_port');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('multicache_server_port');
    ?></div>
					</div>

					<input type="hidden" name="jform[id]" id="jform_id" value="1">
				</fieldset>
			</div>
		</div>
        <?php
        echo JHtml::_('bootstrap.endTab');
        ?>
        <?php
        echo JHtml::_('bootstrap.addTab', 'myTab', 'page-optimisation', JText::_('COM_MULTICACHE_TITLE_CONFIG_OPTIMISATION', true));
        ?>
         <div class="row-fluid">
			<div class="span6">
				<fieldset class="adminform">

					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('gtmetrix_testing');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('gtmetrix_testing');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('gtmetrix_api_budget');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('gtmetrix_api_budget');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('gtmetrix_email');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('gtmetrix_email');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('gtmetrix_token');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('gtmetrix_token');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('gtmetrix_adblock');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('gtmetrix_adblock');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('gtmetrix_test_url');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('gtmetrix_test_url');
    ?></div>
					</div>
					<div id="allow_simulation" class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('gtmetrix_allow_simulation');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('gtmetrix_allow_simulation');
    ?></div>
					</div>

<?php
$class = '';
$rel = '';
if ($showon = $this->form->getField('simulation_advanced')->getAttribute('showon'))
{
    JHtml::_('jquery.framework');
    JHtml::_('script', 'jui/cms.js', false, true);
    $id = $this->form->getFormControl();
    $showon = explode(':', $showon, 2);
    $class = ' showon_' . implode(' showon_', explode(',', $showon[1]));
    $rel = ' rel="showon_' . $id . '[' . $showon[0] . ']"';
}
?>


<div id="simulation_advanced_control"
						class="control-group<?php
    echo $class;
    ?>"
						<?php
    echo $rel;
    ?>>
						<div class="control-label"><?php
    echo $this->form->getLabel('simulation_advanced');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('simulation_advanced');
    ?></div>
					</div>

					<div id="simulation_parse_control" class="control-group"
						style="display: none;">
						<div class="control-label"><?php
    echo $this->form->getLabel('jssimulation_parse');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('jssimulation_parse');
    ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('gtmetrix_cycles');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('gtmetrix_cycles');
    ?></div>
					</div>
				</fieldset>
			</div>
			<div class="span6 form-horizontal">
				<fieldset class="adminform">
					<legend><?php
    echo JText::_('COM_MULTICACHE_TESTING_CONDITIONS');
    ?></legend>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('precache_factor_min');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('precache_factor_min');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('precache_factor_max');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('precache_factor_max');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('precache_factor_default');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('precache_factor_default');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('gzip_factor_min');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('gzip_factor_min');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('gzip_factor_max');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('gzip_factor_max');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('gzip_factor_step');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('gzip_factor_step');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('gzip_factor_default');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('gzip_factor_default');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('cron_url');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('cron_url');
    ?></div>
					</div>


				</fieldset>
			</div>
		</div>
        <?php
        echo JHtml::_('bootstrap.endTab');
        ?>
        <?php
        echo JHtml::_('bootstrap.addTab', 'myTab', 'page-parta', JText::_('COM_MULTICACHE_TITLE_PAGE_DISTRIBUTION_LABEL', true));
        ?>
         <div class="row-fluid">
			<div class="span6 form-horizontal">
				<fieldset class="adminform">
					<legend><?php
    echo JText::_('COM_MULTICACHE_GOOGLE_CONSOLE_API');
    ?></legend>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('googleclientid');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('googleclientid');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('googleclientsecret');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('googleclientsecret');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('googleviewid');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('googleviewid');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('redirect_uri');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('redirect_uri');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('googlestartdate');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('googlestartdate');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('googleenddate');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('googleenddate');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('googlenumberurlscache');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('googlenumberurlscache');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('multicachedistribution');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('multicachedistribution');
    ?></div>
					</div>


				</fieldset>
			</div>
			<div class="span6 form-horizontal">
				<fieldset class="adminform">
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('urlfilters');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('urlfilters');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('frequency_distribution');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('frequency_distribution');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('natlogdist');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('natlogdist');
    ?></div>
					</div>
					<p class="small">please click on authenticate google after filling
						in the details to test and refresh the url page distribution.</p>
<?php
$class = '';
$rel = '';
if ($showon = $this->form->getField('cartmode')->getAttribute('showon'))
{
    JHtml::_('jquery.framework');
    JHtml::_('script', 'jui/cms.js', false, true);
    $id = $this->form->getFormControl();
    $showon = explode(':', $showon, 2);
    $class = ' showon_' . implode(' showon_', explode(',', $showon[1]));
    $rel = ' rel="showon_' . $id . '[' . $showon[0] . ']"';
}
?>
<H2 class="<?php
echo $class;
?>" <?php
echo $rel;
?>><?php
echo JText::_('COM_MULTICACHE_ADVANCED_CART_LEGEND');
?></H2>
					<div class="control-group<?php
    echo $class;
    ?>"
						<?php
    echo $rel;
    ?>>
						<div class="control-label"><?php
    echo $this->form->getLabel('cartmode');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('cartmode');
    ?></div>
					</div>
					<div class="<?php
    echo $class;
    ?>"
						<?php
    echo $rel;
    ?>>
<?php
$class = '';
$rel = '';
if ($showon = $this->form->getField('cartmodeurlinclude')->getAttribute('showon'))
{
    JHtml::_('jquery.framework');
    JHtml::_('script', 'jui/cms.js', false, true);
    $id = $this->form->getFormControl();
    $showon = explode(':', $showon, 2);
    $class = ' showon_' . implode(' showon_', explode(',', $showon[1]));
    $rel = ' rel="showon_' . $id . '[' . $showon[0] . ']"';
}
?>
<div class="control-group<?php
echo $class;
?>" <?php
echo $rel;
?>>
							<div class="control-label"><?php
    echo $this->form->getLabel('cartmodeurlinclude');
    ?></div>
							<div class="controls"><?php
    echo $this->form->getInput('cartmodeurlinclude');
    ?></div>
						</div>
					</div>
<?php
$class = '';
$rel = '';
if ($showon = $this->form->getField('cartsessionvariables')->getAttribute('showon'))
{
    JHtml::_('jquery.framework');
    JHtml::_('script', 'jui/cms.js', false, true);
    $id = $this->form->getFormControl();
    $showon = explode(':', $showon, 2);
    $class = ' showon_' . implode(' showon_', explode(',', $showon[1]));
    $rel = ' rel="showon_' . $id . '[' . $showon[0] . ']"';
}
?>

<div class="control-group<?php
echo $class;
?>" <?php
echo $rel;
?>>
						<div class="control-label"><?php
    echo $this->form->getLabel('cartsessionvariables');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('cartsessionvariables');
    ?></div>
					</div>

<?php
$class = '';
$rel = '';
if ($showon = $this->form->getField('cartdifferentiators')->getAttribute('showon'))
{
    JHtml::_('jquery.framework');
    JHtml::_('script', 'jui/cms.js', false, true);
    $id = $this->form->getFormControl();
    $showon = explode(':', $showon, 2);
    $class = ' showon_' . implode(' showon_', explode(',', $showon[1]));
    $rel = ' rel="showon_' . $id . '[' . $showon[0] . ']"';
}
?>


<div class="control-group <?php
echo $class;
?>" <?php
echo $rel;
?>>

						<div class="control-label"><?php
    echo $this->form->getLabel('cartdifferentiators');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('cartdifferentiators');
    ?></div>
					</div>
				</fieldset>
			</div>
		</div>
        <?php
        echo JHtml::_('bootstrap.endTab');
        ?>
        <?php
        echo JHtml::_('bootstrap.addTab', 'myTab', 'page-partb', JText::_('COM_MULTICACHE_TITLE_CONFIG_ADVANCED', true));
        ?>
         <div class="row-fluid">
			<div class="span6 form-horizontal">
				<fieldset class="adminform">



					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('indexhack');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('indexhack');
    ?></div>
					</div>
					<?php
    if (JDEBUG)
    :
        ?>
					<div class="control-group">
						<div class="control-label"><?php
        echo $this->form->getLabel('force_precache_off');
        ?></div>
						<div class="controls"><?php
        echo $this->form->getInput('force_precache_off');
        ?></div>
					</div>










    
    <?php
endif;
    ?>


<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('advanced_simulation_lock');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('advanced_simulation_lock');
    ?></div>
					</div>






					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('additionalpagecacheurls');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('additionalpagecacheurls');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('force_locking_off');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('force_locking_off');
    ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('positional_dontmovesrc');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('positional_dontmovesrc');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('allow_multiple_orphaned');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('allow_multiple_orphaned');
    ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('conduit_switch');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('conduit_switch');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('minify_html');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('minify_html');
    ?></div>
					</div>
				</fieldset>
			</div>
			<div class="span6 form-horizontal">
				<fieldset class="adminform">
					<legend><?php
    echo JText::_('COM_MULTICACHE_ALGORITHM_SETTINGS');
    ?></legend>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('targetpageloadtime');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('targetpageloadtime');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('algorithmavgloadtimeweight');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('algorithmavgloadtimeweight');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('algorithmmodemaxbelowtimeweight');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('algorithmmodemaxbelowtimeweight');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('algorithmvarianceweight');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('algorithmvarianceweight');
    ?></div>
					</div>
					<legend><?php
    echo JText::_('COM_MULTICACHE_DEPLOYMENT_POST_SIMULATION');
    ?></legend>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('deployment_method');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('deployment_method');
    ?></div>
					</div>
				</fieldset>
			</div>


		</div>
        <?php
        echo JHtml::_('bootstrap.endTab');
        ?>

      <?php
    echo JHtml::_('bootstrap.addTab', 'myTab', 'page-js-tweaks', JText::_('COM_MULTICACHE_TITLE_CONFIG_JAVASCRIPT_TWEAKS', true));
    ?>
         <div class="row-fluid">
			<div class="span6 form-horizontal">
				<fieldset class="adminform">

					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('js_switch');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('js_switch');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('default_scrape_url');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('default_scrape_url');
    ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('principle_jquery_scope');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('principle_jquery_scope');
    ?></div>
					</div>

<?php
$class = '';
$rel = '';
if ($showon = $this->form->getField('principle_jquery_scope_other')->getAttribute('showon'))
{
    JHtml::_('jquery.framework');
    JHtml::_('script', 'jui/cms.js', false, true);
    $id = $this->form->getFormControl();
    $showon = explode(':', $showon, 2);
    $class = ' showon_' . implode(' showon_', explode(',', $showon[1]));
    $rel = ' rel="showon_' . $id . '[' . $showon[0] . ']"';
}
?>
<div class="control-group<?php
echo $class;
?>"<?php
echo $rel;
?>">
						<div class="control-label"><?php
    echo $this->form->getLabel('principle_jquery_scope_other');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('principle_jquery_scope_other');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('dedupe_scripts');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('dedupe_scripts');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('defer_social');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('defer_social');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('defer_advertisement');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('defer_advertisement');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('defer_async');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('defer_async');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('maintain_preceedence');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('maintain_preceedence');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('minimize_roundtrips');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('minimize_roundtrips');
    ?></div>
					</div>

				</fieldset>
			</div>
			<div class="span6 form-horizontal">
				<fieldset class="adminform">
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('social_script_identifiers');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('social_script_identifiers');
    ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('advertisement_script_identifiers');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('advertisement_script_identifiers');
    ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('pre_head_stub_identifiers');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('pre_head_stub_identifiers');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('head_stub_identifiers');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('head_stub_identifiers');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('body_stub_identifiers');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('body_stub_identifiers');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('footer_stub_identifiers');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('footer_stub_identifiers');
    ?></div>
					</div>
					<!-- comments -->
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('js_comments');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('js_comments');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('compress_js');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('compress_js');
    ?></div>
					</div>

					<!-- end comments -->
					<!-- start -->

					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('debug_mode');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('debug_mode');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('orphaned_scripts');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('orphaned_scripts');
    ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('resultant_async');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('resultant_async');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('resultant_defer');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('resultant_defer');
    ?></div>
					</div>
					<!-- end -->

				</fieldset>
			</div>
<?php
if (! empty($this->script_render))
:
    ?>
<div class="span12 form-horizontal">
				<fieldset class="adminform">
					<legend><?php
    echo JText::_('COM_MULTICACHE_JSTWEAKS_INDIVIDUAL_SCRIPT_SETTINGS');
    ?></legend>
					<h6>
						<ul class="list-inline list-unstyled" style="list-style: none;">
							<li class="list-unstyled"><?php
    echo JText::_('COM_MULTICACHE_JSTWEAKS_STAT_TOTAL_SCRIPTS_LABEL') . ' - ' . $this->stats->total_scripts;
    ?></li>
							<li class="list-unstyled"><?php
    echo JText::_('COM_MULTICACHE_JSTWEAKS_STAT_UNIQUE_SCRIPTS_LABEL') . ' - ' . $this->stats->unique_scripts;
    ?></li>
						</ul>
					</h6>

<?php
    echo $this->script_render;
    ?>

            </fieldset>
			</div>























<?php
endif;
?>
        </div>

<?php
echo JHtml::_('bootstrap.endTab');
?>

      <?php
    echo JHtml::_('bootstrap.addTab', 'myTab', 'page-js-inclusion', JText::_('COM_MULTICACHE_TITLE_CONFIG_JAVASCRIPT_INCLUSIONS', true));
    ?>
    <div class="row-fluid">

			<div class="span6 form-horizontal">
				<fieldset class="adminform">

					<!-- jstweaker -->

					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('js_tweaker_url_include_exclude');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('js_tweaker_url_include_exclude');
    ?></div>
					</div>
	<?php
$class = '';
$rel = '';
if ($showon = $this->form->getField('jst_urlinclude')->getAttribute('showon'))
{
    JHtml::_('jquery.framework');
    JHtml::_('script', 'jui/cms.js', false, true);
    $id = $this->form->getFormControl();
    $showon = explode(':', $showon, 2);
    $class = ' showon_' . implode(' showon_', explode(',', $showon[1]));
    $rel = ' rel="showon_' . $id . '[' . $showon[0] . ']"';
}
?>
<div class="control-group<?php
echo $class;
?>" <?php
echo $rel;
?>>
						<div class="control-label"><?php
    echo $this->form->getLabel('jst_urlinclude');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('jst_urlinclude');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('jst_query_include_exclude');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('jst_query_include_exclude');
    ?></div>
					</div>
<?php
$class = '';
$rel = '';
if ($showon = $this->form->getField('jst_query_param')->getAttribute('showon'))
{
    JHtml::_('jquery.framework');
    JHtml::_('script', 'jui/cms.js', false, true);
    $id = $this->form->getFormControl();
    $showon = explode(':', $showon, 2);
    $class = ' showon_' . implode(' showon_', explode(',', $showon[1]));
    $rel = ' rel="showon_' . $id . '[' . $showon[0] . ']"';
}
?>
<div class="control-group<?php
echo $class;
?>" <?php
echo $rel;
?>>
						<div class="control-label"><?php
    echo $this->form->getLabel('jst_query_param');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('jst_query_param');
    ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('jst_url_string');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('jst_url_string');
    ?></div>
					</div>

				</fieldset>
			</div>
			<div class="span6 form-horizontal">
				<fieldset class="adminform">
					<legend><?php
    echo JText::_('COM_MULTICACHE_JAVASCRIPT_COMPONENTS_EXCLUSIONS_LEGEND');
    ?></legend>
    <?php
    if (! empty($this->componentexclusions))
    :
        echo $this->componentexclusions;
    





    endif;
    ?>

				</fieldset>
			</div>


		</div>

		    <?php
    echo JHtml::_('bootstrap.endTab');
    ?>

      <?php
    echo JHtml::_('bootstrap.addTab', 'myTab', 'page-css-tweaks', JText::_('COM_MULTICACHE_TITLE_CONFIG_CSS_TWEAKS', true));
    ?>
         <div class="row-fluid">
			<div class="span6 form-horizontal">
				<fieldset class="adminform">
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('css_switch');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('css_switch');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('css_scrape_url');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('css_scrape_url');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('dedupe_css_styles');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('dedupe_css_styles');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('css_maintain_preceedence');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('css_maintain_preceedence');
    ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php
    echo $this->form->getLabel('group_css_styles');
    ?></div>
						<div class="controls"><?php
    echo $this->form->getInput('group_css_styles');
    ?></div>
						<div class="control-group">
							<div class="control-label"><?php
    echo $this->form->getLabel('compress_css');
    ?></div>
							<div class="controls"><?php
    echo $this->form->getInput('compress_css');
    ?></div>
						</div>
						<div class="control-group">
							<div class="control-label"><?php
    echo $this->form->getLabel('css_special_identifiers');
    ?></div>
							<div class="controls"><?php
    echo $this->form->getInput('css_special_identifiers');
    ?></div>
						</div>
						<div class="control-group">
							<div class="control-label"><?php
    echo $this->form->getLabel('css_comments');
    ?></div>
							<div class="controls"><?php
    echo $this->form->getInput('css_comments');
    ?></div>
						</div>
						<div class="control-group">
							<div class="control-label"><?php
    echo $this->form->getLabel('css_groupsasync');
    ?></div>
							<div class="controls"><?php
    echo $this->form->getInput('css_groupsasync');
    ?></div>
						</div>
						<div class="control-group">
							<div class="control-label"><?php
    echo $this->form->getLabel('groups_async_exclude');
    ?></div>
							<div class="controls"><?php
    echo $this->form->getInput('groups_async_exclude');
    ?></div>
						</div>
						<div class="control-group">
							<div class="control-label"><?php
    echo $this->form->getLabel('groups_async_delay');
    ?></div>
							<div class="controls"><?php
    echo $this->form->getInput('groups_async_delay');
    ?></div>
						</div>
						<div class="control-group">
							<div class="control-label"><?php
    echo $this->form->getLabel('orphaned_styles_loading');
    ?></div>
							<div class="controls"><?php
    echo $this->form->getInput('orphaned_styles_loading');
    ?></div>
						</div>
				
				</fieldset>
			</div>


<?php
if (! empty($this->css_render))
:
    ?>
<div class="span12 form-horizontal">
				<fieldset class="adminform">
					<legend><?php
    echo JText::_('COM_MULTICACHE_CSSTWEAKS_INDIVIDUAL_SCRIPT_SETTINGS');
    ?></legend>
					<h6>
						<ul class="list-inline list-unstyled" style="list-style: none;">
							<li class="list-unstyled"><?php
    echo JText::_('COM_MULTICACHE_CSSTWEAKS_STAT_TOTAL_SCRIPTS_LABEL') . ' - ' . $this->css_stats->total_scripts;
    ?></li>
							<li class="list-unstyled"><?php
    echo JText::_('COM_MULTICACHE_CSSTWEAKS_STAT_UNIQUE_SCRIPTS_LABEL') . ' - ' . $this->css_stats->unique_scripts;
    ?></li>
						</ul>
					</h6>

<?php
    echo $this->css_render;
    ?>

            </fieldset>
			</div>
			<!-- closes span12 -->























<?php
endif;
?>
        </div>
	</div>
	<!-- start -->
    <?php
    echo JHtml::_('bootstrap.addTab', 'myTab', 'page-css-inclusion', JText::_('COM_MULTICACHE_TITLE_CONFIG_CSS_INCLUSIONS', true));
    ?>
<div class="row-fluid">
		<div class="span6 form-horizontal">
			<fieldset class="adminform">
				<!-- csstweaker -->

				<div class="control-group">
					<div class="control-label"><?php
    echo $this->form->getLabel('css_tweaker_url_include_exclude');
    ?></div>
					<div class="controls"><?php
    echo $this->form->getInput('css_tweaker_url_include_exclude');
    ?></div>
				</div>
	<?php
$class = '';
$rel = '';
if ($showon = $this->form->getField('css_urlinclude')->getAttribute('showon'))
{
    JHtml::_('jquery.framework');
    JHtml::_('script', 'jui/cms.js', false, true);
    $id = $this->form->getFormControl();
    $showon = explode(':', $showon, 2);
    $class = ' showon_' . implode(' showon_', explode(',', $showon[1]));
    $rel = ' rel="showon_' . $id . '[' . $showon[0] . ']"';
}
?>
<div class="control-group<?php
echo $class;
?>" <?php
echo $rel;
?>>
					<div class="control-label"><?php
    echo $this->form->getLabel('css_urlinclude');
    ?></div>
					<div class="controls"><?php
    echo $this->form->getInput('css_urlinclude');
    ?></div>
				</div>
				<div class="control-group">
					<div class="control-label"><?php
    echo $this->form->getLabel('css_query_include_exclude');
    ?></div>
					<div class="controls"><?php
    echo $this->form->getInput('css_query_include_exclude');
    ?></div>
				</div>
<?php
$class = '';
$rel = '';
if ($showon = $this->form->getField('css_query_param')->getAttribute('showon'))
{
    JHtml::_('jquery.framework');
    JHtml::_('script', 'jui/cms.js', false, true);
    $id = $this->form->getFormControl();
    $showon = explode(':', $showon, 2);
    $class = ' showon_' . implode(' showon_', explode(',', $showon[1]));
    $rel = ' rel="showon_' . $id . '[' . $showon[0] . ']"';
}
?>
<div class="control-group<?php
echo $class;
?>" <?php
echo $rel;
?>>
					<div class="control-label"><?php
    echo $this->form->getLabel('css_query_param');
    ?></div>
					<div class="controls"><?php
    echo $this->form->getInput('css_query_param');
    ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php
    echo $this->form->getLabel('css_url_string');
    ?></div>
					<div class="controls"><?php
    echo $this->form->getInput('css_url_string');
    ?></div>
				</div>


			</fieldset>
		</div>
		<div class="span6 form-horizontal">
			<fieldset class="adminform">
				<legend><?php
    echo JText::_('COM_MULTICACHE_CSS_COMPONENTS_EXCLUSIONS_LEGEND');
    ?></legend>
    <?php
    if (! empty($this->csscomponentexclusions))
    :
        echo $this->csscomponentexclusions;
    














    endif;
    ?>
			</fieldset>
		</div>


	</div>


<?php
echo JHtml::_('bootstrap.endTab');
?>
   <!--end -->
	<!-- start lazy-->
<?php
echo JHtml::_('bootstrap.addTab', 'myTab', 'page-image-tweaks', JText::_('COM_MULTICACHE_TITLE_CONFIG_IMAGE_TWEAKS', true));
?>
<div class="row-fluid">
		<div class="span6 form-horizontal">
			<legend><?php
echo JText::_('COM_MULTICACHE_IMAGETWEAKS_LAZY_LOAD_SETTINGS');
?></legend>
			<fieldset class="adminform">
				<div class="control-group">
					<div class="control-label"><?php
    echo $this->form->getLabel('image_lazy_switch');
    ?></div>
					<div class="controls"><?php
    echo $this->form->getInput('image_lazy_switch');
    ?></div>
				</div>
				<!-- removed container image_lazy_container_switch ~issues with pq() -->
				<!-- removed image_lazy_container_strings -->



				<div class="control-group">
					<div class="control-label"><?php
    echo $this->form->getLabel('image_lazy_image_selector_include_switch');
    ?></div>
					<div class="controls"><?php
    echo $this->form->getInput('image_lazy_image_selector_include_switch');
    ?></div>
				</div>

							<?php
    $class = '';
    $rel = '';
    if ($showon = $this->form->getField('image_lazy_image_selector_include_strings')->getAttribute('showon'))
    {
        JHtml::_('jquery.framework');
        JHtml::_('script', 'jui/cms.js', false, true);
        $id = $this->form->getFormControl();
        $showon = explode(':', $showon, 2);
        $class = ' showon_' . implode(' showon_', explode(',', $showon[1]));
        $rel = ' rel="showon_' . $id . '[' . $showon[0] . ']"';
    }
    ?>
<div class="control-group<?php
echo $class;
?>" <?php
echo $rel;
?>>
					<div class="control-label"><?php
    echo $this->form->getLabel('image_lazy_image_selector_include_strings');
    ?></div>
					<div class="controls"><?php
    echo $this->form->getInput('image_lazy_image_selector_include_strings');
    ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php
    echo $this->form->getLabel('image_lazy_image_selector_exclude_switch');
    ?></div>
					<div class="controls"><?php
    echo $this->form->getInput('image_lazy_image_selector_exclude_switch');
    ?></div>
				</div>

							<?php
    $class = '';
    $rel = '';
    if ($showon = $this->form->getField('image_lazy_image_selector_exclude_strings')->getAttribute('showon'))
    {
        JHtml::_('jquery.framework');
        JHtml::_('script', 'jui/cms.js', false, true);
        $id = $this->form->getFormControl();
        $showon = explode(':', $showon, 2);
        $class = ' showon_' . implode(' showon_', explode(',', $showon[1]));
        $rel = ' rel="showon_' . $id . '[' . $showon[0] . ']"';
    }
    ?>
<div class="control-group<?php
echo $class;
?>" <?php
echo $rel;
?>>
					<div class="control-label"><?php
    echo $this->form->getLabel('image_lazy_image_selector_exclude_strings');
    ?></div>
					<div class="controls"><?php
    echo $this->form->getInput('image_lazy_image_selector_exclude_strings');
    ?></div>
				</div>

			</fieldset>
		</div>
		<div class="span6 form-horizontal">
			<legend><?php
echo JText::_('COM_MULTICACHE_IMAGETWEAKS_LAZY_LOAD_EXCLUSIONS');
?></legend>
			<fieldset class="adminform">
				<!-- start -->

				<div class="control-group">
					<div class="control-label"><?php
    echo $this->form->getLabel('imagestweaker_url_include_exclude');
    ?></div>
					<div class="controls"><?php
    echo $this->form->getInput('imagestweaker_url_include_exclude');
    ?></div>
				</div>
	<?php
$class = '';
$rel = '';
if ($showon = $this->form->getField('images_urlinclude')->getAttribute('showon'))
{
    JHtml::_('jquery.framework');
    JHtml::_('script', 'jui/cms.js', false, true);
    $id = $this->form->getFormControl();
    $showon = explode(':', $showon, 2);
    $class = ' showon_' . implode(' showon_', explode(',', $showon[1]));
    $rel = ' rel="showon_' . $id . '[' . $showon[0] . ']"';
}
?>
<div class="control-group<?php
echo $class;
?>" <?php
echo $rel;
?>>
					<div class="control-label"><?php
    echo $this->form->getLabel('images_urlinclude');
    ?></div>
					<div class="controls"><?php
    echo $this->form->getInput('images_urlinclude');
    ?></div>
				</div>
				<div class="control-group">
					<div class="control-label"><?php
    echo $this->form->getLabel('images_query_include_exclude');
    ?></div>
					<div class="controls"><?php
    echo $this->form->getInput('images_query_include_exclude');
    ?></div>
				</div>
<?php
$class = '';
$rel = '';
if ($showon = $this->form->getField('images_query_param')->getAttribute('showon'))
{
    JHtml::_('jquery.framework');
    JHtml::_('script', 'jui/cms.js', false, true);
    $id = $this->form->getFormControl();
    $showon = explode(':', $showon, 2);
    $class = ' showon_' . implode(' showon_', explode(',', $showon[1]));
    $rel = ' rel="showon_' . $id . '[' . $showon[0] . ']"';
}
?>
<div class="control-group<?php
echo $class;
?>" <?php
echo $rel;
?>>
					<div class="control-label"><?php
    echo $this->form->getLabel('images_query_param');
    ?></div>
					<div class="controls"><?php
    echo $this->form->getInput('images_query_param');
    ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php
    echo $this->form->getLabel('images_url_string');
    ?></div>
					<div class="controls"><?php
    echo $this->form->getInput('images_url_string');
    ?></div>
				</div>

    <?php
    if (! empty($this->imagescomponentexclusions))
    :
        ?>
    <div class="span6">
					<legend><?php
        echo JText::_('COM_MULTICACHE_CSS_COMPONENTS_EXCLUSIONS_LEGEND');
        ?></legend>
    <?php
        echo $this->imagescomponentexclusions;
        ?>
         </div>













    
    <?php

    endif;
    ?>

				<!-- end -->
			</fieldset>
		</div>

	</div>
	<!-- end lazy -->

        <?php
        echo JHtml::_('bootstrap.endTabSet');
        ?>

        <input type="hidden" name="task" value="" />
        <?php
        echo JHtml::_('form.token');
        ?>

    </div>


</form>