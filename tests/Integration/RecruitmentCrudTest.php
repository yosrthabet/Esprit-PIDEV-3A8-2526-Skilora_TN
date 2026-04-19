<?php

namespace App\Tests\Integration;

use App\Entity\User;
use App\Entity\Profile;
use App\Recruitment\Entity\Application;
use App\Recruitment\Entity\Company;
use App\Recruitment\Entity\JobInterview;
use App\Recruitment\Entity\JobOffer;

class RecruitmentCrudTest extends DatabaseTestCase
{
    private function createCompany(string $name = 'RecruitCorp'): Company
    {
        $user = $this->createTestUser();
        $c = new Company();
        $c->setName($name);
        $c->setOwner($user);
        $c->setCountry('Tunisia');
        $c->setIndustry('IT');
        $c->setSize('50-200');
        $c->setWebsite('https://example.tn');
        $this->em->persist($c);
        $this->em->flush();

        return $c;
    }

    private function createJobOffer(Company $company): JobOffer
    {
        $jo = new JobOffer();
        $jo->setTitle('PHP Developer');
        $jo->setCompany($company);
        $jo->setLocation('Tunis');
        $jo->setWorkType('CDI');
        $jo->setMinSalary('2000');
        $jo->setMaxSalary('4000');
        $jo->setDescription('We need a dev');
        $jo->setRequirements('PHP, Symfony');
        $jo->setStatus('active');
        $jo->setPostedDate(new \DateTimeImmutable());
        $jo->setCurrency('TND');
        $jo->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($jo);
        $this->em->flush();

        return $jo;
    }

    private function createProfile(User $user): Profile
    {
        $p = new Profile();
        $p->setUser($user);
        $p->setFirstName('Test');
        $p->setLastName('Candidate');
        $this->em->persist($p);
        $this->em->flush();

        return $p;
    }

    // --- Company ---

    public function testCreateCompany(): void
    {
        $c = $this->createCompany();
        $this->assertNotNull($c->getId());
    }

    public function testReadCompany(): void
    {
        $c = $this->createCompany('ReadCo');
        $id = $c->getId();
        $this->em->clear();

        $found = $this->em->find(Company::class, $id);
        $this->assertNotNull($found);
        $this->assertSame('ReadCo', $found->getName());
    }

    public function testUpdateCompany(): void
    {
        $c = $this->createCompany();
        $c->setIsVerified(true);
        $this->em->flush();
        $id = $c->getId();
        $this->em->clear();

        $found = $this->em->find(Company::class, $id);
        $this->assertTrue($found->isVerified());
    }

    public function testDeleteCompany(): void
    {
        $c = $this->createCompany();
        $id = $c->getId();
        $this->em->remove($c);
        $this->em->flush();
        $this->em->clear();

        $this->assertNull($this->em->find(Company::class, $id));
    }

    // --- JobOffer ---

    public function testCreateJobOffer(): void
    {
        $c = $this->createCompany();
        $jo = $this->createJobOffer($c);
        $this->assertNotNull($jo->getId());
    }

    public function testReadJobOffer(): void
    {
        $c = $this->createCompany();
        $jo = $this->createJobOffer($c);
        $id = $jo->getId();
        $this->em->clear();

        $found = $this->em->find(JobOffer::class, $id);
        $this->assertNotNull($found);
        $this->assertSame('PHP Developer', $found->getTitle());
    }

    public function testUpdateJobOffer(): void
    {
        $c = $this->createCompany();
        $jo = $this->createJobOffer($c);
        $jo->setTitle('Senior PHP Developer');
        $this->em->flush();
        $id = $jo->getId();
        $this->em->clear();

        $found = $this->em->find(JobOffer::class, $id);
        $this->assertSame('Senior PHP Developer', $found->getTitle());
    }

    public function testDeleteJobOffer(): void
    {
        $c = $this->createCompany();
        $jo = $this->createJobOffer($c);
        $id = $jo->getId();
        $this->em->remove($jo);
        $this->em->flush();
        $this->em->clear();

        $this->assertNull($this->em->find(JobOffer::class, $id));
    }

    // --- Application ---

