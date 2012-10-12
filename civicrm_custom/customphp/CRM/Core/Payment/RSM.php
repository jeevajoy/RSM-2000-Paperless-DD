<?php 

/*
 * Copyright (C) 2008
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * Modified and contributed by NFP Systems, http://www.nfpservices.co.uk
 *
 */

/**
 * @package CRM
 * @author Irfan Ahmed <irfan.ahmed@v-empower.com>
**/

require_once 'CRM/Core/Payment.php';

class CRM_Core_Payment_RSM extends CRM_Core_Payment {
    const
        CHARSET  = 'iso-8859-1';
    
    protected $_mode = null;

    /**
     * We only need one instance of this object. So we use the singleton
     * pattern and cache the instance in this variable
     *
     * @var object
     * @static
     */
    static private $_singleton = null;
    
    /** 
     * Constructor 
     * 
     * @param string $mode the mode of operation: live or test
     *
     * @return void 
     */ 
    function __construct( $mode, &$paymentProcessor ) {
        $this->_mode             = $mode;
        $this->_paymentProcessor = $paymentProcessor;
        $this->_processorName    = ts('RSM2000 Direct Debit');

        if ( $this->_paymentProcessor['payment_processor_type'] == 'RSM' ) {
            return;
        }

        //if ( ! $this->_paymentProcessor['user_name'] ) {
        //    CRM_Core_Error::fatal( ts( 'Could not find User ID for payment processor' ) );
        //}
    }

    /** 
     * singleton function used to manage this object 
     * 
     * @param string $mode the mode of operation: live or test
     *
     * @return object 
     * @static 
     * 
     */ 
    static function &singleton( $mode, &$paymentProcessor ) {
        $processorName = $paymentProcessor['name'];
        if (self::$_singleton[$processorName] === null ) {
            self::$_singleton[$processorName] = new CRM_Core_Payment_RSM( $mode, $paymentProcessor );
        }
        return self::$_singleton[$processorName];
    }
    
    /**
     * This function collects all the information from a web/api form and invokes
     * the relevant payment processor specific functions to perform the transaction
     *
     * @param  array $params assoc array of input parameters for this transaction
     *
     * @return array the result in an nice formatted array (or an error object)
     * @public
     */
    function doDirectPayment( &$params ) {
        $args = array( );
        print_r("asdS");exit;
        $this->initialize( $args, 'DoDirectPayment' );

        //$args['paymentAction']  = $params['payment_action']; // What is this?
		//$args['Freq']			   = $params['frequency']; // **** Confirm if we want to pass frequency
        //$args['Amount']            = $params['amount'];
        //$args['currencyCode']   = $params['currencyID'];
        //$args['invnum']         = $params['invoiceID'];
        //$args['ipaddress']      = $params['ip_address'];
        //$args['creditCardType'] = $params['credit_card_type'];
        //$args['acct']           = $params['credit_card_number'];
        //$args['expDate']        = sprintf( '%02d', $params['month'] ) . $params['year'];
        //$args['cvv2']           = $params['cvv2'];
		$args['title']		   	= $params['title']; //**** Confirm how field populated
        $args['firstname']      = $params['first_name'];
        $args['surname']        = $params['last_name'];
        $args['email']          = $params['email'];
		$args['accountholder']	= $params['title'].' '.$params['first_name'].' '.$params['last_name'];
        $args['address1']       = $params['street_address'];
        $args['town']           = $params['city'];
        $args['county']         = $params['state_province'];
        $args['country']        = $params['country'];
        $args['postcode']       = $params['postal_code'];

        // Allow further manipulation of the arguments via custom hooks ..
        CRM_Utils_Hook::alterPaymentProcessorParams( $this, $params, $args );

        $result = $this->invokeAPI( $args );

        if ( is_a( $result, 'CRM_Core_Error' ) ) {  
            return $result;  
        }

        /* Success */
		/* AG - No Result returned from Rapidata therefore assume amount entered is transaction amt */
		/***** How do we pass back frequency *****/
        $params['trxn_id']        = $params['contactID']; 	//$result['transactionid'];
        $params['gross_amount']   = $params['amount']; 		//$result['amt'];
        return $params;
    }

