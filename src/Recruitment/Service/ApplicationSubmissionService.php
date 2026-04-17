<?php

namespace App\Recruitment\Service;

use App\Entity\User;
use App\Recruitment\ApplicationStatus;
use App\Recruitment\Entity\JobOffer;
use App\Recruitment\Repository\ApplicationsTableGateway;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class ApplicationSubmissionService
{
    public const MAX_CV_BYTES = 5 * 1024 * 1024;

    public function __construct(
        private readonly ApplicationsTableGateway $applicationsTableGateway,
        private readonly CandidateProfileIdResolver $candidateProfileIdResolver,
        private readonly CvJobMatchScorer $cvJobMatchScorer,
        private readonly CvDocumentTextExtractor $cvDocumentTextExtractor,
        private readonly ApplicationQualityScreeningService $applicationQualityScreeningService,
        private readonly string $cvUploadDir,
    ) {
    }

    public function submit(User $user, JobOffer $jobOffer, UploadedFile $cv, ?string $coverLetter): void
    {
        $relativePath = $this->storeCvFile($cv);
        $this->submitWithStoredRelativeCvPath($user, $jobOffer, $relativePath, $coverLetter);
    }

    public function submitUsingExistingCvPath(User $user, JobOffer $jobOffer, string $relativeCvPath, ?string $coverLetter): void
    {
        $relativeCvPath = trim($relativeCvPath);
        if ($relativeCvPath === '') {
            throw new BadRequestHttpException('Aucun CV enregistré n’est disponible.');
        }

        $absoluteCv = rtrim($this->cvUploadDir, '/\\')
            .\DIRECTORY_SEPARATOR
            .str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, ltrim($relativeCvPath, '/\\'));
        if (!is_file($absoluteCv) || !is_readable($absoluteCv)) {
            throw new BadRequestHttpException('Le CV enregistré est introuvable. Générez-en un nouveau.');
        }

        $this->submitWithStoredRelativeCvPath($user, $jobOffer, $relativeCvPath, $coverLetter);
    }

    private function submitWithStoredRelativeCvPath(User $user, JobOffer $jobOffer, string $relativePath, ?string $coverLetter): void
    {
        if (\in_array('ROLE_EMPLOYER', $user->getRoles(), true)) {
            throw new AccessDeniedHttpException('Les employeurs ne peuvent pas postuler depuis l’espace candidat.');
        }

        $st = strtoupper(trim($jobOffer->getStatus()));
        if ($st !== 'OPEN') {
            throw new BadRequestHttpException('Cette offre n’accepte plus de candidatures.');
        }

        $uid = $user->getId();
        $jid = $jobOffer->getId();
        if ($uid === null || $jid === null) {
            throw new BadRequestHttpException('Requête invalide.');
        }

        if ($this->applicationsTableGateway->existsForJobOfferAndCandidate($jid, $uid)) {
            throw new BadRequestHttpException('Vous avez déjà postulé à cette offre.');
        }

        $profileId = $this->candidateProfileIdResolver->findProfileIdForUserId($uid)
            ?? $this->candidateProfileIdResolver->ensureProfileRowForUserId($uid);
        if ($profileId === null) {
            throw new BadRequestHttpException(
                'Profil candidat introuvable (table `profiles` absente ou illisible). Vérifiez la base de données.',
            );
        }

        // Compte candidat = users.id lié au profil (source de vérité pour l’affichage employeur : JOIN users sur candidate_id)
        $candidateAccountUserId = $this->candidateProfileIdResolver->findUserIdForProfileId($profileId);
        if ($candidateAccountUserId === null || $candidateAccountUserId !== $uid) {
            throw new BadRequestHttpException(
                'Le profil candidat ne correspond pas à votre compte utilisateur. Vérifiez la table `profiles` (user_id).',
            );
        }

        $letter = ($coverLetter !== null && $coverLetter !== '') ? $coverLetter : null;

        $absoluteCv = rtrim($this->cvUploadDir, '/\\')
            .\DIRECTORY_SEPARATOR
            .str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, ltrim($relativePath, '/\\'));
        $cvText = $this->cvDocumentTextExtractor->extractFromAbsolutePath($absoluteCv);
        $quality = $this->applicationQualityScreeningService->analyze($letter, $cvText);
        if ($quality->blocked) {
            $why = $quality->reasons !== [] ? ' '.implode(' ', $quality->reasons) : '';
            throw new BadRequestHttpException(
                'Votre candidature semble de mauvaise qualité ou suspecte et a été bloquée. '
                .'Merci de soumettre un contenu plus détaillé et authentique.'.$why,
            );
        }

        $matchPct = $this->cvJobMatchScorer->computeMatchPercentage($absoluteCv, $jobOffer);
        if ($matchPct === null || $matchPct <= 0.0) {
            throw new BadRequestHttpException(
                'Candidature non enregistrée : votre CV ne correspond pas encore à cette offre (match score = 0%). '
                .'Ajoutez des compétences/mots-clés liés à l’offre puis réessayez.',
            );
        }

        $this->applicationsTableGateway->insertApplication(
            $jid,
            $profileId,
            $candidateAccountUserId,
            ApplicationStatus::IN_PROGRESS,
            $relativePath,
            $letter,
            $matchPct,
        );
    }

    private function storeCvFile(UploadedFile $cv): string
    {
        if (!$cv->isValid()) {
            throw new BadRequestHttpException('Le fichier CV est invalide.');
        }

        if ($cv->getSize() > self::MAX_CV_BYTES) {
            throw new BadRequestHttpException('Le CV ne doit pas dépasser 5 Mo.');
        }

        $subDir = (new \DateTimeImmutable())->format('Y/m');
        $targetDir = $this->cvUploadDir.'/'.$subDir;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Impossible de créer le répertoire d’upload.');
        }

        $slugger = new AsciiSlugger();
        $safeBase = (string) $slugger->slug(pathinfo($cv->getClientOriginalName(), PATHINFO_FILENAME) ?: 'cv');
        $ext = strtolower($cv->guessExtension() ?: $cv->getClientOriginalExtension() ?: 'bin');
        $filename = $safeBase.'_'.bin2hex(random_bytes(6)).'.'.$ext;

        $cv->move($targetDir, $filename);

        return $subDir.'/'.$filename;
    }
}
