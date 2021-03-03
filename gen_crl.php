<?php
/* Generate CRLs from cron
 * Add a link to your cron to automagically update the CRL
 */
 
include('../html/config.php');
include(STORE_DIR.'/config/config.php');
include('../html/include/my_functions.php');
include('../html/include/common.php') ;
include('../html/include/openssl_functions.php') ;

CA_generate_crl();
