<?php
/*
Plugin Name: show df -h
Plugin URI: http://robtranquillo.wordpress.org
Description: the plugin prints in adminmenu the current free disk-space on hdd
Version: 1.0
Author: Rob Tranquillo
Author URI: http://robtranquillo.wordpress.org
Update Server: *
Min WP Version: 3.4.2
Max WP Version: *

	Copyright 2012  Rob Tranquillo  (email: rob.tranquillo@gmail.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

 class show_dfh { 	
	public $adminToolBar;
	private $active_setting;
	private $cycle_setting;
	private $lastrun_setting;
	private $last_dfh;
	private $dfhOutputString;
	
	function __construct()
	{
		if( $_POST['df-h-activate-setting'] == 'sidebar' || $_POST['df-h-activate-setting'] == 'button' ) 
				update_option('df-h-activate-setting', $_POST['df-h-activate-setting'] );											
		$this->active_setting = get_option('df-h-activate-setting');

		if( $_POST['df-h-cycle-setting'] ) 
		{			
			if(	$_POST['df-h-cycle-setting'] > 1000 || $_POST['df-h-cycle-setting'] < 0)  //verify $post is a real number
				$_POST['df-h-cycle-setting'] = 1; 
			update_option('df-h-cycle-setting', $_POST['df-h-cycle-setting'] );				
		}
		$this->cycle_setting = get_option('df-h-cycle-setting');
		if( $this->cycle_setting == false ) $this->cycle_setting = 1;

		$this->lastrun_setting = get_option('df-h-lastrun-setting');		
		
		$this->adminToolBar = 'df-h';
		if( $this->active_setting == 'sidebar' ) {
			$this->get_dfh();
			update_option('dfh_buffer_toolbar',	 $this->adminToolBar );
			update_option('dfh_buffer_complete', $this->completeResult );
		}
		if( $_POST['get_df-h'] != '' ) 	{	$this->callTheServer();
											$this->dfhOutputString = '<h3>'.$this->adminToolBar.'</h3>'; 
		}
	}
	
	function get_dfh()
	{
		//if last run is long enough in the past
		if( $this->lastrun_setting + $this->cycle_setting*60 < time() )
		{
			$this->callTheServer();
			update_option( 'df-h-lastrun-setting', time() );
		}
		else
		{
			if( get_option('dfh_buffer_toolbar') ) $this->adminToolBar = get_option('dfh_buffer_toolbar');
			$this->completeResult = get_option('dfh_buffer_complete');
		}
	}
	
	function plugin_page()
	{
		if( $this->active_setting == 'sidebar' ) 
		{
			$checked['sidebar'] = ' checked ';	
			$cycle_input = "<li><input type=text value='$this->cycle_setting' maxlength=3 size=3 name='df-h-cycle-setting'> Minutes to update the measurement (0 = at every admin-pageload)</li>";
		}
		else 
		{
			$checked['button'] = ' checked ';
			$button = '<li><input type=submit name="get_df-h" value="Get df -h now!"> </li>';			
		}		
		
		echo 	'<br>
		<h2>Settings</h2>
		<br>
		<h3>ATTENTION!</h3>
		<br>Under circumstances it is possible that this df -h command on servers, 
		<br>these one with very large and distributed file systems, <b><i>need a lot of time</b></i>. 
		<br>Over a dozen minutes! So please take care of your maschine, first try that 
		<br>the plugin works very quick, use the button down there for it and after that 
		<br>activate the plugin for admin-tools-sidebar.
		'."
		<form method=post >
		<ul>
			<li><input type=radio name='df-h-activate-setting' value='sidebar' $checked[sidebar]> show df-h in admin-tools-sidebar										</li>
			$cycle_input
			<li><input type=radio name='df-h-activate-setting' value='button'  $checked[button]> only show df-h after defined request (you will get an button for it)	</li>			
			<li><input type=submit value='save setting'></li>
			$button
		</form>		
		<br><br>".		
		$this->completeResult;
		
		echo '<br><br> Last run on console: ' . date('d-m-Y h:i:s', $this->lastrun_setting);
	}

	function callTheServer()
	{
		$dfh = shell_exec('df -h');
		$dfh = substr( $dfh, strpos($dfh, '/'), -2 	);		//cut out "Filesystem Size Used Avail Use% Mounted on"
		$dfh = substr( $dfh, strpos($dfh, ' ')+1 	); 		//cut out the hdd-name
		$dfh = trim( str_replace('  ', ' ', $dfh)	);
		$this->adminToolBar = $dfh;
		$dfh = explode(' ',$dfh);
		$this->completeResult = "
		<h2>Output</h2> 
		<ul>				
			<li>	Complete Drive Space: $dfh[0]	</li>
			<li>	Used Drive Space: $dfh[1]		</li>
			<li>	Free Drive Space: $dfh[2]		</li>
			<li>	Free Drive Space: $dfh[3]		</li>
		</ul>";
	}
	
 }//class end


function show_dfh_admin_menu() {
    require_once ABSPATH . '/wp-admin/admin.php';
    $plugin = new show_dfh;
	add_management_page('edit.php', $plugin->adminToolBar , 9, __FILE__, array($plugin, 'plugin_page'));	
}

//Add an hook to AdminPage
add_action('admin_menu', 'show_dfh_admin_menu');  

?>