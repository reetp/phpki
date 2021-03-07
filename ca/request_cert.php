<?php

include('../config.php');
include(STORE_DIR.'/config/config.php');
include('../include/my_functions.php');
include('../include/common.php') ;
include('../include/openssl_functions.php') ;

# User's preferences file
$user_cnf = $config['home_dir'] . "/config/user-".strtr($PHPki_user, '/\\', '|#').'.php';

# Retrieve GET/POST values
$form_stage   = gpvar('form_stage');
$submit       = gpvar('submit');

$country      = gpvar('country');
$province     = gpvar('province');
$locality     = gpvar('locality');
$organization = gpvar('organization');
$unit         = gpvar('unit');
$common_name  = gpvar('common_name');
$email        = gpvar('email');
$passwd       = gpvar('passwd');
$passwdv      = gpvar('passwdv');
$expiry       = gpvar('expiry');
$keysize      = gpvar('keysize');
$cert_type    = gpvar('cert_type');
$dns_names    = gpvar('dns_names');
$ip_addr      = gpvar('ip_addr');

# To repopulate form after error.
$hidden_fields = '
    <input type=hidden name=country value="' . htvar($country) . '">
    <input type=hidden name=province value="' . htvar($province) . '">
    <input type=hidden name=locality value="' . htvar($locality) . '">
    <input type=hidden name=organization value="' . htvar($organization) . '">
    <input type=hidden name=unit value="' . htvar($unit) . '">
    <input type=hidden name=common_name value="' . htvar($common_name) . '">
    <input type=hidden name=email value="' . htvar($email) . '">
    <input type=hidden name=passwd value="' . htvar($passwd) . '">
    <input type=hidden name=passwdv value="' . htvar($passwdv) . '">
    <input type=hidden name=expiry value="' . htvar($expiry) . '">
    <input type=hidden name=keysize value="' . htvar($keysize) . '">
    <input type=hidden name=cert_type value="' . htvar($cert_type) . '">
    <input type=hidden name=dns_names value="' . htvar($dns_names) . '">
    <input type=hidden name=ip_addr value="' . htvar($ip_addr) . '">
';


