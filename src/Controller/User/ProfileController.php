<?php

namespace App\Controller\User;

use App\Entity\Experience;
use App\Entity\PortfolioItem;
use App\Entity\Profile;
use App\Entity\Skill;
use App\Repository\ExperienceRepository;
use App\Repository\PortfolioItemRepository;
use App\Repository\ProfileRepository;
use App\Repository\SkillRepository;
use App\Repository\UserRepository;
use App\Service\User\ProfileAiService;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\AppController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AppController
{
    private bool $skillsTableMissingWarned = false;

    public function __construct(
        private EntityManagerInterface $em,
        private ProfileRepository $profileRepository,
        private SkillRepository $skillRepository,
        private ExperienceRepository $experienceRepository,
        private PortfolioItemRepository $portfolioItemRepository,
        private ProfileAiService $profileAiService,
    ) {}

    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $user = $this->getAppUser();
        $profile = $this->profileRepository->findOneBy(['user' => $user]);

        if (!$profile) {
            $profile = new Profile();
            $profile->setUser($user);
            $this->em->persist($profile);
        }

        // Back-fill first/last name from User.fullName when the profile fields are empty
        if (!$profile->getFirstName() && !$profile->getLastName() && $user->getFullName()) {
            $parts = explode(' ', trim($user->getFullName()), 2);
            $profile->setFirstName($parts[0] ?? null);
            $profile->setLastName($parts[1] ?? null);
            $this->em->flush();
        } else {
            $this->em->flush();
        }

        $skills = $this->safeSkillsForProfile($profile);
        $experiences = $this->experienceRepository->findBy(['profile' => $profile], ['period.startDate' => 'DESC']);
        $portfolioItems = $this->portfolioItemRepository->findBy(['user' => $user], ['createdDate' => 'DESC']);

        return $this->render('user/profile/index.html.twig', [
            'profile' => $profile,
            'skills' => $skills,
            'experiences' => $experiences,
            'portfolioItems' => $portfolioItems,
        ]);
    }

    #[Route('/profile/update', name: 'app_profile_update', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function update(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('profile_update', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getAppUser();
        $profile = $this->profileRepository->findOneBy(['user' => $user]);

        if (!$profile) {
            $profile = new Profile();
            $profile->setUser($user);
            $this->em->persist($profile);
        }

        $profile->setFirstName($request->request->getString('first_name'));
        $profile->setLastName($request->request->getString('last_name'));
        $profile->setPhone($request->request->getString('phone'));
        $profile->setLocation($request->request->getString('location'));
        $profile->setHeadline($request->request->getString('headline'));
        $profile->setBio($request->request->getString('bio'));
        $profile->setWebsite($request->request->getString('website'));

        $birthDate = $request->request->getString('birth_date');
        $profile->setBirthDate($birthDate ? new \DateTime($birthDate) : null);

        $this->em->flush();

        $this->addFlash('success', 'Profile updated successfully');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/photo/upload', name: 'app_profile_photo_upload', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function uploadPhoto(Request $request, SluggerInterface $slugger): JsonResponse
    {
        if (!$this->isCsrfTokenValid('profile_photo', $request->request->getString('_token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], 403);
        }

        $user = $this->getAppUser();
        $profile = $this->profileRepository->findOneBy(['user' => $user]);

        if (!$profile) {
            $profile = new Profile();
            $profile->setUser($user);
            $this->em->persist($profile);
        }

        // Handle base64 cropped image data
        $croppedData = $request->request->getString('cropped_image');
        if (!$croppedData) {
            return new JsonResponse(['error' => 'No image data received.'], 400);
        }

        // Validate and decode base64 data
        if (!preg_match('/^data:image\/(jpeg|png|webp|gif);base64,/', $croppedData, $matches)) {
            return new JsonResponse(['error' => 'Invalid image format.'], 400);
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $imageData = base64_decode((string) preg_replace('/^data:image\/\w+;base64,/', '', $croppedData));

        if ($imageData === false || strlen($imageData) > 5 * 1024 * 1024) {
            return new JsonResponse(['error' => 'Image too large (max 5MB).'], 400);
        }

        // Validate it's actually an image
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);
        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
            return new JsonResponse(['error' => 'Invalid image type.'], 400);
        }

        // Create upload directory
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Delete old avatar if it's a local file
        $oldPhoto = $profile->getPhotoUrl();
        if ($oldPhoto && str_starts_with($oldPhoto, '/uploads/avatars/')) {
            $oldFile = $this->getParameter('kernel.project_dir') . '/public' . $oldPhoto;
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        // Generate unique filename
        $filename = 'avatar-' . $user->getId() . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $filepath = $uploadDir . '/' . $filename;

        file_put_contents($filepath, $imageData);

        // Update profile
        $photoUrl = '/uploads/avatars/' . $filename;
        $profile->setPhotoUrl($photoUrl);
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'photoUrl' => $photoUrl,
            'message' => 'Profile photo updated!',
        ]);
    }

    #[Route('/profile/photo/delete', name: 'app_profile_photo_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deletePhoto(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('profile_photo', $request->request->getString('_token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], 403);
        }

        $user = $this->getAppUser();
        $profile = $this->profileRepository->findOneBy(['user' => $user]);

        if ($profile && $profile->getPhotoUrl()) {
            $oldPhoto = $profile->getPhotoUrl();
            if (str_starts_with($oldPhoto, '/uploads/avatars/')) {
                $oldFile = $this->getParameter('kernel.project_dir') . '/public' . $oldPhoto;
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            $profile->setPhotoUrl(null);
            $this->em->flush();
        }

        return new JsonResponse(['success' => true, 'message' => 'Photo removed.']);
    }

    #[Route('/profile/skills/add', name: 'app_profile_skill_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addSkill(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('profile_skill', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getAppUser();
        $profile = $this->profileRepository->findOneBy(['user' => $user]);

        if (!$profile) {
            throw $this->createNotFoundException('Profile not found');
        }

        $skill = new Skill();
        $skill->setProfile($profile);
        $skill->setSkillName($request->request->getString('skill_name'));
        $skill->setProficiencyLevel($request->request->getString('proficiency_level'));
        $skill->setYearsExperience((int) $request->request->get('years_experience'));

        try {
            $this->em->persist($skill);
            $this->em->flush();
        } catch (TableNotFoundException $e) {
            $this->addFlash('warning', 'La table des competences est absente en base. Ajoutez-la pour enregistrer des skills.');

            return $this->redirectToRoute('app_profile');
        }

        $this->addFlash('success', 'Skill added');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/skills/{id}/delete', name: 'app_profile_skill_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteSkill(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('profile_skill', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getAppUser();
        $profile = $this->profileRepository->findOneBy(['user' => $user]);
        try {
            $skill = $this->skillRepository->find($id);
        } catch (TableNotFoundException $e) {
            $this->addFlash('warning', 'La table des competences est absente en base.');

            return $this->redirectToRoute('app_profile');
        }

        $skillProfile = $skill?->getProfile();
        if (!$skill || !$profile || !$skillProfile || $skillProfile->getId() !== $profile->getId()) {
            throw $this->createAccessDeniedException();
        }

        $this->em->remove($skill);
        $this->em->flush();

        $this->addFlash('success', 'Skill removed');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/experience/add', name: 'app_profile_experience_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addExperience(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('profile_experience', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getAppUser();
        $profile = $this->profileRepository->findOneBy(['user' => $user]);

        if (!$profile) {
            throw $this->createNotFoundException('Profile not found');
        }

        $experience = new Experience();
        $experience->setProfile($profile);
        $experience->setCompany($request->request->getString('company'));
        $experience->setPosition($request->request->getString('position'));
        $experience->setDescription($request->request->getString('description'));
        $experience->setCurrentJob($request->request->has('current_job'));

        $startDate = $request->request->getString('start_date');
        $experience->setStartDate($startDate ? new \DateTimeImmutable($startDate) : null);

        $endDate = $request->request->getString('end_date');
        $experience->setEndDate($endDate ? new \DateTimeImmutable($endDate) : null);

        $this->em->persist($experience);
        $this->em->flush();

        $this->addFlash('success', 'Experience added');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/experience/{id}/delete', name: 'app_profile_experience_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteExperience(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('profile_experience', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getAppUser();
        $profile = $this->profileRepository->findOneBy(['user' => $user]);
        $experience = $this->experienceRepository->find($id);

        $experienceProfile = $experience?->getProfile();
        if (!$experience || !$profile || !$experienceProfile || $experienceProfile->getId() !== $profile->getId()) {
            throw $this->createAccessDeniedException();
        }

        $this->em->remove($experience);
        $this->em->flush();

        $this->addFlash('success', 'Experience removed');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/portfolio/add', name: 'app_profile_portfolio_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addPortfolio(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('profile_portfolio', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getAppUser();

        $item = new PortfolioItem();
        $item->setUser($user);
        $item->setTitle($request->request->getString('title'));
        $item->setDescription($request->request->getString('description'));
        $item->setProjectUrl($request->request->getString('project_url'));
        $item->setImageUrl($request->request->getString('image_url'));
        $item->setTechnologies($request->request->getString('technologies'));

        $this->em->persist($item);
        $this->em->flush();

        $this->addFlash('success', 'Portfolio item added');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/portfolio/{id}/delete', name: 'app_profile_portfolio_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deletePortfolio(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('profile_portfolio', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getAppUser();
        $item = $this->portfolioItemRepository->find($id);

        $itemUser = $item?->getUser();
        if (!$item || !$itemUser || $itemUser->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $this->em->remove($item);
        $this->em->flush();

        $this->addFlash('success', 'Portfolio item removed');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/{username}', name: 'app_profile_public')]
    public function publicProfile(
        string $username,
        UserRepository $userRepository,
    ): Response {
        $user = $userRepository->findOneBy(['username' => $username]);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $profile = $this->profileRepository->findOneBy(['user' => $user]);
        $skills = $profile ? $this->safeSkillsForProfile($profile) : [];
        $experiences = $profile ? $this->experienceRepository->findBy(['profile' => $profile], ['period.startDate' => 'DESC']) : [];
        $portfolioItems = $this->portfolioItemRepository->findBy(['user' => $user], ['createdDate' => 'DESC']);

        return $this->render('user/profile/public.html.twig', [
            'profileUser' => $user,
            'profile' => $profile,
            'skills' => $skills,
            'experiences' => $experiences,
            'portfolioItems' => $portfolioItems,
        ]);
    }

    // ─── AI Profile Analysis ────────────────────────────────────────────────

    /**
     * SSE stream: debounce-triggered by frontend, streams GPT analysis tokens.
     */
    #[Route('/profile/ai/analyze', name: 'app_profile_ai_analyze', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function aiAnalyzeStream(Request $request): StreamedResponse
    {
        if (!$this->isCsrfTokenValid('profile_ai_analyze', $request->request->getString('_token'))) {
            return new StreamedResponse(function () {
                echo "data: " . json_encode(['error' => 'Invalid CSRF token.']) . "\n\n";
                echo "data: [DONE]\n\n";
                ob_flush(); flush();
            }, 403, ['Content-Type' => 'text/event-stream']);
        }

        $profileData = $this->buildProfileDataForCurrentUser();

        $response = new StreamedResponse(function () use ($profileData) {
            foreach ($this->profileAiService->streamAnalysis($profileData) as $chunk) {
                echo $chunk;
                ob_flush();
                flush();
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    /**
     * Returns a JSON snapshot of the current user's profile for the frontend
     * (used to display "what was analyzed" alongside the AI result).
     */
    #[Route('/profile/ai/snapshot', name: 'app_profile_ai_snapshot', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function aiSnapshot(): JsonResponse
    {
        return new JsonResponse($this->buildProfileDataForCurrentUser());
    }

    /**
     * @return array{
     *     name: string,
     *     headline: string,
     *     bio: string,
     *     location: string,
     *     website: string,
     *     skills: list<array{name: string, level: string, years: int}>,
     *     experiences: list<array{company: string, position: string, description: string, current: bool}>,
     *     portfolio: list<array{title: string, description: string, technologies: string}>
     * }
     */
    private function buildProfileDataForCurrentUser(): array
    {
        $user    = $this->getAppUser();
        $profile = $this->profileRepository->findOneBy(['user' => $user]);
        $skills  = $profile ? $this->safeSkillsForProfile($profile) : [];
        $exps    = $profile ? $this->experienceRepository->findBy(['profile' => $profile], ['period.startDate' => 'DESC']) : [];
        $items   = $this->portfolioItemRepository->findBy(['user' => $user], ['createdDate' => 'DESC']);

        return [
            'name'        => $user->getDisplayName(),
            'headline'    => $profile?->getHeadline() ?? '',
            'bio'         => $profile?->getBio() ?? '',
            'location'    => $profile?->getLocation() ?? '',
            'website'     => $profile?->getWebsite() ?? '',
            'skills'      => array_map(fn(Skill $s) => [
                'name'  => $s->getSkillName() ?? '',
                'level' => $s->getProficiencyLevel() ?? 'Intermediate',
                'years' => $s->getYearsExperience() ?? 0,
            ], $skills),
            'experiences' => array_values(array_map(fn(Experience $e) => [
                'company'     => $e->getCompany() ?? '',
                'position'    => $e->getPosition() ?? '',
                'description' => $e->getDescription() ?? '',
                'current'     => (bool) $e->isCurrentJob(),
            ], $exps)),
            'portfolio'   => array_values(array_map(fn(PortfolioItem $p) => [
                'title'        => $p->getTitle() ?? '',
                'description'  => $p->getDescription() ?? '',
                'technologies' => $p->getTechnologies() ?? '',
            ], $items)),
        ];
    }

    // ───────────────────────────────────────────────────────────────────────

    /**
     * @return list<Skill>
     */
    private function safeSkillsForProfile(Profile $profile): array
    {
        try {
            return array_values($this->skillRepository->findBy(['profile' => $profile]));
        } catch (TableNotFoundException $e) {
            if (!$this->skillsTableMissingWarned) {
                $this->skillsTableMissingWarned = true;
                $this->addFlash('warning', 'La table des competences (skills) est absente. Le profil reste accessible sans cette section.');
            }

            return [];
        }
    }
}
