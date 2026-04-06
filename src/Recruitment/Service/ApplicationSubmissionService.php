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
        private readonly string $cvUploadDir,
    ) {
    }

    public function submit(User $user, JobOffer $jobOffer, UploadedFile $cv, ?string $coverLetter): void
    {
        if (\in_array('ROLE_EMPLOYER', $user->getRoles(), true)) {
            throw new AccessDeniedHttpException('Les employeurs ne peuvent pas postuler depuis l’espace candidat.');
        }

        if ($jobOffer->getStatus() !== 'OPEN') {
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

        $relativePath = $this->storeCvFile($cv);

        $profileId = $this->candidateProfileIdResolver->findProfileIdForUserId($uid);
        if ($profileId === null) {
            throw new BadRequestHttpException(
                'Profil candidat introuvable (table `profiles`). Complétez votre profil avant de postuler.',
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

        $this->applicationsTableGateway->insertApplication(
            $jid,
            $profileId,
            $candidateAccountUserId,
            ApplicationStatus::IN_PROGRESS,
            $relativePath,
            $letter,
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
