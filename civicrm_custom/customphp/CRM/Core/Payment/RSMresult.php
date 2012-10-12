<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 2.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2009                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2009
 * $Id$
 *
 */

//echo "HelloExtern"; exit;
class CRM_Core_Payment_RSMresult{

 static function processRSM_postData($params) {
   
    $appealcode = explode('_', $params['appealcode']);  
   /* require_once 'CRM/Utils/Array.php';
    $cParams = array ( 
                     'version'                =>'3',
                     'sequential'             =>'1',
                     'id'                     =>$appealcode[0], 
                     'total_amount'           =>$params['amount'],
                     'contribution_status_id' =>'1');
    require_once 'api/api.php';	 
    $results = civicrm_api("Contribution","update",$cParams ); */
    
    $sql = "UPDATE `civicrm_contribution` SET `contribution_status_id`=1 WHERE `id`=$appealcode[0]"; 
    $dao = CRM_Core_DAO::executeQuery($sql); 
    
    $mParams = array ( 
                     'version'                =>'3',
                     'sequential'             =>'1',
                     'id'                     =>$appealcode[1],               
                     'status_id'              =>'1');
    $results1 = civicrm_api("Membership","update",$mParams );
    
    require_once 'CRM/Core/Session.php';
    $session = CRM_Core_Session::singleton();
    $qfKey = $session->get('qfKey1');    
    //print_r($session);exit;
    $config = CRM_Core_Config::singleton( );    
    $redirect_url =  "$config->userFrameworkBaseURL?page=CiviCRM&q=civicrm/contribute/transact&_qf_ThankYou_display=1&qfKey={$qfKey}";      
    $session->set('qfKey1' , ''); 
    header("Location:$redirect_url");
    exit(); 
 }
}
?>