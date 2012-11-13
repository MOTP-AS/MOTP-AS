<?php

//ldap_set_option($ds,LDAP_OPT_PROTOCOL_VERSION,3);
//ldap_set_option($ds, LDAP_OPT_REFERRALS, 0); // "In order to perform the searches on Windows 2003 Server Active Directory you have

function SyncLdapUsers()
{
$counter=0;
$newuser = new stdClass();
$ldapconn = ldap_connect(@LDAP_SERVER);
$ldf=str_replace("&amp;", "&", @LDAP_FILTER);


  if ($ldapconn)
   $ldapbind = ldap_bind($ldapconn, @LDAP_LOGIN, @LDAP_PASSWD);



  $justthese = array("displayname", "mail", "company", "department", "physicaldeliveryofficename", "mobile", "ipphone", "telephonenu
  $sr=ldap_search($ldapconn, @LDAP_DN,$ldf, $justthese);
  $info = ldap_get_entries($ldapconn, $sr);

 for ($i=0; $i < $info["count"]; $i++) {

  $userenbl=true;
  if (($info[$i]["useraccountcontrol"][0] == "66050" ||$info[$i]["useraccountcontrol"][0] == "514"))
  $userenbl=false;



    $id = get_user_id ($info[$i]["samaccountname"][0]);
    If ($id>0){
    $counter++;
    $newuser->id=$id;
    $newuser->user = $info[$i]["samaccountname"][0];
    $newuser->role = "U";
    $newuser->name = $info[$i]["displayname"][0];
    $newuser->enabled=$userenbl;
    $newuser->tries=0;
    $newuser->llogin=0;
    update_user ($newuser);
     }
     else
     {
      If($userenbl){
       $newuser->user = $info[$i]["samaccountname"][0];
       $newuser->role = "U";
       $newuser->enabled=$userenbl;
       $newuser->name = $info[$i]["displayname"][0];
       $counter++;
       log_audit($_SESSION['user'],"Ldap import.New user has added.", $info[$i]["samaccountname"][0]." (".$info[$i]["displayname"][0
       insert_user($newuser);
       }
     }
   }
return $counter;
}