    /** 
     * This function checks to see if we have the right config values 
     * 
     * @return string the error message if any 
     * @public 
     */ 
    function checkConfig( ) {
        $error = array( );
        //if ( $this->_paymentProcessor['payment_processor_type'] == 'Rapidata') {
        //    if ( empty( $this->_paymentProcessor['user_name'] ) ) {
        //        $error[] = ts( 'User ID is not set in the Administer CiviCRM &raquo; Payment Processor.' );
        //    }
        //}
    
        if ( ! empty( $error ) ) {
            return implode( '<p>', $error );
        } else {
            return null;
        }
    }

    function doTransferCheckout( &$params, $component = 'contribute' ) {
        $config = CRM_Core_Config::singleton( );         
        if ( $component != 'contribute' ) {
            CRM_Core_Error::fatal( ts( 'Component is invalid' ) );
        }
        
        $notifyURL = 
            $config->userFrameworkResourceURL . 
            "extern/ipn.php?reset=1&contactID={$params['contactID']}" .
            "&contributionID={$params['contributionID']}" .
            "&module={$component}";

		$membershipID = CRM_Utils_Array::value( 'membershipID', $params );
		if ( $membershipID ) {
			$notifyURL .= "&membershipID=$membershipID";
		}
		$relatedContactID = CRM_Utils_Array::value( 'related_contact', $params );
		if ( $relatedContactID ) {
			$notifyURL .= "&relatedContactID=$relatedContactID";

			$onBehalfDupeAlert = CRM_Utils_Array::value( 'onbehalf_dupe_alert', $params );
			if ( $onBehalfDupeAlert ) {
				$notifyURL .= "&onBehalfDupeAlert=$onBehalfDupeAlert";
			}
		}

        $url    = 'civicrm/contribute/transact';
        $cancel = '_qf_Main_display';
        $returnURL = CRM_Utils_System::url( $url,
                                            "_qf_ThankYou_display=1&qfKey={$params['qfKey']}",
                                            true, null, false );
        $cancelURL = CRM_Utils_System::url( $url,
                                            "$cancel=1&cancel=1&qfKey={$params['qfKey']}",
                                            true, null, false );

        // ensure that the returnURL is absolute.
        if ( substr( $returnURL, 0, 4 ) != 'http' ) {
            require_once 'CRM/Utils/System.php';
            $fixUrl = CRM_Utils_System::url("civicrm/admin/setting/url", '&reset=1');
            CRM_Core_Error::fatal( ts( 'Sending a relative URL to RSM is erroneous. Please make your resource URL (in <a href="%1">Administer CiviCRM &raquo; Global Settings &raquo; Resource URLs</a> ) complete.', array( 1 => $fixUrl ) ) );
        }
        
   
        
        
        require_once 'api/api.php';
        $contactDetails = civicrm_api("Contact","get", array ('version' => '3','sequential' =>'1', 'contact_id' =>$params['contactID']));
        $contact = $contactDetails['values'][0];       
        $RSM_campaignID = "243.151"; 
        $individual_prefix = substr($contact['prefix'],0,-1);           
		$RSMParams = array(      'campaign'      => $RSM_campaignID,							   
								 'title'   		 => $individual_prefix,
								 'firstname'   	 => $contact['first_name'],
								 'surname'  	 => $contact['last_name'],
								 'email'   		 => $params['email-5'],
								 'accountholder' => $individual_prefix.' '.$contact['first_name'].' '.$contact['last_name'],
								 'address1'  	 => $contact['street_address'],
								 'address2'    	 => $contact['supplemental_address_1'],
								 'address3'	     => $contact['supplemental_address_2'],
								 'town'			 => $contact['city'],
								 'county'		 => $contact['state_province'], 								 
								 'postcode'		 => $contact['postal_code'],  		
                                 'purchasefix'   => $params['amount'],
                                 'purchasedesc'  => $params['description'],
                                 'repeat'        => '1',
                                 'donationfix'   => '50',
                                 'donorref'      => $params['contactID'],
                                 'appealcode'    => $params['contributionID']."_".$params['membershipID'],
								);
                                
        require_once 'CRM/Core/Session.php';
        $session = CRM_Core_Session::singleton();
        $session->set('qfKey1' , $params['qfKey']);                        
                                
                            
                                
                                
        
		// Fetch County Name from Database
		// Check if the user has selected the state/province, as it is not a mandatory field in the form
	/*	if (!empty($params['state_province-Primary'])) {	
    		$query =   "SELECT name
    					FROM civicrm_state_province
    					WHERE id = ".$params['state_province-Primary'];
    		# run the query
    		$dao = CRM_Core_DAO::executeQuery( $query );
            
    		while ( $dao->fetch() ) {
    			$RSMParams['county'] = $dao->name;
    		}
		}
		
		// Fetch Title Name from Database
		$query =   "SELECT name FROM civicrm_option_value
					WHERE value = ".$params['individual_prefix']."
					AND option_group_id = 6";
		# run the query
		$dao = CRM_Core_DAO::executeQuery( $query );
		while ( $dao->fetch() ) {
			$RSMParams['title'] = str_replace(".","",$dao->name);
			$RSMParams['accountholder'] = str_replace(".","",$dao->name).' '.$params['first_name'].' '.$params['last_name'];
		}  */
		
        // add name and address if available, CRM-3130
        /*$otherVars = array( 'first_name'     => 'first_name',
                            'last_name'      => 'last_name',
                            'street_address' => 'address1',
                            'city'           => 'city',
                            'state_province' => 'state',
                            'postal_code'    => 'zip',
                            'email'          => 'email' 
                            );

        foreach ( array_keys( $params ) as $p ) {
            // get the base name without the location type suffixed to it
            $parts = explode( '-', $p );
            $name  = count( $parts ) > 1 ? $parts[0] : $p;
            if ( isset( $otherVars[$name] ) ) {
                $value = $params[$p];
                if ( $name == 'state_province' ) {
                    $stateName = CRM_Core_PseudoConstant::stateProvinceAbbreviation( $value );
                    $value     = $stateName;
                }
                if ( $value ) {
                    $RapidataParams[$otherVars[$name]] = $value;
                }
            }
        }*/

        // if recurring donations, add a few more items
/*        if ( ! empty( $params['is_recur'] ) ) {
            if ( $params['contributionRecurID'] ) {
                $notifyURL .= "&contributionRecurID={$params['contributionRecurID']}&contributionPageID={$params['contributionPageID']}";
                $RapidataParams['notify_url'] = $notifyURL;
            } else {
                CRM_Core_Error::fatal( ts( 'Recurring contribution, but no database id' ) );
            }
            
            $RapidataParams = array( 'WID' => $this->_paymentProcessor['user_name'],
                                           'R'   => $returnURL,
                                           'B'   => $this->_paymentProcessor['signature'],
                                           'T'   => $params['amount'],
                                           'RD'	 => '1',
                                           'C'   => '1',
                                           'I'	 => $params['invoiceID']
                                           );
        } else {
            $RapidataParams += array( 'cmd'    => '_xclick',
                                            'amount' => $params['amount'],
                                            );
        }*/

        // Allow further manipulation of the arguments via custom hooks ..
        CRM_Utils_Hook::alterPaymentProcessorParams( $this, $params, $RSMParams );          
        $uri = '';
        foreach ( $RSMParams as $key => $value ) {
            if ( $value === null ) {
                continue;
            }

            $value = urlencode( $value );
            if ( $key == 'return' ||
                 $key == 'cancel_return' ||
                 $key == 'notify_url' ) {
                $value = str_replace( '%2F', '/', $value );
            }
            $uri .= "&{$key}={$value}";
        }

        $uri = substr( $uri, 1 );
        $url = $this->_paymentProcessor['url_site'];
        $sub = empty( $params['is_recur'] ) ? 'xclick' : 'subscriptions';
         $RSMURL = "{$url}?$uri";
/*		 
$fileRes = fopen("d:\\debug.log","a");
fwrite($fileRes, "Rapidata URL=".$RapidataURL."\r\n"); 
foreach ($params as $key => $value) {
	fwrite($fileRes,"Param $key value $value\r\n");
}
fclose($fileRes);   
*/   


?>
      <html>
      <body>
      <form method="post" name="rsm" action="https://rsm3.rsmsecure.com/demo/directdebit/dd.php">            
      <?php
          foreach($RSMParams as $key => $val){
            echo '<input  name="'.$key.'" type="hidden" value="'.$val.'" />';
          }        
      ?>       
     </form>
    
    <script type="text/javascript">
    //alert(document.elements);
    document.forms['rsm'].submit();
    </script>
    <?php
    
        echo "Redirecting... please wait";
        require_once 'CRM/Core/Session.php';
        CRM_Core_Session::storeSessionObjects( );
        exit;
 }
}