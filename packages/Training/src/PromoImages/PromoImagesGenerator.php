<?php declare(strict_types=1);

namespace Pehapkari\Training\PromoImages;

use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Pehapkari\Exception\ShouldNotHappenException;
use Pehapkari\Pdf\PdfFactory;
use Pehapkari\Pdf\RgbColor;
use Pehapkari\Training\Entity\TrainingTerm;
use setasign\Fpdi\Fpdi;

final class PromoImagesGenerator
{
    /**
     * @var string
     */
    private $promoImageOutputDirectory;

    /**
     * @var PdfFactory
     */
    private $pdfFactory;

    public function __construct(string $promoImageOutputDirectory, PdfFactory $pdfFactory)
    {
        $this->promoImageOutputDirectory = $promoImageOutputDirectory;
        $this->pdfFactory = $pdfFactory;
    }

    /**
     * @return string File path to temp promoImages
     */
    public function generateForTrainingTerm(TrainingTerm $trainingTerm): string
    {
        $trainingName = $trainingTerm->getTrainingName();
        $date = $trainingTerm->getStartDateTime()->format('j. n. Y');

        return $this->generateForTrainingNameDateAndParticipantName($trainingName, $date, $trainingTerm);
    }

    private function generateForTrainingNameDateAndParticipantName(
        string $trainingName,
        string $date,
        TrainingTerm $trainingTerm
    ): string {
        $pdf = $this->createLandscapePdfWithFonts();

        $pageId = $pdf->importPage(1);
        $pdf->useTemplate($pageId, 0, 0, 720, 405, true);

        $this->addHeadline($pdf);

        $this->addTrainingName($trainingName, $pdf);
        $this->addTrainingImage($trainingTerm, $pdf);
        $this->addDate($date, $pdf);

        $this->addTrainerImage($trainingTerm, $pdf);
        $this->addTrainerNamePositionAndCompany($trainingTerm, $pdf);

        $destination = $this->createFileDestination($trainingName, $trainingTerm);
        $this->savePdfToDestination($destination, $pdf);

        return $destination;
    }

    private function createLandscapePdfWithFonts(): Fpdi
    {
        $fpdi = $this->pdfFactory->createHorizontalWithTemplate(
            __DIR__ . '/../../../../public/assets/pdf/promo_image.pdf'
        );
        $fpdi->SetFont('OpenSans', '', 14);

        return $fpdi;
    }

    private function addHeadline(Fpdi $fpdi): void
    {
        $fpdi->Image(__DIR__ . '/../../../../public/assets/images/slon_pehapkari_cz.png', 240, 15, 150);

        $this->addTextInSizeToLocation('školení', 17, 390, 37, $fpdi);
    }

    private function addTrainingName(string $trainingName, Fpdi $fpdi): void
    {
        // resize for long lecture names
        $fontSize = $this->resolveTrainingNameFontSize($trainingName);

        $fpdi->SetTextColor(...RgbColor::GREEN);
        $fpdi->SetFont('OpenSans', 'Bold', $fontSize);

        $text = $this->encode($trainingName);
        $fpdi->SetFontSize($fontSize);

        // @see https://stackoverflow.com/a/24258682/1348344
        $fpdi->SetXY(280, 70);
        $fpdi->MultiCell(400, 60, $text);

        // back to black regular
        $fpdi->SetTextColor(...RgbColor::BLACK);
        $fpdi->SetFont('OpenSans', '');
    }

    /**
     * @done
     */
    private function addTrainingImage(TrainingTerm $trainingTerm, Fpdi $fpdi): void
    {
        $trainingImage = $trainingTerm->getTrainingImageAbsolutePath();
        $this->ensureFileExists($trainingImage);

        $imageSquareSize = 140;
        $fpdi->Image($trainingImage, 75, 60, $imageSquareSize, $imageSquareSize);
    }

    /**
     * @done
     */
    private function addDate(string $date, Fpdi $fpdi): void
    {
        $fpdi->SetFont('OpenSans', 'Bold');

        $this->addTextInSizeToLocation($date, 20, 200, 255, $fpdi);

        $fpdi->SetFont('OpenSans', '');
    }

    /**
     * @done
     */
    private function addTrainerImage(TrainingTerm $trainingTerm, Fpdi $fpdi): void
    {
        $trainerImage = $trainingTerm->getTrainerImageAbsolutePath();
        $this->ensureFileExists($trainerImage);

        $imageSquareSize = 160;

        $fpdi->Image($trainerImage, 440, 230, $imageSquareSize, $imageSquareSize);
    }

    private function addTrainerNamePositionAndCompany(TrainingTerm $trainingTerm, Fpdi $fpdi): void
    {
        $this->addTextInSizeToLocation('školí ' . $trainingTerm->getTrainerName(), 20, 180, 310, $fpdi);

        if ($trainingTerm->getTrainerPosition()) {
            $this->addTextInSizeToLocation($trainingTerm->getTrainerPosition(), 16, 180, 330, $fpdi);
        }

        if ($trainingTerm->getTrainerCompany()) {
            $this->addTextInSizeToLocation($trainingTerm->getTrainerCompany(), 16, 180, 350, $fpdi);
        }
    }

    private function createFileDestination(string $trainingName, TrainingTerm $trainingTerm): string
    {
        return $this->promoImageOutputDirectory . '/' .
            sprintf(
                'promo-image-%s-%s.pdf',
                Strings::webalize($trainingName),
                Strings::webalize($trainingTerm->getStartDateTime()->format('Y-m-d'))
            );
    }

    private function savePdfToDestination(string $destination, Fpdi $fpdi): void
    {
        // ensure directory exists
        FileSystem::createDir(dirname($destination));

        // F = filesystem
        $fpdi->Output('F', $destination);
    }

    private function addTextInSizeToLocation(string $text, int $fontSize, int $x, int $y, Fpdi $fpdi): void
    {
        $text = $this->encode($text);
        $fpdi->SetFontSize($fontSize);

        $fpdi->SetXY($x, $y);
        $fpdi->Write(0, $text);
    }

    private function resolveTrainingNameFontSize(string $trainingName): int
    {
        if (Strings::length($trainingName) < 45) {
            return 35;
        }

        if (Strings::length($trainingName) > 45) {
            return 24;
        }

        return 22;
    }

    private function encode(string $string): string
    {
        return (string) iconv('UTF-8', 'windows-1250', $string);
    }

    private function ensureFileExists(string $trainingImage): void
    {
        if (file_exists($trainingImage)) {
            return;
        }

        throw new ShouldNotHappenException(sprintf('File "%s" was not found.', $trainingImage));
    }
}
