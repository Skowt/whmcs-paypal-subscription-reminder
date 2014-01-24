<?php
/**
 *  Paypal Subscription Reminder Addon
 *
 * This module creates a ticket from one of the staff members on WHMCS, with a reminder
 * For clients who have just submitted a cancellation, to cancel their Paypal subscriptions
 * To prevent being automatically billing again.
 *
 * @package    Paypal Subscription Reminder
 * @author     Daniel Pietersen <daniel.pietersen.pe@gmail.com>
 * @copyright  Copyright (c) Daniel Pietersen 2005-2014
 * @license    http://www.whmcs.com/license/ WHMCS Eula
 * @version    v1.00
 */

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

## Debug Settings
$debug = mysql_fetch_array( select_query('tbladdonmodules','value', "module='paypalsub_reminder' AND setting='debug'"));
$debug = $debug['value'];
	
#Adds new entries to activity log.
function logitHook($message) {

		$command = "logactivity";
		
		$staffmember = mysql_fetch_array( select_query('tbladdonmodules','value', "module='paypalsub_reminder' AND setting='staffusername'"));
		$staffmember = $staffmember['value'];
		
		if (isset($staffmember)) {
			$values["description"] = $message;
			$results = localAPI($command,$values,$staffmember);
		}
		
}		
	
#Create the ticket for customers
function paypalsub_reminder_Create_ticket($vars) {

			global $debug;

			# Don't run if not enabled.
			if ($debug == 'on') {
				logitHook("Paypal Subscription Reminder | Create Ticket Function Started.");
			}
			
			$enabled = mysql_fetch_array( select_query('tbladdonmodules', 'value', "module='paypalsub_reminder' AND setting='enabled'"));
			$enabled = $enabled['value'];

			# Don't run if not enabled.
			if ($enabled != 'on') {
			
				# Display debug info if enabled.
				if ($debug == 'on') {
					logitHook("Paypal Subscription Reminder | Not enabled. Not sending ticket.");
				}
				return;
				
			}

			# Get settings
			$staffmemberticket = mysql_fetch_array( select_query('tbladdonmodules', 'value', "module='paypalsub_reminder' AND setting='staffusername'"));
			$staffmemberticket = $staffmemberticket['value'];
			
			$departmentname = mysql_fetch_array( select_query('tbladdonmodules', 'value', "module='paypalsub_reminder' AND setting='departmentid'"));
			$departmentname = $departmentname['value'];
			
			$departmentid = mysql_fetch_array( select_query('tblticketdepartments','id', "name='" . $departmentname . "'"));
			$departmentid = $departmentid['id'];

			$ticketsubject = mysql_fetch_array( select_query('tbladdonmodules', 'value', "module='paypalsub_reminder' AND setting='ticketsubject'"));
			$ticketsubject = $ticketsubject['value'];
			
			$ticketmessage = mysql_fetch_array( select_query('tbladdonmodules', 'value', "module='paypalsub_reminder' AND setting='ticketmessage'"));
			$ticketmessage = $ticketmessage['value'];
			
			$ticketpriority = mysql_fetch_array( select_query('tbladdonmodules', 'value', "module='paypalsub_reminder' AND setting='ticketpriority'"));
			$ticketpriority = $ticketpriority['value']; 
			
			
			# Display debug info if enabled.
			if ($debug == 'on') {
				logitHook("Paypal Subscription Reminder | Staff Member Ticket: " . $staffmemberticket);
				logitHook("Paypal Subscription Reminder | Department Name: " . $departmentname);
				logitHook("Paypal Subscription Reminder | Department ID: " . $departmentid);
				logitHook("Paypal Subscription Reminder | Ticket Subject: " . $ticketsubject);
				logitHook("Paypal Subscription Reminder | Ticket Priority: " . $ticketpriority);
			}			

			$command = "openticket";
			$adminuser = $staffmemberticket;
			$values["clientid"] = $vars['userid'];
			$values["deptid"] = $departmentid;
			$values["subject"] = $ticketsubject;
			$values["message"] = $ticketmessage;
			$values["priority"] = $ticketpriority;
			 
			$results = localAPI($command,$values,$adminuser);
    
            if ($results['result']!="success") {
                logitHook("Paypal Subscription Reminder | Error: " . $results['result']);
			}
            else {
                logitHook("Paypal Subscription Reminder | Ticket Created for Cancellation.");
                SetStatus($results['id']);
			}

}

#Set Status of ticket to Answered
function SetStatus($ticketid) {

		global $debug;

		# Debug info.
		if ($debug == 'on') {
			logitHook("Paypal Subscription Reminder | SetStatus Function Started.");
		}
    
		$staffmemberticket = mysql_fetch_array( select_query('tbladdonmodules', 'value', array('module' => 'paypalsub_reminder', 'setting' => 'staffusername') ), MYSQL_ASSOC );
		$staffmemberticket = $staffmemberticket['value'];
		
		$ticketstatus = mysql_fetch_array( select_query('tbladdonmodules', 'value', array('module' => 'paypalsub_reminder', 'setting' => 'ticketstatus') ), MYSQL_ASSOC );
		$ticketstatus = $ticketstatus['value'];
		
		# Debug info.
		if ($debug == 'on') {
			logitHook("Paypal Subscription Reminder | Staff Member Username: " . $staffmemberticket);
			logitHook("Paypal Subscription Reminder | Ticket Status: " . $ticketstatus);
		}		
	
        $command = "updateticket";
        $adminuser = $staffmemberticket;
        $values["ticketid"] = $ticketid;
        $values["status"] = $ticketstatus;
     
        $results = localAPI($command,$values,$adminuser);
    
        if ($results['result']!="success")
            logitHook("Paypal Subscription Reminder | Error: " . $results['result']);
        else
            logitHook("Paypal Subscription Reminder | Ticket Set to '" . $ticketstatus . "'.");
    
} 

# Debug info.
if ($debug == 'on') {
	add_hook("TicketOpenAdmin", 1, "paypalsub_reminder_Create_ticket");
}
else {
	add_hook("CancellationRequest", 1, "paypalsub_reminder_Create_ticket");
}