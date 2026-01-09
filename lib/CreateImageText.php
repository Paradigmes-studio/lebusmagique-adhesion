<?php

class ImageCarteAdhesion
{
	
	private const FONT_BOLD = 'res/arial.ttf';
    private const FONT_SEMI_BOLD = 'res/arial.ttf';
    private const FONT_REGULAR = 'res/arial.ttf';

	public $image=null;
	
    public function generate($adhesionClient)
    {
		echo "creationimage</br>";
       // Create image from existing file
        $image = @imagecreatefromjpeg('res/bgCard.jpg');
		//$image = imagecreatefrompng('res/bgCard.png');
        if (!$image) {
			echo "Erreur create from background image</br>";
			return false;
		}
		
        // Create text colours
        $black = imagecolorallocate($image, 0, 0, 0);
        $orange = imagecolorallocate($image, 240, 126, 48);
 
        // Draw top and bottom line
		// imageline($image, 30, 200, 824, 200, $orange);

        echo "write Nom</br>";
        // Nom
        $text = sprintf('%s', $adhesionClient->last_name);
        $this->writeText($image, 50, 100, 50, $black, self::FONT_BOLD, $text);

        echo "write Prénom</br>";
        // Prénom
        $text = sprintf('%s',$adhesionClient->first_name);
        $this->writeText($image, 50, 170, 50, $black, self::FONT_BOLD, $text);

        echo "write Adherant</br>";
        // N° adhérent
        $text = sprintf("N° d'adhérent : %s", $adhesionClient->id);
        $this->writeText($image, 50, 270, 35, $orange, self::FONT_BOLD, $text);

        echo "write Type</br>";
		// Type d'adhésion
		$text = sprintf("Type d'adhésion :");
		$this->writeText($image, 50, 450, 35, $black, self::FONT_BOLD, $text);
		$text = sprintf("%s", $adhesionClient->adhesion_type);
        $this->writeText($image, 150, 530, 50, $orange, self::FONT_BOLD, $text);
 
        echo "write Date</br>";
		// Date de fin de validité
        $text = sprintf('Date de fin de validité :');
        $this->writeText($image, 50, 650, 35, $black, self::FONT_BOLD, $text);
		$text = sprintf('%s', date_format(new DateTime($adhesionClient->date_fin), 'd-m-Y'));	
        $this->writeText($image, 250, 750, 60, $orange, self::FONT_BOLD, $text);
		
        echo "Save Image</br>";
        // Save image
        if (!imagejpeg($image, 'res/Carte'. $adhesionClient->id .'.jpg', 90)) {
			imagedestroy($image);
			echo "Erreur create final image</br>";
			return false;
		} else {
			imagedestroy($image);
			echo "image crée avec succés</br>";
			return true;
		}
    }
 
    private function writeText($image, int $x, int $y, int $fontSize, $fontColour, $fontPath, string $text)
    {
       
        #if (!imagestring($image, $fontSize*10, $x, $y, $text, $fontColour))
        if (!imagettftext($image, $fontSize, 0, $x, $y, $fontColour, $fontPath, $text))
			echo "erreur write</br>";
        else
            echo "write ok</br>";

    }
	
	
}
?>