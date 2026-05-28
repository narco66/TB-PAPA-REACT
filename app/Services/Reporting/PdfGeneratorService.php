<?php

namespace App\Services\Reporting;

use App\Models\GeneratedReport;
use App\Models\User;
use App\Reports\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Service de génération PDF institutionnel TB-PAPA-CEEAC.
 *
 * Pipeline :
 *   1. Calcul des données via Report::donnees($filtres)
 *   2. Génération du QR Code de vérification
 *   3. Rendu Blade -> HTML
 *   4. Conversion HTML -> PDF via DomPDF
 *   5. Sauvegarde fichier + entrée GeneratedReport (audit)
 */
class PdfGeneratorService
{
    public function generer(Report $report, array $filtres = [], ?int $userId = null): GeneratedReport
    {
        $codeVerification = strtoupper(Str::random(16));
        $donnees = $report->donnees($filtres);

        // Génération du QR Code en base64 pour intégration inline dans le PDF
        $urlVerification = url('/rapports/verifier/' . $codeVerification);
        $qrSvg = base64_encode(
            QrCode::format('svg')->size(120)->margin(0)->generate($urlVerification),
        );

        // Logo institutionnel encodé en base64 pour intégration inline robuste (DomPDF)
        $logoDataUri = $this->logoCeeacDataUri();

        // Métadonnées institutionnelles
        $contexte = [
            'rapport' => [
                'titre' => $report->titre(),
                'categorie' => $report->categorie(),
                'description' => $report->description(),
            ],
            'filtres' => $filtres,
            'genere_le' => now(),
            'genere_par' => $userId ? User::find($userId)?->name : 'Système',
            'code_verification' => $codeVerification,
            'qr_svg_base64' => $qrSvg,
            'url_verification' => $urlVerification,
            'logo_ceeac' => $logoDataUri,
            'institution' => [
                'nom' => 'Commission de la Communauté Économique des États de l\'Afrique Centrale',
                'abreviation' => 'CEEAC',
                'systeme' => 'TB-PAPA-CEEAC',
            ],
            'donnees' => $donnees,
        ];

        // Rendu Blade
        $pdf = Pdf::loadView($report->template(), $contexte)
            ->setPaper($report->format(), $report->orientation())
            ->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                // isRemoteEnabled = true permet à DomPDF de traiter les data URIs inline (logo CEEAC, QR code)
                'isRemoteEnabled' => true,
                'chroot' => public_path(),
                'dpi' => 110,
            ]);

        // Stockage
        $dossier = 'reports/' . now()->format('Y/m');
        $nomFichier = Str::slug($report->titre()) . '-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(6)) . '.pdf';
        $cheminRelatif = $dossier . '/' . $nomFichier;
        Storage::disk('local')->put($cheminRelatif, $pdf->output());

        $cheminComplet = Storage::disk('local')->path($cheminRelatif);
        $hash = hash_file('sha256', $cheminComplet);
        $taille = filesize($cheminComplet);

        // Audit trail en base
        $generated = GeneratedReport::create([
            'report_key' => $report->key(),
            'categorie' => $report->categorie(),
            'titre' => $report->titre(),
            'description' => $report->description(),
            'filtres' => $filtres,
            'chemin_stockage' => $cheminRelatif,
            'nom_fichier' => $nomFichier,
            'taille_octets' => $taille,
            'hash_sha256' => $hash,
            'format' => 'pdf',
            'code_verification' => $codeVerification,
            'signe_numeriquement' => false,
            'genere_par_id' => $userId,
            'genere_at' => now(),
            'statut' => 'pret',
        ]);

        return $generated;
    }

    /**
     * Encode le logo CEEAC en data URI base64 pour intégration inline dans les PDF.
     * Retourne null si le fichier est introuvable (les templates affichent alors un fallback texte).
     */
    protected function logoCeeacDataUri(): ?string
    {
        $candidates = [
            public_path('images/LOGO-CEEAC.jpg'),
            public_path('images/logo-ceeac.jpg'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                $mime = function_exists('mime_content_type') ? mime_content_type($path) : 'image/jpeg';

                return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
            }
        }

        return null;
    }

    public function telecharger(GeneratedReport $report, ?int $userId = null): string
    {
        $cheminComplet = Storage::disk('local')->path($report->chemin_stockage);

        if (! file_exists($cheminComplet)) {
            throw new \RuntimeException("Fichier de rapport introuvable : {$report->nom_fichier}");
        }

        $report->increment('nb_telechargements');
        $report->update([
            'dernier_telechargement_at' => now(),
            'dernier_telechargement_par_id' => $userId,
        ]);

        return $cheminComplet;
    }
}
