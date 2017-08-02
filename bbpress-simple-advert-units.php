<?php
/*
Plugin Name: bbpress Simple Advert Units
Plugin URI: http://www.blogercise.com/bbpress-simple-advert-units/
Description: Insert ad units into your bbPress 2 forum
Author: jezza101
Version: 0.41
Author URI: http://www.blogercise.com
*/



/*
Date      Version     History
------    -----       --------
10/10/12  0.2         Added Unit Config
10/01/13  0.3         Exclude ads from RSS
13/04/13  0.31        Added upgrade logic
10/04/14  0.4
06/11/14  0.41
*/

//idea - create 'units' each unit has properties.  Who can see, contents, style, position, etc



//--- INITIALISE --------------------------------

if ( is_admin() ){ // admin actions
  add_action( 'admin_menu', 'bbp_simpleadvertunits_menu' );

  register_activation_hook(__FILE__,'bb_sau_init');
}


function bbp_simpleadvertunits_menu() {
 add_submenu_page('edit.php?post_type=forum', 'Advert Units', 'Ad Units', 'administrator', 'bbp_simpleadvertunits_menu', 'bbp_simpleadvertunits_page');
}


$bbp_sau_opt_vals = get_option('bbp_simpleadvertunits_options');

//print_r ($bbp_sau_opt_vals);

 IF (isset($_GET['sau_action'])){
    $sau_action      = sanitize_text_field($_GET['sau_action']);
    }

IF (isset($_GET['sau_id'])){
    $sau_id          = sanitize_text_field($_GET['sau_id']);
}


//---------------------------------------------------------------------------------
//--- CONFIG PAGE  --------------------------------


