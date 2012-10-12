RSM 2000 Ltd Paperless Direct Debit:
--------------------------------------

Requirements
--------------
This module requires CiviCRM4.2.x or greater and Drupal 7.x. or Wordpress 3.4.2
Installation Instructions
To install the RSM 2000 Paperless Direct Debit move the files under
'civicrm_custom/custom_php/CRM/Core/Payment' to your sites `[Custom PHP
directory]/CRM/Core/Payment` directory.
If the Custom PHP directory is not set, please set it in Administer > Configure > Global Settings >
Directories
For details of how to set custom directories see here:
http://wiki.civicrm.org/confluence/display/CRMDOC33/Directories
Import the Payment Processor Type
To import the Payment Processor Type, please import the file 'script.sql' to your civicrm database.
Add the payment Processor
To add the payment Processor, navigate to Administer > Configure > Global Settings > Payment
Processors.
Now click 'Add Payment Processor' button.
Select Payment Processor Type : 'RSM2000' and please give the name as ' RSM2000 DD'.
And set your credentials in the Username , Password, Singature, URL etc .
Now, you can create contribution or event pages with RSM2000 Direct Debit for online
payment.
Contact Information
All feedback and comments of a technical nature (including support questions)
and for all other comments you can reach me at the following e-mail address. Please
include "CiviCRM RSM2000 Paperless Direct Debit" somewhere in the subject.
rajesh AT millertech.co.uk
License Information
Copyright (C) Miller Technology 2010