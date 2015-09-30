<?php
namespace App\Model;

use Nette,
    Nette\Mail\Message,
    Nette\Mail\SmtpMailer;

/**
 * Třída zajišťující odesílání emailů lidem.
 *
 * @author bkralik
 */
class DruzstvoMailer extends Nette\Object {
    
    /**
     * @var Nette\Mail\IMailer 
     */
    protected $mailer;
    
    /**
     * @var string
     */
    protected $mailerFromAddress;
    
    /**
     * Mail template folder
     */
    CONST templateFolder = '../app/presenters/templates/Emaily/';
    
    public function __construct($mailerFromAddress, Nette\Mail\IMailer $mailer) {
        $this->mailerFromAddress = $mailerFromAddress;
        $this->mailer = $mailer;
    }
    
    /**
     * Funkce odesílající emaily s dokumenty lidem.
     * 
     * Maily odesílá podle šablony v presenters/Emaily/dokument.latte
     * 
     * @param \Nette\Database\Table\ActiveRow $dokument Dokument k rozeslání
     * @param \Nette\Database\Table\Selection $lidi Lidé k obmailování
     * @param string $link Link na dokument
     * @return integer Počet odeslaných emailů
     */
    public function sendDokument($dokument, $lidi, $link) {
        $mail = new Message;
        $mail->setFrom($this->mailerFromAddress)
             ->setSubject('Nový dokument SVJ Čiklova 647/3');
        
        $pocet = 0;
        foreach($lidi as $clovek) {
            $mail->addBcc($clovek->email, $clovek->jmeno);
            $pocet++;
        }
        
        $latte = new \Latte\Engine;
        $params = array(
            'orderId' => 123,
            'link' => $link,
            'dokument' => $dokument
        );
        $mail->setHtmlBody($latte->renderToString(self::templateFolder.'dokument.latte', $params));
        
        $mail->addAttachment($dokument->jmeno, file_get_contents(Dokument::DOKUMENTY_FOLDER.$dokument->soubor));
        
        $this->mailer->send($mail);
        
        return($pocet);
    }
    
    /**
     * Funkce odesílající email s heslem po registraci.
     * 
     * Maily odesílá podle šablony v presenters/Emaily/novyucet.latte
     * 
     * @param string $jmeno Jméno člověka
     * @param string $heslo Heslo
     * @param string $email Email
     * @return integer Počet odeslaných mailů
     */
    public function sendRegistrace($jmeno, $heslo, $email) {
        $mail = new Message;
        $mail->setFrom($this->mailerFromAddress)
             ->setSubject('Registrace na stránkách SVJ Čiklova 647/3');
        
        $mail->addTo($email, $jmeno);
        
        $latte = new \Latte\Engine;
        $params = array(
            'email' => $email,
            'heslo' => $heslo
        );
        $mail->setHtmlBody($latte->renderToString(self::templateFolder.'novyucet.latte', $params));

        $this->mailer->send($mail);
        
        return(1);
    }
    
    public function sendKontaktMail($clovekMail, $clovekName, $zprava, $spravci) {
        $mail = new Message;
        $mail->setFrom($this->mailerFromAddress)
            ->setSubject('Nová zpráva z konktaktního formuláře webu SVJ Čiklova 647/3')
            ->addReplyTo($clovekMail)
            ->setBody($clovekName." s emailem ".$clovekMail." posílá následující dotaz: \n\n".$zprava);
        
        foreach($spravci as $address) {
            $mail->addTo($address);
        }

        $this->mailer->send($mail);
        
        return(1);
    }
}