function bbp_simpleadvertunits_page(){
    global $bbp_sau_opt_vals,$sau_action,$sau_id;

    $hide       =   '';
    $show       =   '';
    $ad_code    =   '';
    //SHOULD BE LOGGED INTO ADMIN PANEL
    if (!current_user_can('manage_options')) {die("Security check FAIL");}

    // echo $sau_id.$sau_action;
  //---------------------------------------------------------------------------------
  //-- HANDLE EDIT AND NEW


    if( isset($_POST['submit_options']) && $_POST['submit_options'] == 'Links' ) {

       if ( !wp_verify_nonce( $_POST['bbpadunitsconfig_noncename'], plugin_basename(__FILE__) )) {die("Security check FAIL");}

       $bbp_sau_opt_vals['links']['login_link']['link']             = esc_url ($_POST['login_link']);
       $bbp_sau_opt_vals['links']['register_link']['link']          = esc_url ($_POST['register_link']);

       update_option('bbp_simpleadvertunits_options',$bbp_sau_opt_vals );
       echo '<div class="updated"><p><strong>Unit Saved</strong></p></div>';
    }

 if ($sau_action=='add' || $sau_action=='edit')

  {
    //HANDLE POSTED DATA

    if( isset($_POST['submit_options']) && $_POST['submit_options'] == 'Y' ) {

          //SECURITY
          if ( !wp_verify_nonce( $_POST['bbpadunitsconfig_noncename'], plugin_basename(__FILE__) )) {die("Security check FAIL");}

          if ($sau_id <> $_POST['location']    ){
            //IF LOCATION CHANGES WE NEED TO CHANGE TO NEW ONE AND CLEAR OUT OLD ONE

             //reset
             $bbp_sau_opt_vals['positions'][$sau_id]['hide_from_users'] =   TRUE;
             $bbp_sau_opt_vals['positions'][$sau_id]['ad_code']         =   '';

             //set to new location
             $sau_id = $_POST['location'];
          }

          //SET VALUES
          if($_POST['showhide']=='hide')
               {$bbp_sau_opt_vals['positions'][$sau_id]['hide_from_users']=TRUE;}
          else
               {$bbp_sau_opt_vals['positions'][$sau_id]['hide_from_users']=FALSE;}

          $bbp_sau_opt_vals['positions'][$sau_id]['ad_code']                = ($_POST['ad_code']);
          $ad_code                                                          = ($_POST['ad_code']);  //so it shows after entry
          //SAVE THE OPTIONS
          krsort($bbp_sau_opt_vals);

          update_option('bbp_simpleadvertunits_options',$bbp_sau_opt_vals );
          echo "<div class='updated'><p><strong>Options Saved</strong></p></div>
          <a href='".wp_nonce_url("edit.php?post_type=forum&page=bbp_simpleadvertunits_menu&sau_action=add", "addunits")."'>Add New Unit</a>
          ";
    }
    if (!empty($bbp_sau_opt_vals['positions'][$sau_id]['hide_from_users']) && $bbp_sau_opt_vals['positions'][$sau_id]['hide_from_users']==TRUE){$hide='checked';}else{$show='checked';}


 //--------------------


          if ($sau_action=='edit'){
           //GET VALUES
           //echo "$sau_id!".$bbp_sau_opt_vals['positions'][$sau_id]['ad_code'];
              $login_link      = $bbp_sau_opt_vals['positions'][$sau_id]['login_link'];
              $register_link   = $bbp_sau_opt_vals['positions'][$sau_id]['register_link'];
              $ad_code         = $bbp_sau_opt_vals['positions'][$sau_id]['ad_code'];
          }


  //BUILD FORM --------------


    $optionsform ="";
    $optionsform .="  <h2>Unit Config</h2>
                      <form name='form1' method='post' action='edit.php?post_type=forum&page=bbp_simpleadvertunits_menu&sau_action=$sau_action&sau_id=$sau_id'>
                      <input type='hidden' name='submit_options' value='Y'>";

    $optionsform .= '<input type="hidden" name="bbpadunitsconfig_noncename" id="bbpadunitsconfig_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

    $optionsform .= "<p>
                      Insert your Ad Unit code here (eg Amazon widget, Adsense unit, etc).
                     <p>";

    $optionsform .= "<textarea rows='10' cols='150' name='ad_code'>".stripslashes($ad_code)."</textarea>";

    $optionsform .= "<p><em>clear text above to delete the ad</em>
                     <p>
                     Unit Location:

                     <select name='location'>";

    //SET DEFAULT
    if (!empty($bbp_sau_opt_vals['positions'][$sau_id]['short_description']))
    {$default=$bbp_sau_opt_vals['positions'][$sau_id]['short_description'];}
      else{$default='';}

    $optionsform .= "<option value='$sau_id'>".$default."</option>";

    //BUILD DROP DOWN
    foreach ($bbp_sau_opt_vals['positions'] as $key => $value){
               if($value['ad_code']=='' && !empty($value['short_description'])){    //ONLY SHOW IF NOT ALREADY USED
                   $optionsform .= "<option value='$key'>".$value['short_description']."</option>";
               }
    }

    $optionsform .= "</select> <em>Only unused locations are shown</em>
                    ";

    $optionsform .= "<p>Hide from logged in forum users:
                     <input type='radio' name='showhide' value='hide' $hide>Hide
                     <input type='radio' name='showhide' value='show' $show>Show";

    $optionsform.="
                    <p class='submit'>
                    <input type='submit' name='Submit' value='Save!' />
                    </p>
                    </form>
                    </div>
                    <br>
                    </p>";

 }

 ELSE


{
    //OUTPUT PAGE
    echo '<div class="wrap">';
    echo "<h2>Simple Advert Units Configuration</h2>";
    echo "Your Units:";

  $optionsform ='';

  $optionsform .= "<p>
                   <table style='width:75%' class='widefat' align='center'>
                   <thead>
                    <tr>
                     <th class='row-title'>Unit</th>
                     <th class='row-title'>Hide From Users</th>
                     <th class='row-title'>Unit Location</th>
                     <th class='row-title'>Edit</th>
                   </tr>
                   </thead>
                   <tbody>
                    ";

    $class='';
    foreach ($bbp_sau_opt_vals['positions'] as $key => $value)

    {
    if ($value['ad_code']==''){continue;}
    if ($value['hide_from_users']==1){$hide = 'Yes';} else {$hide = 'No';}
    $editurl = wp_nonce_url('edit.php?post_type=forum&page=bbp_simpleadvertunits_menu&sau_action=edit&sau_id='.$key);
    $optionsform .= "<tr class='$class'>
                          <td>".$value['short_description']."</td>
                          <td>".$hide."</td>
                          <td>".$value['long_description']."</td>
                          <td><a href='$editurl'>edit</a></td>
                     </tr>";

    if($class==''){$class='alternate';}else {$class='';}
    }


   $optionsform .= "
                     </tbody>
                     </table>
                     <p>
                     Create a new advert unit: <a href='".wp_nonce_url("edit.php?post_type=forum&page=bbp_simpleadvertunits_menu&sau_action=add", "addunits")."'>Add New Unit</a>
                     <br>
                     <p>
                     Create new units, enter your advert code and select the location you would like the unit to appear in within your bbPress Forum
                     <p>
                    ";
 
//$optionsform.="<a href ='http://support.google.com/adsense/bin/answer.py?hl=en&answer=48182'>adsense rules</a>";

$optionsform.='<hr>
              <b>For more info</b>: <a href="http://www.blogercise.com/bbpress-simple-advert-units/">Homepage</a>,  follow our <a href="http://www.blogercise.com/feed/">RSS feed</a> or <a href="https://twitter.com/jezza101">Twitter</a> for updates. <a href="http://wordpress.org/support/plugin/bbpress-simple-advert-units">WP Forum</a>
              <h2>Support the Plugin</h2>
              <p>
              Have you found this plugin useful?
              <p>
              I hope so!  
              <p>
              If you continue to use it and it helps you to make a
              tonne of cash then why not say "thanks" by <strong>donating two pounds</strong> via PayPal:   <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
                                                                                    <input type="hidden" name="cmd" value="_s-xclick">
                                                                                    <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
                                                                                    ">
                                                                                    <input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal � The safer, easier way to pay online.">
                                                                                    <img alt="" border="0" src="https://www.paypalobjects.com/en_GB/i/scr/pixel.gif" width="1" height="1">
                                                                                    </form>


              <i>Donating through PayPal is fast and secure, your continued support keeps this plugin free for all!</i>
              <p> ';
              
 }



   echo $optionsform ;

} //end config page function



