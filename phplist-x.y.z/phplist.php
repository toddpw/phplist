<?php
# class to become plugin for the webbler http://demo.tincan.co.uk
# this file is not used in the standalone version

class phplist extends DefaultPlugin {
  var $DBstructure = array();
  var $tables = array();
  var $table_prefix = "";
  var $VERSION = "dev";

  function phplist() {
    global $tables,$config;
    $table_prefix = "phplist_";
    $this->table_prefix = $table_prefix;
    $usertable_prefix = "";
    include $config["code_root"]."/uploader/plugins/phplist/".$this->coderoot()."/structure.php";
    $this->DBstructure = $DBstruct;

    $this->tables = array(
      "user" => $usertable_prefix . "user",
      "user_history" => $usertable_prefix . "user_history",
      "list" => $table_prefix . "list",
      "listuser" => $table_prefix . "listuser",
      "user_blacklist" => $table_prefix . "user_blacklist",
      "user_blacklist_data" => $table_prefix . "user_blacklist_data",
      "message" => $table_prefix . "message",
      "messagedata" => $table_prefix. "messagedata",
      "listmessage" => $table_prefix . "listmessage",
      "usermessage" => $table_prefix . "usermessage",
      "attribute" => $usertable_prefix . "attribute",
      "user_attribute" => $usertable_prefix . "user_attribute",
      "sendprocess" => $table_prefix . "sendprocess",
      "template" => $table_prefix . "template",
      "templateimage" => $table_prefix . "templateimage",
      "bounce" => $table_prefix ."bounce",
      "user_message_bounce" => $table_prefix . "user_message_bounce",
      "user_message_forward" => $table_prefix . 'user_message_forward',
      "config" => $table_prefix . "config",
      "admin" => $table_prefix . "admin",
      "adminattribute" => $table_prefix . "adminattribute",
      "admin_attribute" => $table_prefix . "admin_attribute",
      "admin_task" => $table_prefix . "admin_task",
      "task" => $table_prefix . "task",
      "subscribepage" => $table_prefix."subscribepage",
      "subscribepage_data" => $table_prefix . "subscribepage_data",
      "eventlog" => $table_prefix . "eventlog",
      "attachment" => $table_prefix."attachment",
      "message_attachment" => $table_prefix . "message_attachment",
      "rssitem" => $table_prefix . "rssitem",
      "rssitem_data" => $table_prefix . "rssitem_data",
      "user_rss" => $table_prefix . "user_rss",
      "rssitem_user" => $table_prefix . "rssitem_user",
      "listrss" => $table_prefix . "listrss",
      "sessiontable" => "WebblerSessions",
      "urlcache" => $table_prefix . "urlcache",
      'linktrack' => $table_prefix .'linktrack',
      'linktrack_userclick' => $table_prefix.'linktrack_userclick',
      'userstats' => $table_prefix .'userstats',
    );
    $this->addDataType("phplist_1","Mailinglist Pages");
   # $_GET["id"] = 1;
  }

  function name() {
    return "Advanced Mailinglists";
  }

  function home() {
    return "public_html/lists/admin/home.php";
  }

  function coderoot() {
    return "public_html/lists/admin/";
  }

  function codelib() {
    return array(
      "public_html/lists/admin/lib.php",
      "public_html/lists/admin/defaultconfig.inc",
      "public_html/lists/texts/english.inc");
  }

  function frontendlib() {
    return array(
      "public_html/lists/admin/lib.php",
      "public_html/lists/texts/english.inc",
 //     "public_html/lists/admin/subscribelib2.php",
 //     "public_html/lists/admin/phplistObj.php",
#      "public_html/lists/admin/defaultconfig.inc",
    );
  }

  function saveConfig($key,$val,$editable = 1) {
    Sql_Query(sprintf('update %s set value = "%s",editable = %d where item = "%s"',
      $this->tables["config"],$val,$editable,$key));
    if (!Sql_Affected_Rows()) {
      Sql_Query(sprintf('insert into %s (item,value,editable)   values("%s","%s",%d)',$this->tables["config"],$key,$val,$editable));
    }
  }

