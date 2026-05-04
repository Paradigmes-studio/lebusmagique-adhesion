<?php

class ImageCarteAdhesion
{
	
	private const FONT_BOLD = 'res/arial.ttf';
    private const FONT_SEMI_BOLD = 'res/arial.ttf';
    private const FONT_REGULAR = 'res/arial.ttf';

	public $image=null;
	
    public function generate($adhesionClient)
    {
        $image = @imagecreatefromjpeg('res/bgCard.jpg');
        if (!$image) {
			return false;
		}

        $black = imagecolorallocate($image, 0, 0, 0);
        $orange = imagecolorallocate($image, 240, 126, 48);

        $text = sprintf('%s', $adhesionClient->last_name);
        $this->writeText($image, 50, 100, 50, $black, self::FONT_BOLD, $text);

        $text = sprintf('%s',$adhesionClient->first_name);
        $this->writeText($image, 50, 170, 50, $black, self::FONT_BOLD, $text);

        $text = sprintf("N° d'adhérent : %s", $adhesionClient->id);
        $this->writeText($image, 50, 270, 35, $orange, self::FONT_BOLD, $text);

		$text = sprintf("Type d'adhésion :");
		$this->writeText($image, 50, 450, 35, $black, self::FONT_BOLD, $text);
		$text = sprintf("%s", $adhesionClient->adhesion_type);
        $this->writeText($image, 150, 530, 50, $orange, self::FONT_BOLD, $text);

        $text = sprintf('Date de fin de validité :');
        $this->writeText($image, 50, 650, 35, $black, self::FONT_BOLD, $text);
		$text = sprintf('%s', date_format(new DateTime($adhesionClient->date_fin), 'd-m-Y'));
        $this->writeText($image, 250, 750, 60, $orange, self::FONT_BOLD, $text);

        $ok = imagejpeg($image, 'res/Carte'. $adhesionClient->id .'.jpg', 90);
        imagedestroy($image);
        return (bool)$ok;
    }

    private function writeText($image, int $x, int $y, int $fontSize, $fontColour, $fontPath, string $text)
    {
        imagettftext($image, $fontSize, 0, $x, $y, $fontColour, $fontPath, $text);
    }
	
	
}
?>