//--- INSERT ADVERT INTO TOPIC  --------------------------------

 
//  adunit_topic_betweenposts ---------------------------------------------


function bbp_sau_filter_betweenposts( $content, $reply_id ) {

        global $bbp_sau_opt_vals, $bbp_sau_runonce;
        $location ='adunit_topic_betweenposts';

        if($bbp_sau_runonce==1){return $content;}else{$bbp_sau_runonce=1;}  //IS THERE A BETTER WAY?
        if(sau_is_hidden($location)){return $content;}
 

  // EASIER JUST TO ADD THIS TO A UNIT IF NEEDED
  //      if($bbp_sau_opt_vals['positions'][$location]['hide_from_users']==TRUE)
  //      {
  //         $logininfo="Remove ads, join the forum today: <a href='".$bbp_sau_opt_vals['register_link']."'>Join Now</a> | <a href='".$bbp_sau_opt_vals['login_link']."'>Login</a>";
  //      }

        $ad = $bbp_sau_opt_vals['positions'][$location]['ad_code'];

        if (strlen($ad)>1)
        {
        //MANIPULATE POST OUTPUT
	$content=$content.'</div></div>  <!-- .bbp-reply -->
                            <div class="bbp-reply-header">
                             <div class="bbp-admin-links">

                       	     </div><!-- .bbp-meta -->
                            </div><!-- .bbp-reply-header -->
                            <div style="text-align:center;padding-top:20px">
                            <!-- .bbp-adunit -->'.stripslashes($ad).'<!-- .bbp-adunit-end -->
                            <p>
                         </div>
                        <div><div>';
        }

    $content = do_shortcode($content);

	return $content;
}
add_filter('bbp_get_reply_content', 'bbp_sau_filter_betweenposts', 100, 2);


// adunit_topic_header ---------------------------------------------


function bbp_sau_filter_topic_header() {

      $location ='adunit_topic_header';
      if(sau_is_hidden($location)){return;};
      echo sau_display_ad ($location);

}

