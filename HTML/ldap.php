<?php
include 'INC/include.php';
include 'INC/ldap_func.php';
ldap_set_option($ds,LDAP_OPT_PROTOCOL_VERSION,3);
ldap_set_option($ds, LDAP_OPT_REFERRALS, 0); // "In order to perform the searches on Windows 2003 Server Active Directory you have t

If (input($argv[1])==1)
echo "Sync. obj:".SyncLdapUsers()."\n" ;

?>