switch ($form_stage) {
    case 'validate':
        $er = '';

        if (! $country) {
            $er .= 'Missing Country<br>';
        }
        if (! $province) {
            $er .= 'Missing State/Province<br>';
        }
        if (! $locality) {
            $er .= 'Missing Locality (City/County)<br>';
        }
        if (! $organization) {
            $er .= 'Missing Organization (Company/Agency)<br>';
        }
        if (! $unit) {
            $er .= 'Missing Unit/Department<br>';
        }
        if (! $common_name) {
            $er .= 'Missing E-mail User\'s Full Name<br>';
        }
        if (! $email) {
            $er .= 'Missing E-mail Address<br>';
        }

        if (($cert_type == 'email' || $cert_type == 'email_signing') && ! $passwd) {
            $er .= 'Missing Certificate Password<br>';
        }
        if (($cert_type == 'email' || $cert_type == 'email_signing') && ! $passwdv) {
            $er .= 'Missing Certificate Password Verification "Again"<br>';
        }

        if ($passwd && strlen($passwd) < 8) {
            $er .= 'Certificate password is too short.<br>';
        }

        if ($passwd and $passwd != $passwdv) {
            $er .= 'Password and password verification do not match.<br>';
        }

        //if ( ! is_alnum($passwd) or ! is_alnum($passwdv) )
        //  $er .= 'Password contains invalid characters.<br>';

        if ($email && ! is_email($email)) {
            $er .= 'E-mail address ('. htvar($email) . ') may be invalid.<br>';
        }

        $ip_ar=explode("\n", $ip_addr);
        foreach ($ip_ar as $value) {
            if ($value && ! is_ip($value)) {
                $er .= 'IP address ('. htvar($value) . ') may be invalid.<br>';
            }
        }

        $dns_n=explode("\n", $dns_names);
        foreach ($dns_n as $value) {
            if ($value && ! is_fqdn(trim($value))) {
                $er .= 'DNS Name ('. htvar($value) . ') may be invalid.<br>';
            }
        }

        if ($er) {
            $er = '<h2>ERROR(S) IN FORM:</h2><h4><blockquote>' . $er . '</blockquote></h4>';
        }

        if ($email && ($serial = CAdb_in($email, $common_name))) {
            $er = '';
            $certtext = CA_cert_text($serial);
            $er .= '<h2>A valid certificate already exists for ' . htvar("$common_name  <$email>") . '</h2>';
            $er .= '</font><blockquote><pre> ' . htvar($certtext) . ' </pre></blockquote>';
        }

        if ($er) {
            printHeader();
            ?>

        <form action='<?php echo $PHP_SELF?>' method=post>
        <input type=submit name=submit value='Go Back'>
        <font color=#ff0000><?php echo $er?></font>
        <br><input type=submit name=submit value='Go Back'>

        <?php
        print $hidden_fields;
        print "</form>";

        printFooter();
        break;
        }

    case 'confirm':
        printHeader();

        ?>
        <h4>You are about to create a certificate using the following information:</h4>
        <table width=500><tr>
        <td width=25% style='white-space: nowrap'>
        <p align=right>
        User's Name<br>
        E-mail Address<br>
        Organization<br>
        Department/Unit<br>
        Locality<br>
        State/Province<br>
        Country<br>
        Certificate Life<br>
        Key Size<br>
        Certificate Use<br>
        <?php
        if ($cert_type == 'server') {
            print 'DNS Alt Names<br>';
            print 'IP Addresses<br>';
        }
        ?>
        </p>
        </td>

        <td>
        <?php
        print htvar($common_name) . '<br>';
        print htvar($email) . '<br>';
        print htvar($organization) . '<br>';
        print htvar($unit) . '<br>';
        print htvar($locality) . '<br>';
        print htvar($province) . '<br>';
        print htvar($country) . '<br>';
        print htvar($expiry). ' Year'.($expiry == 1 ? '' : 's').'<br>';
        print htvar($keysize). ' bits<br>';

        switch ($cert_type) {
            case 'email':
                print 'E-mail, SSL Client' . '<br>';
                break;
            case 'email_signing':
                print 'E-mail, SSL Client, Code Signing' . '<br>';
                break;
            case 'server':
                print 'SSL Server' . '<br>';
                print htvar($dns_names). '<br>';
                print htvar($ip_addr). '<br>';
                break;
            case 'vpn_client':
                print 'VPN Client Only' . '<br>';
                break;
            case 'vpn_server':
                print 'VPN Server Only' . '<br>';
                break;
            case 'vpn_client_server':
                print 'VPN Client, VPN Server' . '<br>';
                break;
            case 'time_stamping':
                print 'Time Stamping' . '<br>';
        }
        ?>
        </td>

        </tr></table>

        <h4>Are you sure?</h4>
        <p><form action='<?php echo $PHP_SELF?>' method=post>
    <?php echo  $hidden_fields ?>
    <input type=hidden name=form_stage value=final>
    <input type=submit name=submit value='Yes.  Create and Download' >&nbsp;
    <input type=submit name=submit value='Yes.  Just Create' >&nbsp;
    <input type=submit name=submit value='Go Back'>
    </form>

    <?php
    printFooter();

    # Save user's defaults
    $fp = fopen($user_cnf, 'w');
    $x = '<?php
    $country      = \''.addslashes($country).'\';
    $locality     = \''.addslashes($locality).'\';
    $province     = \''.addslashes($province).'\';
    $organization = \''.addslashes($organization).'\';
    $unit         = \''.addslashes($unit).'\';
    $expiry       = \''.addslashes($expiry).'\';
    $keysize      = \''.addslashes($keysize).'\';
    ?>';
    fwrite($fp, $x);
    fclose($fp);

        break;

    case 'final':
        if ($submit == "Yes  Create and Download" || $submit == "Yes.  Just Create") {
            if (! $serial = CAdb_in($email, $common_name)) {
                list($ret,$errtxt) = CA_create_cert($cert_type, $country, $province, $locality, $organization, $unit, $common_name, $email, $expiry, $passwd, $keysize, $dns_names, $ip_addr);

                if (! $ret) {
                        printHeader();
                    ?>
                    <form action="<?php echo $PHP_SELF?>" method="post">
                    <font color=#ff0000>
                    <h2>There was an error creating your certificate.</h2></font><br>
                    <blockquote>
                    <h3>Debug Info:</h3>
                    <pre><?php echo $errtxt?></pre>
                    </blockquote>
                    <p>
                    <?php echo $hidden_fields?>
                    <input type=submit name=submit value=Back>
                    <p>
                </form>
                <?php
                printFooter();
                break;
                } else {
                    $serial = $errtxt;
                }
            }
        }
    
        if ($submit == "Yes  Create and Download") {
            switch ($cert_type) {
                case 'server':
        #               upload(array("$config[private_dir]/$serial-key.pem","$config[new_certs_dir]/$serial.pem",$config['cacert_pem']), "$common_name ($email).pem",'application/pkix-cert');
                    upload(array($config['private_dir'] . "/$serial-key.pem",$config['new_certs_dir'] . "/$serial.pem",$config['cacert_pem']), $rec['common_name'] . "-Bundle.pem", 'application/pkix-cert');
                    break;
                case 'email':
                case 'email_signing':
                case 'time_stamping':
                case 'vpn_client_server':
                case 'vpn_client':
                case 'vpn_server':
        #               upload("$config[pfx_dir]/$serial.pfx", "$common_name ($email).p12", 'application/x-pkcs12');
                    upload($config['pfx_dir'] . "/$serial.pfx", $rec['common_name'] . ".p12", 'application/x-pkcs12');
                    break;
            }
        
            # Clear common_name fields
            $common_name = '';
            break;
        }
    
    # Clear common_name fields
        $common_name = '';

    // We could add 'return to index or create another certificate'

    default:
        #
        # Default fields to reasonable values if necessary.
        #
        if (! $submit and file_exists($user_cnf)) {
            include($user_cnf);
        }

        if (! $country) {
            $country = $config['country'];
        }
        if (! $province) {
            $province = $config['province'];
        }
        if (! $locality) {
            $locality = "";
        }
        if (! $organization) {
            $organization = "";
        }
        if (! $unit) {
            $unit = "";
        }
        if (! $email) {
            $email = "";
        }
        if (! $expiry) {
            $expiry = 1;
        }
        if (! $keysize) {
            $keysize = 2048;
        }
        if (! $cert_type) {
            $cert_type = 'email';
        }
        if (! $dns_names) {
            $dns_names = "";
        }
        if (! $ip_addr) {
            $ip_addr = "";
        }

        printHeader();
        ?>
    
        <body onLoad="self.focus();document.request.common_name.focus();document.request.cert_type.onchange();">
        <form action="<?php echo $PHP_SELF?>" method=post name=request>
        <table width=99%>
        <th colspan=2><h3>Certificate Request Form</h3></th>
    
        <tr>
        <td width=30%>Common Name<font color=red size=3> *</font><br>(i.e. User real name or computer hostname - used as SubjectAltName)</td>
        <td><input type=text name=common_name value="<?php echo  htvar($common_name)?>" size=50 maxlength=60></td>
        </tr>
    
        <tr>
        <td>E-mail Address<font color=red size=3> *</font></td>
        <td><input type=text name=email value="<?php echo htvar($email)?>" size=50 maxlength=60></td>
        </tr>
    
        <tr>
        <td>Organization (Company/Agency)<font color=red size=3> *</font></td>
        <td><input type=text name=organization value="<?php echo htvar($organization)?>" size=60 maxlength=60></td>
        </tr>
    
        <tr>
        <td>Department/Unit<font color=red size=3> *</font> </td><td><input type=text name=unit value="<?php echo  htvar($unit) ?>" size=40 maxlength=60></td>
        </tr>
    
        <tr>
        <td>Locality (City/County)<font color=red size=3> *</font></td><td><input type=text name=locality value="<?php echo  htvar($locality) ?>" size=30 maxlength=30></td>
        </tr>
    
        <tr>
        <td>State/Province<font color=red size=3> *</font></td><td><input type=text name=province value="<?php echo  htvar($province) ?>" size=30 maxlength=30></td>
        </tr>
    
        <tr>
        <td>Country<font color=red size=3> *</font></td>
        <td><input type=text name=country value="<?php echo  htvar($country) ?>" size=2 maxlength=2></td>
        </tr>
    
        <tr>
        <td>Certificate Password<font color=red size=3> *</font><br>(Min 8 chars - Mandatory for Email,SSL Client,Code signing)</td>
        <td><input type=password name=passwd value="<?php echo  htvar($passwd) ?>" size=30>&nbsp;&nbsp; Again <input type=password name=passwdv  value="<?php echo  htvar($passwdv) ?>" size=30></td>
        </tr>
    
        <tr>
        <td>Certificate Life<font color=red size=3>*</font> </td>
        <td><select name=expiry>

        <?php
        print "<option value=0.083 " . ($expiry == 1 ? "selected='selected'" : "") . " >1 Month</option>\n" ;
        print "<option value=0.25 " . ($expiry == 1 ? "selected='selected'" : "") . " >3 Months</option>\n" ;
        print "<option value=0.5 " . ($expiry == 1 ? "selected='selected'" : "") . " >6 Months</option>\n" ;
        print "<option value=1 " . ($expiry == 1 ? "selected='selected'" : "") . " >1 Year</option>\n" ;
        for ($i = 2; $i <= 5; $i++) {
            print "<option value=$i " . ($expiry == $i ? "selected='selected'" : "") . " >$i Years</option>\n" ;
        }
    
        ?>
    
        </select></td>
        </tr>
    
        <tr>
        <td>Key Size<font color=red size=3>*</font> </td>
        <td><select name=keysize>
        <?php
        for ($i = 512; $i <= 4096; $i+= 512) {
            print "<option value=$i " . ($keysize == $i ? "selected='selected'" : "") . ">$i bits</option>\n" ;
        }
        ?>
    
        </select></td>
        </tr>
    
        <tr>
        <td>Certificate Use:<font color=red size=3>*</font> </td>
        <td><select name=cert_type onchange="if (this.value=='server')
            {setVisibility('testrow1',true);setVisibility('testrow2',true);} else {setVisibility('testrow1',false);setVisibility('testrow2',false);}">
        <?php
        print '<option value="email" '.($cert_type=='email'?'selected':'').'>E-mail, SSL Client</option>';
        print '<option value="email_signing" '.($cert_type=='email_signing'?'selected':'').'>E-mail, SSL Client, Code Signing</option>';
        print '<option value="server" '.($cert_type=='server'?'selected':'').'>SSL Server</option>';
        print '<option value="vpn_client" '.($cert_type=='vpn_client'?'selected':'').'>VPN Client Only</option>';
        print '<option value="vpn_server" '.($cert_type=='vpn_server'?'selected':'').'>VPN Server Only</option>';
        print '<option value="vpn_client_server" '.($cert_type=='vpn_client_server'?'selected':'').'>VPN Client, VPN Server</option>';
        print '<option value="time_stamping" '.($cert_type=='time_stamping'?'selected':'').'>Time Stamping</option>';
        ?>
        </select></td>
        </tr>
    
        <tr id="testrow2" name="testrow2" style="visibility:hidden;display:none;">
        <td>Alternative DNS Names<br>(only one per Line)</td><td><textarea name=dns_names cols=30 rows=5><?php echo htvar($dns_names) ?></textarea></td>
        </tr>
    
        <tr id="testrow1" name="testrow1" style="visibility:hidden;display:none;">
        <td>IP's<br>(only one per Line)</td><td><textarea name=ip_addr cols=30 rows=5><?php echo htvar($ip_addr) ?></textarea></td>
        </tr>
        <tr>
            <td>&nbsp</td>
            <td>&nbsp</td>
        </tr>
        <tr>
        <td><font color=red size=3>* Fields are required</td><td><input type=submit name=submit value='Submit Request'><input type=hidden name=form_stage value='validate'></td>
        </tr>
        </table>
        </form>
    <?php
    printFooter();
}

?>
