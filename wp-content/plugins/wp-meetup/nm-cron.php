<?php

class NM_Cron {

	/*This class creates a database that contains ID, LAST_DATE, and RUN variables. ID remains 1 always and forever. LAST_DATE is the last time that nm_crom produced a true statement. RUN is the TRUE/FALSE statement produced and are represented by 1's and 0's. A 1 is equilvalent to TRUE and 0 is equivalent to FALSE. 

	FUNCTIONALITY: This takes the current date and compares it to the LAST_DATE. If these date are the same then there is no change and RUN remains false. However, if these dates are differnet then this class will update the database setting the current time as the LAST_TIME and replacing RUN with a 1. RUN will them be replaced again by a 0 when the class is next ran. 

	USE: inorder to use this class in a program, your program must contain the following code:

		include 'nmcron.php'

	Also,

		global $wpdb, $nmcron;
		$variable_name = $wpdb->get_var( "SELECT `run` FROM $nmcron->sqltable_cron WHERE `id`=1" );

	In this case, $variable_name will be either a '0' or a '1'.

	TIME INTERVAL: Current times are stored in (Y-m-d H:i:s) format. This allows for an update every secoond, every minute, every hour, and so on. However, this is only activated when the program is ran. For example, if you want to to update every hour, the dates need to be set to (Y-m-d H:00:00). However this only updates when the page is viewed, therefore it is possible for the page to update at 8:59 and then again at 9:00. Once updated at 9:00, it won't be able to be ran again until 10:00. It is also possible to not be updated at all if the class is never ran.  */

	
	var $sqltable_cron = 'nm_cron';

	function __construct() {
		// adds the init function as a wordpress action.
		global $wpdb;
		$this->sqltable_cron = $wpdb->prefix . $this->sqltable_cron;
		add_action('init', array(&$this, 'init'));
	}

	function init() {
		/*  calls fp_update_database if the database doesnt already exist and add_db_row  */
		global $wpdb;
		$tableSearch = $wpdb->get_var("SHOW TABLES LIKE '$this->sqltable_cron'");
		if ($tableSearch != $this->sqltable_cron) {
			$this->fp_update_database();
			$this->add_db_row();
		}
		$this->date_compare();
	}


	function fp_update_database() {
		/*  Build the nmcron database if and only if it does not already exist */
		global $wpdb;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$sql = "CREATE TABLE $this->sqltable_cron (
				 id int(11) NOT NULL AUTO_INCREMENT,
				 last_date datetime NOT NULL,
				 run BOOLEAN NOT NULL,
				PRIMARY KEY (id)
				)
				CHARACTER SET utf8
				COLLATE utf8_general_ci;";
		dbDelta($sql);
	}


	function date_compare() {
		/* compares the time at moment ran with the last time saved in database. If the two are equal them the update cron will not change the date saved and save false to the database via the update_cron function, inversely if the two are not equal, then it will update db to current date and store true, also via the update_cron function. */
		global $wpdb;
		//$today = date("Y-m-d H:i:s"); // Use if you need finer control over time intervals
		$today = date("Y-m-d 00:00:00");
		$last_date = $wpdb->get_var("SELECT last_date FROM $this->sqltable_cron WHERE `id`=1");
		$changedate = 0;
		if ($today != $last_date) {
			$changedate = 1;
			$this->update_cron($changedate);
		}
		else {
			$this->update_cron($changedate);
		}
	}

	function add_db_row() {
		/*  This function adds the initial date and run to the database when created.  */
		global $wpdb;	
		$newdata = array(
			//'last_date' 	=> date('Y-m-d H:i:s'),
			'last_date'     => date("Y-m-d 00:00:00"),
			'run'			=> '1', 
		);
		$wpdb->insert($this->sqltable_cron, $newdata);
	}

	function update_cron($changedate) {
		/*this function takes two parameters, $run which is a string containing true or false, and $changedate which is a boolean. $changedate will contain true is the date is to updated, eitherway, $run will update in database. */
		global $wpdb;
		if ($changedate == 1) {
			$data = array(
				//'last_date' => date('Y-m-d H:i:s'), // use if we need more fine grained control
				'last_date' => date('Y-m-d 00:00:00'),
				'run'		=> $changedate,
			);
			$where = array(
				'id' => 1
			);
			$wpdb->update($this->sqltable_cron, $data, $where);
		}
		else {
			$data = array(
				'run' => $changedate,
			);
			$where = array(
				'id' => 1
			);
			$wpdb->update($this->sqltable_cron, $data, $where);
		}
	}
}