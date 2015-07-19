<?php
/*
Plugin Name: Affiliates Manager Simple Membership Integration
Plugin URI: https://wpaffiliatemanager.com
Description: Process an affiliate commission via Affiliates Manager after a Simple Membership payment.
Version: 1.0.1
Author: wp.insider, affmngr
Author URI: https://wpaffiliatemanager.com
*/

function wpam_simple_membership_add_custom_parameters($custom_field_value)
{
    if(isset($_COOKIE['wpam_id']))
    {
        $name = 'wpam_tracking';
        $value = $_COOKIE['wpam_id'];
        $new_val = $name.'='.$value;
        $current_val = $custom_field_value;
        if(empty($current_val)){
            $custom_field_value = $new_val;
        }
        else{
            $custom_field_value = $current_val.'&'.$new_val;
        }
        WPAM_Logger::log_debug('Simple Membership Integration - Adding custom field value. New value: '.$custom_field_value);
    }
    else if(isset($_COOKIE[WPAM_PluginConfig::$RefKey]))
    {
        $name = 'wpam_tracking';
        $value = $_COOKIE[WPAM_PluginConfig::$RefKey];
        $new_val = $name.'='.$value;
        $current_val = $custom_field_value;
        if(empty($current_val)){
            $custom_field_value = $new_val;
        }
        else{
            $custom_field_value = $current_val.'&'.$new_val;
        }
        WPAM_Logger::log_debug('Simple Membership Integration - Adding custom field value. New value: '.$custom_field_value);
    }
    return $custom_field_value;
}

add_filter("swpm_custom_field_value_filter", "wpam_simple_membership_add_custom_parameters");

function wpam_simple_membership_payment_completed($ipn_data)
{
    $custom_data = $ipn_data['custom'];
    WPAM_Logger::log_debug('Simple Membership Integration - Payment completed hook fired. Custom field value: '.$custom_data);
    $custom_values = array();
    parse_str($custom_data, $custom_values);
    if(isset($custom_values['wpam_tracking']) && !empty($custom_values['wpam_tracking']))
    {
        $tracking_value = $custom_values['wpam_tracking'];
        WPAM_Logger::log_debug('Simple Membership Integration - Tracking data present. Need to track affiliate commission. Tracking value: '.$tracking_value);
        $purchaseLogId = $ipn_data['txn_id'];
        $purchaseAmount = $ipn_data['mc_gross']; //TODO - later calculate sub-total only
        $strRefKey = $tracking_value;
        $requestTracker = new WPAM_Tracking_RequestTracker();
        $requestTracker->handleCheckoutWithRefKey( $purchaseLogId, $purchaseAmount, $strRefKey);
        WPAM_Logger::log_debug('Simple Membership Integration - Commission tracked for transaction ID: '.$purchaseLogId.'. Purchase amt: '.$purchaseAmount);
    }
    else{
        WPAM_Logger::log_debug('Simple Membership Integration - No Tracking data present. No Need to track affiliate commission.');
    }
}

add_action("swpm_paypal_ipn_processed", "wpam_simple_membership_payment_completed");
