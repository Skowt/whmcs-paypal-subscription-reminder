<?php
/**
 *  PayPal Subscription Reminder Addon
 *
 * This module creates a ticket from one of the staff members on WHMCS, with a reminder
 * For clients who have just submitted a cancellation, to cancel their PayPal subscriptions
 * To prevent being automatically billing again.
 *
 * @package    PayPal Subscription Reminder
 * @author     Daniel Pietersen <daniel.pietersen.pe@gmail.com>
 * @copyright  Copyright (c) Daniel Pietersen 2005-2014
 * @license    http://www.whmcs.com/license/ WHMCS Eula
 * @version    v1.00
 */
 
 ## Global Variables
 $departmentstring = ''; # Get ready for use.
 $adminstring = ''; # Get ready for use.
 $ticketstatusstring = ''; # Get ready for use.

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");
	
#Adds new entries to activity log.
function logit($message) {

		$command = "logactivity";
		
		$staffmember = mysql_fetch_array( select_query('tbladdonmodules','value', "module='paypalsub_reminder' AND setting='staffusername'"));
		$staffmember = $staffmember['value'];
		
		if (isset($staffmember)) {
			$values["description"] = $message;
			$results = localAPI($command,$values,$staffmember);
		}
		
}		


# Get Info for config from MySQL DB.
function GetInfo() {

	# Acknowledge Global Variable Use
	 global $departmentstring, $adminstring, $ticketstatusstring;

########## Get Departments ##########

	$table = "tblticketdepartments";
	$fields = "id,name";
	$where = "id LIKE '%'";
	$sort = "name";
	$sortorder = "ASC";
	$result = select_query($table,$fields,$where,$sort,$sortorder);
	
	while ($data = mysql_fetch_array($result)) {
	
		$id = $data['id'];
		$name = $data['name'];
		
		if ($departmentstring == '') {
			$departmentstring = $data['name'];
		}
		else {
			$departmentstring = $departmentstring . ',' .  $data['name'];
		}
		
	}
	
############ Get Admins ############

	$table = "tbladmins";
	$fields = "id,username";
	$where = "id LIKE '%'";
	$sort = "username";
	$sortorder = "ASC";
	$result = select_query($table,$fields,$where,$sort,$sortorder);
	
	while ($data = mysql_fetch_array($result)) {
	
		$id = $data['id'];
		$name = $data['username'];
		
		if ($adminstring == '') {
			$adminstring = $data['username'];
		}
		else {
			$adminstring = $adminstring . ',' .  $data['username'];
		}
		
	}
	
	
############ Get Ticket Statuses ############	

	$table = "tblticketstatuses";
	$fields = "id,title,sortorder";
	$sort = "sortorder";
	$sortorder = "ASC";
	$result = select_query($table,$fields,'',$sort,$sortorder);
	
	while ($data = mysql_fetch_array($result)) {
	
		$id = $data['id'];
		$name = $data['title'];
		
		if ($ticketstatusstring == '') {
			$ticketstatusstring = $data['title'];
		}
		else {
			$ticketstatusstring = $ticketstatusstring . ',' .  $data['title'];
		}
		
	}


}

	
function paypalsub_reminder_config() {

	GetInfo();

	# Acknowledge Global Variable Use
	 global $departmentstring, $adminstring, $ticketstatusstring;

    $configarray = array(
    "name" => "PayPal Subscription Reminder",
    "description" => "Automatically remind clients when cancelling about any active PayPal subscriptions that they might have.",
    "version" => "1.2",
    "author" => "Daniel Pietersen",
    "language" => "english",
    "fields" => array(
        "enabled" => array ("FriendlyName" => "Enabled", "Type" => "yesno","Description" => "Enable to start sending PayPal subscription reminders.", ),
        "departmentid" => array ("FriendlyName" => "Department", "Type" => "dropdown", "Options" => $departmentstring ,"Description" => "Select which Department the ticket must be created in.", ),
        "staffusername" => array ("FriendlyName" => "Staff Member Username", "Type" => "dropdown", "Options" => $adminstring ,"Description" => "Select which admin the ticket will be created from.", ),
        "ticketsubject" => array ("FriendlyName" => "Ticket Subject", "Type" => "text", "Size" => "40", "Description" => "The subject to be used for the new ticket.", "Default" => "PayPal Subscription Reminder", ),
		"ticketstatus" => array ("FriendlyName" => "Ticket Status", "Type" => "dropdown", "Options" => $ticketstatusstring ,"Description" => "Select what status you want the ticket to have.", ),
		"ticketpriority" => array ("FriendlyName" => "Ticket Priority", "Type" => "dropdown", "Options" => "High,Medium,Low" ,"Description" => "Select what priority you want the ticket to have.", "Default" => "Medium", ),
        "ticketmessage" => array ("FriendlyName" => "Ticket Message", "Type" => "textarea", "Rows" => "5", "Cols" => "50", "Description" => "The subscription reminder sent when customers cancel their accounts.", 
		"Default" => "Important. Please Read:

Please note that once your services have been cancelled on our side, you still have to cancel any active subscriptions on your PayPal account. You can do so by following the steps below:

Log in to your PayPal account.
Click Profile near the top of the page.
Select My money.
In the 'My pre-approved payments' section, click Update.
Select the merchant whose agreement you want to cancel and click Cancel.
Click Cancel Profile to confirm your request.

You must cancel our subscription or you will be billed again.

As in our TOS, We do not accept responsibility for users who have canceled their services and not their PayPal subscriptions. The client will take full responsibility for negligence to the subscription and all payments for a canceled service will not be refunded.

Kind Regards,

Your Name
Your Position
Website Name
http://URL.com", ),
		"debug" => array ("FriendlyName" => "Debug", "Type" => "yesno","Description" => "Developer use only. This will send reminders for new admin tickets (for testing) instead of on cancellation.",),),);
		
    return $configarray;

	
}

function  paypalsub_reminder_activate() {

	logit("PayPal Subscription Reminder | Module was activated.");
 
    # Return Result
    return array('status'=>'success','description'=>'PayPal Subscription Reminder has been enabled.');
    return array('status'=>'error','description'=>'PayPal Subscription Reminder has been disabled.');
 
}
 
function  paypalsub_reminder_deactivate() {
 
 	logit("PayPal Subscription Reminder | Module was deactivated.");

    # Return Result
    return array('status'=>'success','description'=>'PayPal Subscription Reminder disabled successfully.');
    return array('status'=>'error','description'=>'Paybal Subscription Reminder had an issue while disabling. Contact developer.');
	
 
}

function paypalsub_reminder_output($vars) {

    $modulelink = $vars['modulelink'];
    $version = $vars['version'];
    $LANG = $vars['_lang'];

    echo '<p>'.$LANG['moduledropdown'].'</p>';

}
