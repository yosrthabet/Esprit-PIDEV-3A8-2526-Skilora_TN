<?php

namespace App\Controller;

use App\Entity\Experience;
use App\Entity\PortfolioItem;
use App\Entity\Profile;
use App\Entity\Skill;
use App\Repository\ExperienceRepository;
use App\Repository\PortfolioItemRepository;
use App\Repository\ProfileRepository;
use App\Repository\SkillRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    private bool $skillsTableMissingWarned = false;

    public function __construct(
        private EntityManagerInterface $em,
        private ProfileRepository $profileRepository,
        private SkillRepository $skillRepository,
        private ExperienceRepository $experienceRepository,
        private PortfolioItemRepository $portfolioItemRepository,
    ) {}

    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $user = $this->getUser();
        $profile = $this->profileRepository->findOneBy(['user' => $user]);

        if (!$profile) {
            $profile = new Profile();
            $profile->setUser($user);
            $this->em->persist($profile);
            $this->em->flush();
        }

        $skills = $this->safeSkillsForProfile($profile);
        $experiences = $this->experienceRepository->findBy(['profile' => $profile], ['startDate' => 'DESC']);
        $portfolioItems = $this->portfolioItemRepository->findBy(['user' => $user], ['createdDate' => 'DESC']);

        return $this->render('profile/index.html.twig', [
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
        if (!$this->isCsrfTokenValid('profile_update', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getUser();
        $profile = $this->profileRepository->findOneBy(['user' => $user]);

        if (!$profile) {
            $profile = new Profile();
            $profile->setUser($user);
            $this->em->persist($profile);
        }

        $profile->setFirstName($request->request->get('first_name'));
        $profile->setLastName($request->request->get('last_name'));
        $profile->setPhone($request->request->get('phone'));
        $profile->setLocation($request->request->get('location'));
        $profile->setHeadline($request->request->get('headline'));
        $profile->setBio($request->request->get('bio'));
        $profile->setWebsite($request->request->get('website'));

        $birthDate = $request->request->get('birth_date');
        $profile->setBirthDate($birthDate ? new \DateTime($birthDate) : null);

        $this->em->flush();

        $this->addFlash('success', 'Profile updated successfully');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/skills/add', name: 'app_profile_skill_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addSkill(Request $request): Response
    {
        $user = $this->getUser();
        $profile = $this->profileRepository->findOneBy(['user' => $user]);

        if (!$profile) {
            throw $this->createNotFoundException('Profile not found');
        }

        $skill = new Skill();
        $skill->setProfile($profile);
        $skill->setSkillName($request->request->get('skill_name'));
        $skill->setProficiencyLevel($request->request->get('proficiency_level'));
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
    public function deleteSkill(int $id): Response
    {
        $user = $this->getUser();
        $profile = $this->profileRepository->findOneBy(['user' => $user]);
        try {
            $skill = $this->skillRepository->find($id);
        } catch (TableNotFoundException $e) {
            $this->addFlash('warning', 'La table des competences est absente en base.');

            return $this->redirectToRoute('app_profile');
        }

        if (!$skill || !$profile || $skill->getProfile()->getId() !== $profile->getId()) {
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
        $user = $this->getUser();
        $profile = $this->profileRepository->findOneBy(['user' => $user]);

        if (!$profile) {
            throw $this->createNotFoundException('Profile not found');
        }

        $experience = new Experience();
        $experience->setProfile($profile);
        $experience->setCompany($request->request->get('company'));
        $experience->setPosition($request->request->get('position'));
        $experience->setDescription($request->request->get('description'));
        $experience->setCurrentJob($request->request->has('current_job'));

        $startDate = $request->request->get('start_date');
        $experience->setStartDate($startDate ? new \DateTime($startDate) : null);

        $endDate = $request->request->get('end_date');
        $experience->setEndDate($endDate ? new \DateTime($endDate) : null);

        $this->em->persist($experience);
        $this->em->flush();

        $this->addFlash('success', 'Experience added');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/experience/{id}/delete', name: 'app_profile_experience_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteExperience(int $id): Response
    {
        $user = $this->getUser();
        $profile = $this->profileRepository->findOneBy(['user' => $user]);
        $experience = $this->experienceRepository->find($id);

        if (!$experience || !$profile || $experience->getProfile()->getId() !== $profile->getId()) {
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
        $user = $this->getUser();

        $item = new PortfolioItem();
        $item->setUser($user);
        $item->setTitle($request->request->get('title'));
        $item->setDescription($request->request->get('description'));
        $item->setProjectUrl($request->request->get('project_url'));
        $item->setImageUrl($request->request->get('image_url'));
        $item->setTechnologies($request->request->get('technologies'));
        $item->setCreatedDate(new \DateTime());

        $this->em->persist($item);
        $this->em->flush();

        $this->addFlash('success', 'Portfolio item added');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/portfolio/{id}/delete', name: 'app_profile_portfolio_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deletePortfolio(int $id): Response
    {
        $user = $this->getUser();
        $item = $this->portfolioItemRepository->find($id);

        if (!$item || $item->getUser()->getId() !== $user->getId()) {
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
        $experiences = $profile ? $this->experienceRepository->findBy(['profile' => $profile], ['startDate' => 'DESC']) : [];
        $portfolioItems = $this->portfolioItemRepository->findBy(['user' => $user], ['createdDate' => 'DESC']);

        return $this->render('profile/public.html.twig', [
            'profileUser' => $user,
            'profile' => $profile,
            'skills' => $skills,
            'experiences' => $experiences,
            'portfolioItems' => $portfolioItems,
        ]);
    }

    /**
     * @return list<Skill>
     */
    private function safeSkillsForProfile(Profile $profile): array
    {
        try {
            return $this->skillRepository->findBy(['profile' => $profile]);
        } catch (TableNotFoundException $e) {
            if (!$this->skillsTableMissingWarned) {
                $this->skillsTableMissingWarned = true;
                $this->addFlash('warning', 'La table des competences (skills) est absente. Le profil reste accessible sans cette section.');
            }

            return [];
        }
    }
}