  function getConfig($key) {
    $req = Sql_Fetch_Row_Query(sprintf('select value from %s where item = "%s"',$this->tables["config"],$key));
    $req[0] = preg_replace('/\[DOMAIN\]/i', $GLOBALS["domain"], $req[0]);
    $req[0] = preg_replace('/\[WEBSITE\]/i', $GLOBALS["website"], $req[0]);
    return $req[0];
  }

  function parseText($data,$leaf,$branch) {
    return $data;
  }

  function getListsAsArray() {
    $list = array();
    $list[0] = "-- None";
    $req = Sql_Query(sprintf('select distinct id, name from %s order by listorder',$this->tables["list"]));
    while ($row = Sql_Fetch_Array($req)){
      $list[$row["id"]] = $row["name"];
    }
    return $list;
  }

  function addUserToList($userid,$listid) {
    Sql_Query(sprintf('replace into %s (userid,listid,entered) values(%d,%d,now())',
      $this->tables["listuser"],$userid,$listid));
  }

  function addEmail($email,$password = "") {
    Sql_Query(sprintf('insert into user set email = "%s",
      entered = now(),password = "%s",
      passwordchanged = now(),disabled = 0,
      uniqid = "%s",htmlemail = 1
      ', $email,$password,getUniqid()),1);
    $id = Sql_Insert_Id();
    if (is_array($_SESSION["userdata"])) {
      saveUserByID($id,$_SESSION["userdata"]);
    }
    $_SESSION["userid"] = $id;
    return $id;
  }

  function addEmailToList($email,$listid) {
    if (empty($email) || empty($listid)) return 0;

    $userid = Sql_Fetch_Row_Query(sprintf('select * from %s where email = "%s"',
      $this->tables["user"],$email));
    if ($userid[0]) {
      $this->addUserToList($userid[0],$listid);
      return 1;
    } else {
      $id = $this->addEmail($email);
      if ($id) {
        $this->addUserToList($id,$listid);
        return 1;
      }
     }
    return 0;
  }

  function addList($listname){
    Sql_Query(sprintf('insert into %s (id,name,modified) values (NULL,"%s",now())',
    $this->tables["list"],$listname));

    return Sql_Insert_Id();
  }

  function deleteListUsers($listid){
    Sql_Query(sprintf('delete from %s where listid = %d',$this->tables["listuser"],$listid));
  }

  function getUserConfig($item,$userid = 0) {
    $value = $this->getConfig($item);
    if ($userid) {
      $user_req = Sql_Fetch_Row_Query("select uniqid from {$this->tables["user"]} where id = $userid");
      $uniqid = $user_req[0];
      # parse for placeholders
      # do some backwards compatibility:
      $url = $this->getConfig("unsubscribeurl");$sep = ereg('\?',$url)?'&':'?';
      $value = eregi_replace('\[UNSUBSCRIBEURL\]', $url.$sep.'uid='.$uniqid, $value);
      $url = $this->getConfig("confirmationurl");$sep = ereg('\?',$url)?'&':'?';
      $value = eregi_replace('\[CONFIRMATIONURL\]', $url.$sep.'uid='.$uniqid, $value);
      $url = $this->getConfig("preferencesurl");$sep = ereg('\?',$url)?'&':'?';
      $value = eregi_replace('\[PREFERENCESURL\]', $url.$sep.'uid='.$uniqid, $value);
    }
    $value = eregi_replace('\[SUBSCRIBEURL\]', $this->getConfig("subscribeurl"), $value);
   #0013076: Blacklisting posibility for unknown users
    $value = eregi_replace('\[BLACKLISTURL\]', $this->getConfig("blacklisturl"), $value);
    if ($value == "0") {
      $value = "false";
    } elseif ($value == "1") {
      $value = "true";
    }
    return $value;
  }

  function confirmUser($userid) {
    Sql_Query(sprintf('update %s set confirmed = 1 where id = %d',$this->tables["user"],$userid));
  }

  function confirmEmail($email) {
    Sql_Query(sprintf('update %s set confirmed = 1 where email = "%s"',$this->tables["user"],$email));
  }


  function userEmail($userid = 0) {
    $user_req = Sql_Fetch_Row_Query("select email from {$this->tables["user"]} where id = $userid");
    return $user_req[0];
  }    

  function sendConfirmationRequest($userid) {
    $subscribemessage = ereg_replace('\[LISTS\]', '', $this->getUserConfig("subscribemessage",$userid));
    $this->sendMail($this->userEmail($userid), $this->getConfig("subscribesubject"), $subscribemessage);
    Sql_Query(sprintf('update %s set confirmed = 0 where id = %d',$this->tables["user"],$userid));
  }
  
  function sendMail ($to,$subject,$message,$header = "",$parameters = "") {
    mail($to,$subject,$message);
    dbg("mail $to $subject");
    if (TEST)
      return 1;
    if (!$to)  {
      logEvent("Error: empty To: in message with subject $subject to send");
      return 0;
    } elseif (!$subject) {
      logEvent("Error: empty Subject: in message to send to $to");
      return 0;
    }
    if (isBlackListed($to)) {
      logEvent("Error, $to is blacklisted, not sending");
      Sql_Query(sprintf('update %s set blacklisted = 1 where email = "%s"',$GLOBALS["tables"]["user"],$to));
      addUserHistory($to,"Marked Blacklisted","Found user in blacklist while trying to send an email, marked black listed");
      return 0;
    }
    $v = phpversion();
    $v = preg_replace("/\-.*$/","",$v);
    if ($GLOBALS["message_envelope"]) {
      $header = rtrim($header);
      if ($header)
        $header .= "\n";
      $header .= "Errors-To: ".$GLOBALS["message_envelope"];
      if (!$parameters || !ereg("-f".$GLOBALS["message_envelope"],$parameters)) {
        $parameters = '-f'.$GLOBALS["message_envelope"];
      }
    }
    $header .= "X-Mailer: PHPlist v".VERSION.' (http://www.phplist.com)'."\n";
  
    if (WORKAROUND_OUTLOOK_BUG) {
      $header = rtrim($header);
      if ($header)
        $header .= "\n";
      $header .= "X-Outlookbug-fixed: Yes";
      $message = preg_replace("/\r?\n/", "\r\n", $message);
    }
  
    if (!ereg("dev",VERSION)) {
      if ($v > "4.0.5" && !ini_get("safe_mode")) {
        if (mail($to,$subject,$message,$header,$parameters))
          return 1;
        else
          return mail($to,$subject,$message,$header);
      }
      else
        return mail($to,$subject,$message,$header);
    } else {
      # send mails to one place when running a test version
      $message = "To: $to\n".$message;
      if ($GLOBALS["developer_email"]) {
        return mail($GLOBALS["developer_email"],$subject,$message,$header,$parameters);
      } else {
        print "Error: Running CVS version, but developer_email not set";
      }
    }
  }


  function display($subtype,$name,$value,$docid = 0) {
    global $config;
    $data = parseDelimitedData($value);
    $html = sprintf('<input type=hidden name="%s"
       value="%d">',$name,$subtype,$subtype);
    switch ($subtype) {
      case "1":
        $html .= '<p>Select the form to use for users to subscribe</p>';
        $req = Sql_Query(sprintf('select * from %s where active',$this->tables["subscribepage"]));
        $html .= sprintf('<select name="%s_spage">',$name);
        $html .= sprintf('<option value="0"> -- select one</option>');
        while ($row = Sql_Fetch_Array($req)) {
          $selected = $data["spage"] == $row["id"] ? "selected":"";
          $html .= sprintf('<option value="%s" %s> %s</option>',$row["id"],$selected,$row["title"]);
        }
        return $html;
    }
    return "Invalid subtype: $subtype";
  }

  function adminTasks() {
    $tasks = array(
      "home" => "Administer Mailinglists",
      "send" => "Send a message",
      "configure" => "Configure Mailinglists",
      "list" => "Lists",
      "messages" => "Messages",
      "reconcileusers" => "Reconcile",
      "export" => "Export Emails",
      "attributes" => "Attributes",
      "spage" => "Configure Subscribe Pages",
    );
    if ($this->tables["attribute"] && Sql_Table_Exists($this->tables["attribute"])) {
      $res = Sql_Query("select * from {$this->tables['attribute']}",1);
      while ($row = Sql_Fetch_array($res)) {
        if ($row["type"] != "checkbox" && $row["type"] != "textarea" && $row["type"] != "textline" && $row["type"] != "hidden")
          $tasks["editattributes&"."id=".$row["id"]] = '=&gt; '.strip_tags($row["name"]);
      }
    }
    $tasks["templates"] = "Templates";
    $tasks["send"] = "Send a message";
    $tasks["bounces"] = "View Bounces";
    $tasks["eventlog"] = "Eventlog";
    return $tasks;
  }

  function adminPages() {
    $tasks = array(
      "home" => "Administer Mailinglists",
      "list" => "Lists",
      "configure" => "Configure",
      "messages" => "Messages",
      "reconcileusers" => "Reconcile",
      "export" => "Export Emails",
      "attributes" => "Attributes",
      "editattributes" => "Edit Attributes",
      "templates" => "Templates",
      "send" => "Send a message",
      "bounces" => "View Bounces",
      "eventlog" => "Eventlog",
      "spage" => "Configure Subscribe Pages",
      "spageedit" => "Edit a Subscribe Page",
      "viewtemplate" => "View a message template",
      "image" => "Image",
      "template" => "Edit a template",
      "dbcheck" => "Check DB",
      "processqueue" => "Process Queue",
      "editlist" => "Edit a List",
      "members" => "Members of a list",
      "message" => "View a message",
      "users" => "List Users",
    );
    return $tasks;
  }

  function selectPage($id) {
    if (!$id) return '<!-- no subscribe page defined -->';
    $html = '';
#    if (preg_match("/(\w+)/",$_GET["p"],$regs)) {
    switch ($_GET["p"]) {
      case "preferences":
        if (!$_GET["id"]) $_GET["id"] = $id;
        require $this->coderoot()."/subscribelib2.php";
        $html = PreferencesPage($id,$userid);
        break;
      case "confirm":
        $html = ConfirmPage($id);
        break;
      case "unsubscribe":
        $html = UnsubscribePage($id);
        break;
      default:
      case "subscribe":
        require $this->coderoot() ."/subscribelib2.php";
        $html = SubscribePage($id);
        break;
    }
    return $html;
  }

  function show($dbdata,$leaf,$branch,$fielddata) {
    global $config;
    $GLOBALS["dontcache"] = 1;
    $data = parseDelimitedData($dbdata);
    switch ($data["subtype"]) {
      case "1":
        $phplistObj = new phplistObj($data["spage"]);
        $html .= 'PHPlist'.$phplistObj->PageContent();
        return $html;
        break;
      default: return "Invalid Subtype";
    }
  }

  function initialise() {
    global $config;
    foreach($this->DBstructure as $table => $val){
      if (!Sql_Table_exists($table)) {
    #    print "creating $table <br>\n";
        Sql_Create_Table($this->tables[$table],$this->DBstructure[$table]);
      }
    }
  }

  function isInitialised() {
    global $config;
    return Sql_Table_Exists($this->tables["list"]);
  }

  function upgrade() {
    global $config,$tables;
    $doit = "yes";
    $tables = $this->tables;
    include $config["code_root"].'/'.$config["uploader_dir"].'/plugins/phplist/'.$this->coderoot()."/upgrade.php";
  }

  function version() {
    return $this->VERSION;
  }

  function duplicateLeaf($from,$to,$table) {
  }

  function deleteLeaf($leaf,$fielddata,$table) {
  }

  function store($itemid,$fielddata,$value,$table) {
    global $config;
    $data["name"] = $fielddata["name"];
    $data["subtype"] = $_POST[$value];
    if ($data["subtype"] == 1) {
      # save link info
      $data["spage"] = $_POST[$fielddata["name"]."_spage"];
      $data["subtype"] = 1;
    }

    Sql_query(sprintf('replace into %s values("%s",%d,"%s")',$table,$fielddata["name"],$itemid,delimited($data)));
  }

}
?>