add_action ('bbp_template_before_replies_loop', 'bbp_sau_filter_topic_header',50);


// adunit_topic_inpost ---------------------------------------------


function bbp_sau_filter_topic_inpost($content) {

      global $bbp_sau_opt_vals, $bbp_sau_run_inpost_once;
      $location ='adunit_topic_inpost';
      if($bbp_sau_run_inpost_once==1){return $content;}else{$bbp_sau_run_inpost_once=1;}
      if(sau_is_hidden($location)){return $content;};

      $ad = sau_display_ad ($location);

      $output   ='';      
      if (strlen($ad)>1){
          $output .=  '<div style="float:right;padding:10px;margin:0px 0px 5px 5px;border-style:solid;border-width:thin;">';
          $output .=  $ad ;
          $output .= "</div>";
      }

      return $output.$content;
}

add_filter('bbp_get_reply_content', 'bbp_sau_filter_topic_inpost', 101, 2);

// adunit_topic_footer ---------------------------------------------


function bbp_sau_filter_topic_footer() {
      $location ='adunit_topic_footer';
      if(sau_is_hidden($location)){return;};
      echo sau_display_ad ($location) ;

}

add_action ('bbp_template_after_replies_loop', 'bbp_sau_filter_topic_footer',60);

// adunit_forum_header ---------------------------------------------


function bbp_sau_filter_forum_header() {
      $location ='adunit_forum_header';
      if(sau_is_hidden($location)){return;};
      echo sau_display_ad ($location) ;

}

add_action ('bbp_template_before_single_forum', 'bbp_sau_filter_forum_header',60);


// adunit_forum_footer ---------------------------------------------



function bbp_sau_filter_forum_footer() {
      $location ='adunit_forum_footer';
      if(sau_is_hidden($location)){return;};
      echo sau_display_ad ($location) ;

}

add_action ('bbp_template_after_single_forum', 'bbp_sau_filter_forum_footer',60)   ;

// adunit_main_header ---------------------------------------------


function bbp_sau_filter_main_header() {
      $location ='adunit_main_header';
      if(sau_is_hidden($location)){return;};
      echo sau_display_ad ($location) ;

}

add_action ('bbp_template_before_forums_loop', 'bbp_sau_filter_main_header',60) ;



// adunit_main_footer ---------------------------------------------



 function bbp_sau_filter_main_footer() {
      $location ='adunit_main_footer';
      if(sau_is_hidden($location)){return;};
      echo sau_display_ad ($location) ;

}

add_action ('bbp_template_after_forums_loop', 'bbp_sau_filter_main_footer',60)   ;



add_filter('plugin_action_links', 'myplugin_plugin_action_links', 10, 2);



function myplugin_plugin_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        // The "page" query string value must be equal to the slug
        // of the Settings admin page we defined earlier, which in
        // this case equals "myplugin-settings".
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=bbp_simpleadvertunits_menu">Settings</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}






//--------------------------------------------------------------------
//  SHARED FUNCTIONS -------------------------------------
 
function sau_is_hidden ($location){
    global $bbp_sau_opt_vals;

    //user is logged in and it need's to be hidden
    if ( is_user_logged_in() && $bbp_sau_opt_vals['positions'][$location]['hide_from_users'] == TRUE){return true;}
    else {return false;}

}

function sau_display_ad ($location){
    global $bbp_sau_opt_vals;

    $output='';
    
      $ad = $bbp_sau_opt_vals['positions'][$location]['ad_code'];
      if (strlen($ad)>1){
        $output = "<!-- SAU_START_$location -->".stripslashes($ad)."<!-- SAU_END_$location -->";
      }

    $output = do_shortcode($output);
    return $output;   
}



//   RSS  -------------------------------------------------------------------------------------------------

function bbp_sau_exclude_RSS($query_vars){

     print_r($query_vars);exit;
   return $query_vars ;

}

//WHAT FILTER??
//add_filter( 'bbp_request', 'bbp_sau_exclude_RSS'  );

// ----------------------------------------------------------




//add_filter('bbp_template_after_replies_loop', 'pw_bbp_filter_replies3', 10, 2);