    public function testCreateApplication(): void
    {
        $c = $this->createCompany();
        $jo = $this->createJobOffer($c);
        $user = $this->createTestUser();
        $profile = $this->createProfile($user);

        $app = new Application();
        $app->setJobOffer($jo);
        $app->setCandidate($user);
        $app->setCandidateProfileId($profile->getId());
        $app->setCvPath('/uploads/cv/test.pdf');
        $app->setStatus('pending');
        $this->em->persist($app);
        $this->em->flush();

        $this->assertNotNull($app->getId());
    }

    public function testReadApplication(): void
    {
        $c = $this->createCompany();
        $jo = $this->createJobOffer($c);
        $user = $this->createTestUser();
        $profile = $this->createProfile($user);

        $app = new Application();
        $app->setJobOffer($jo);
        $app->setCandidate($user);
        $app->setCandidateProfileId($profile->getId());
        $app->setCvPath('/uploads/cv/read.pdf');
        $app->setStatus('pending');
        $this->em->persist($app);
        $this->em->flush();
        $id = $app->getId();
        $this->em->clear();

        $found = $this->em->find(Application::class, $id);
        $this->assertNotNull($found);
        $this->assertSame('pending', $found->getStatus());
    }

    public function testUpdateApplication(): void
    {
        $c = $this->createCompany();
        $jo = $this->createJobOffer($c);
        $user = $this->createTestUser();
        $profile = $this->createProfile($user);

        $app = new Application();
        $app->setJobOffer($jo);
        $app->setCandidate($user);
        $app->setCandidateProfileId($profile->getId());
        $app->setCvPath('/uploads/cv/u.pdf');
        $app->setStatus('pending');
        $this->em->persist($app);
        $this->em->flush();

        $app->setStatus('accepted');
        $this->em->flush();
        $id = $app->getId();
        $this->em->clear();

        $found = $this->em->find(Application::class, $id);
        $this->assertSame('accepted', $found->getStatus());
    }

    public function testDeleteApplication(): void
    {
        $c = $this->createCompany();
        $jo = $this->createJobOffer($c);
        $user = $this->createTestUser();
        $profile = $this->createProfile($user);

        $app = new Application();
        $app->setJobOffer($jo);
        $app->setCandidate($user);
        $app->setCandidateProfileId($profile->getId());
        $app->setCvPath('/uploads/cv/d.pdf');
        $app->setStatus('pending');
        $this->em->persist($app);
        $this->em->flush();
        $id = $app->getId();

        $this->em->remove($app);
        $this->em->flush();
        $this->em->clear();

        $this->assertNull($this->em->find(Application::class, $id));
    }

    // --- JobInterview ---

    public function testCreateJobInterview(): void
    {
        $c = $this->createCompany();
        $jo = $this->createJobOffer($c);
        $user = $this->createTestUser();
        $profile = $this->createProfile($user);

        $app = new Application();
        $app->setJobOffer($jo);
        $app->setCandidate($user);
        $app->setCandidateProfileId($profile->getId());
        $app->setCvPath('/uploads/cv/int.pdf');
        $app->setStatus('pending');
        $this->em->persist($app);
        $this->em->flush();

        $iv = new JobInterview();
        $iv->setApplication($app);
        $iv->setScheduledAt(new \DateTimeImmutable('+7 days'));
        $iv->setFormat('ONLINE');
        $iv->setLifecycleStatus('SCHEDULED');
        $iv->setVideoLink('https://meet.example.com/abc');
        $this->em->persist($iv);
        $this->em->flush();

        $this->assertNotNull($iv->getId());
    }

    public function testReadJobInterview(): void
    {
        $c = $this->createCompany();
        $jo = $this->createJobOffer($c);
        $user = $this->createTestUser();
        $profile = $this->createProfile($user);

        $app = new Application();
        $app->setJobOffer($jo);
        $app->setCandidate($user);
        $app->setCandidateProfileId($profile->getId());
        $app->setCvPath('/uploads/cv/intread.pdf');
        $app->setStatus('pending');
        $this->em->persist($app);
        $this->em->flush();

        $iv = new JobInterview();
        $iv->setApplication($app);
        $iv->setScheduledAt(new \DateTimeImmutable('+7 days'));
        $iv->setFormat('ONSITE');
        $iv->setLifecycleStatus('SCHEDULED');
        $iv->setLocation('Esprit Campus');
        $this->em->persist($iv);
        $this->em->flush();
        $id = $iv->getId();
        $this->em->clear();

        $found = $this->em->find(JobInterview::class, $id);
        $this->assertNotNull($found);
        $this->assertSame('ONSITE', $found->getFormat());
    }
}
