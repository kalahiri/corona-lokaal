<?php
/**
 * This file is written by Kalahiri in 2021 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Send e-mail
 * 
 *  Controleer of je de juiste accountgegevens hebt ingevuld.
 *  Controleer of je de test-instelling bij function send() hebt uitgezet.
 * 
 * 
 *  Om een lage spamscore te krijgen:
 * - zorg dat DKIM en SPF op de server aanstaan.
 * - Zorg dat er een DMARC record op de server staat: https://help.directadmin.com/item.php?id=596
 *      Navigate to "DNS Management"
 *      Click "Add Record"
 *      For "Record Type", select "TXT"
 *      TXT Record Type, seelct "DMARC"
 *      Enter your email into the "Aggregate Email (RUA)" field: spam-reports@domain.com
 *      Click "Add"
 * - zorg voor foutloze HTML-code
 * - Voeg een List Unsubscribe header toe aan je headers
 *      List-Unsubscribe: <mailto: unsubscribe@example.com?subject=unsubscribe>
 *      (of met een landing page omdat niet alle mailapps mailto in de header aankunnen):
 *      List-Unsubscribe: <mailto: unsubscribe@example.com?subject=unsubscribe>, <http://www.example.com/unsubscribe.html>
 * 
 * 
 * Test je spamscore nij https://www.mail-tester.com/
 * 
 * Help bij PHPmailer: https://phpmailer.github.io/PHPMailer/classes/PHPMailer.PHPMailer.PHPMailer.html
 * 
 * LET OP!!!  The idiot developers do not use UTF-8 as default char set. Je moet dat in PHPMailer zelf aanzetten
 *              dat kan door op regel 78 public $CharSet = self::CHARSET_ISO88591; 
 *              te veranderen in public $CharSet = self::CHARSET_UTF8;
 * 
 */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


class Mailer {
    private $phpMailer;
    private $view;
    private $subject;


    /**
     * Account instellingen voor het VERZEND account.
     */

    private $accountHost; // Set the SMTP server to send through
    private $accountDisplayname;  SMTP username
    private $accountUsername; // SMTP username
    private $accountPassword; // SMTP password
    private $contactPage; 






    /**
     * Constructor
     */
    public function __construct() {
        require_once( 'includes/phpmailer/PHPMailer.php' );
        require_once( 'includes/phpmailer/Exception.php' );
        require_once( 'includes/phpmailer/SMTP.php' );

        //Instantiation and passing `true` enables exceptions
        $this->phpMailer = new PHPMailer( true );

        // Debugging
        // Geef debugverslag op de webpagina van elk stapje in de verzending. uitzetten in productieomgeving
        // $this->phpMailer->SMTPDebug = SMTP::DEBUG_SERVER; 
        $this->phpMailer->SMTPDebug = SMTP::DEBUG_OFF;


    }


    /**
     * Set the SMTP server to send through
     */
    public function setSendFromEmailHost( $emailHost ){
        $this->accountHost = $emailHost;
    }

    /**
     * SMTP name to display with account send from
     */
    public function setSendFromEmailDisplayname( $emailDisplayName ) {
        $this->accountDisplayname = $emailDisplayName;
    }

    /**
     * SMTP username
     */
    public function setSendFromEmailAddress( $emailaddress ) {
        $this->accountUsername = $emailaddress; 
    }

    /**
     * SMTP password
     */
    public function setSendFromEmailPassword( $emailPassword ) {
        $this->accountPassword = $emailPassword;
    }

    /**
     * Contact page containing contact information for recipients.
     */
    public function setSendFromContactPage( $contactPageUrl ) {
        $this->contactPage = $contactPageUrl;
    }


    /**
     * Initialize mailsystem. Account settings have to be done previously.
     */
    public function initMailer() {
        $this->init();
    }


    /**
     * Add a recipient for the e-mail.
     */
    public function addRecipient( $email, $name = '' ) {
        if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) return false;
        $this->phpMailer->addAddress( $email, $name );
        return true;
    }


    /**
     * Add a CC recipient for the e-mail.
     */
    public function addCC( $email, $name = '' ) {
        if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) return false;
        $this->phpMailer->addCC( $email, $name );
        return true;
    }


    /**
     * Add a BCC recipient for the e-mail.
     */
    public function addBCC( $email, $name = '' ) {
        if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) return false;
        $this->phpMailer->addBCC( $email, $name );
        return true;
    }


    /**
     * Add a BCC recipient for the e-mail.
     * TODO: to be implemented
     */
    public function addAttachment( $file, $name = '' ) {
            //Attachments
            //$this->phpMailer->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
            //$this->phpMailer->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name
    }

    /**
     * Set subject of the email
     */
    public function setSubject( $subject ) {
        $this->subject = $subject;
    }


    /**
     * Initialise PHPMailer: setp SMTP account to send from
     */
    private function init() {
        $this->phpMailer->isSMTP(); // Send using SMTP
        $this->phpMailer->Host       = $this->accountHost;  // Set the SMTP server to send through
        $this->phpMailer->SMTPAuth   = true; // Enable SMTP authentication
        $this->phpMailer->Username   = $this->accountUsername; // SMTP username
        $this->phpMailer->Password   = $this->accountPassword; // SMTP password
        $this->phpMailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
        $this->phpMailer->Port       = 587;
        $this->phpMailer->setFrom( $this->accountUsername, $this->accountDisplayname);
        $this->phpMailer->addCustomHeader('List-Unsubscribe: <mailto: ' . $this->accountUsername 
                                            . '?subject=unsubscribe>, '. $this->contactPage );
       // $this->phpMailer->addBCC( $this->accountUsername, $this->accountDisplayname );
    }


    /**
     * Set HTMl coded content for users using HTML view
     * Best to set both HTML content as well as plain text content
     */
    public function setHtmlContent( $htmlContent ) {
        $this->phpMailer->Subject = $this->subject;
        $this->phpMailer->isHTML(true);
        $this->phpMailer->Body = $htmlContent;
    }

    /**
     * Set the plain text content for users without HTML view
     * Best to set both HTML content as well as plain text content
     */
    public function setPlainTextContent( $plainTextContent ) {
        $this->phpMailer->AltBody = $plainTextContent;
    }


    /**
     * Send the email
     */
    public function send() {
        try {
            $this->phpMailer->send();
            return true;
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$this->phpMailer->ErrorInfo}";
            return false;
        }
        return false;
    }



    
}