//--- INSTALL --------------------------------

function bb_sau_init() {


    //UPGRADE CODE

    
    $version = '0.4';
    if (get_option('bbp_simpleadvertunits_version',0) != $version) {
        // Execute your upgrade logic here

        //no upgrade code needed ATM

    }
    update_option('bbp_simpleadvertunits_version', $version);


    //have we already got ads?
    if(get_option('bbp_simpleadvertunits_options',0)){return;}
    
    
    //install for the first time... 

    //default login url
        $login = "/wp-login.php?action=register";

        $links         = array(
                               'message'                   => 'Sign in to remove ads: ',
                               'register_link'             => array(
                        		                              'text' => 'Register',
                        		                              'link' => $login),
                               'login_link'                => array(
                                                              'text' => 'Login',
                        		                              'link' => $login)
                                ) ;

 	$positions = array(
		'adunit_topic_header'       => array('short_description'  =>'Topic->Header',
                                                     'long_description'   =>'Before a topic starts',
                                                     'example'            =>'',
                                                     'show_login_message' =>TRUE,
                                                     'show_heading'       =>TRUE,
                                                     'ad_code'            =>'',
                                                     'hide_from_users'    =>TRUE
                                                     ),
		'adunit_topic_betweenposts' => array('short_description'  =>'Topic->Between Posts',
                                                     'long_description'   =>'This appears between first post and second post',
                                                     'example'            =>'',
                                                     'show_login_message' =>TRUE,
                                                     'show_heading'       =>TRUE,
                                                     'ad_code'            =>'',
                                                     'hide_from_users'    =>TRUE
                                                     ),
		'adunit_topic_inpost'       => array('short_description'  =>'Topic->In Original Post',
                                                     'long_description'   =>'Ad appears within body of first post',
                                                     'example'            =>'',
                                                     'show_login_message' =>TRUE,
                                                     'show_heading'       =>TRUE,
                                                     'ad_code'            =>'',
                                                     'hide_from_users'    =>TRUE
                                                     ),
		'adunit_topic_footer'       =>  array('short_description' =>'Topic->Footer',
                                                     'long_description'   =>'End of a topic',
                                                     'example'            =>'',
                                                     'show_login_message' =>TRUE,
                                                     'show_heading'       =>TRUE,
                                                     'ad_code'            =>'',
                                                     'hide_from_users'    =>TRUE
                                                     ),
		'adunit_forum_header'       =>  array('short_description' =>'Forum->Header',
                                                     'long_description'   =>'Before list of topics',
                                                     'example'            =>'',
                                                     'show_login_message' =>TRUE,
                                                     'show_heading'       =>TRUE,
                                                     'ad_code'            =>'',
                                                     'hide_from_users'    =>TRUE
                                                     ),
		'adunit_forum_footer'       =>  array('short_description' =>'Forum->Footer',
                                                     'long_description'   =>'End of list of topics',
                                                     'example'            =>'',
                                                     'show_login_message' =>TRUE,
                                                     'show_heading'       =>TRUE,
                                                     'ad_code'            =>'',
                                                     'hide_from_users'    =>TRUE
                                                     ),
		'adunit_main_header'        =>  array('short_description' =>'Front Page->Header',
                                                     'long_description'   =>'Before of list of forums',
                                                     'example'            =>'',
                                                     'show_login_message' =>TRUE,
                                                     'show_heading'       =>TRUE,
                                                     'ad_code'            =>'',
                                                     'hide_from_users'    =>TRUE
                                                     ),
		'adunit_main_footer'        =>  array('short_description' =>'Front Page->Footer',
                                                     'long_description'   =>'End of list of forums',
                                                     'example'            =>'',
                                                     'show_login_message' =>TRUE,
                                                     'show_heading'       =>TRUE,
                                                     'ad_code'            =>'',
                                                     'hide_from_users'    =>TRUE
                                                     )
	);

	$new_options = array(
		'links'                  => $links,
		'positions'              => $positions
	);

	update_option('bbp_simpleadvertunits_options', $new_options );

    

} //END INIT

 