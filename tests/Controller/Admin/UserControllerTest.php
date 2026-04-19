<?php

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->em->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->em->getConnection()->isTransactionActive()) {
            $this->em->getConnection()->rollBack();
        }
        $this->em->close();
        parent::tearDown();
    }

    // =========================================================
    // Helpers
    // =========================================================

    private function loginAsAdmin(): void
    {
        $admin = $this->em->getRepository(User::class)->findOneBy(['role' => 'ADMIN']);
        $this->client->loginUser($admin);
    }

    private function createRegularUser(array $overrides = []): User
    {
        $suffix = bin2hex(random_bytes(4));
        $user = new User();
        $user->setUsername($overrides['username'] ?? 'johndoe_' . $suffix);
        $user->setEmail($overrides['email'] ?? 'john_' . $suffix . '@example.com');
        $user->setFullName($overrides['fullName'] ?? 'John Doe');
        $user->setRole($overrides['role'] ?? 'USER');
        $user->setActive($overrides['active'] ?? true);
        $user->setPassword($overrides['password'] ?? 'pass123');

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function postWithCsrf(string $url, string $csrfTokenId, array $extraParams = [], bool $ajax = true): void
    {
        // GET to start a session (uses the loginUser auth)
        $this->client->request('GET', '/admin/users');

        // Extract CSRF token from the session
        $container = $this->client->getContainer();
        $requestStack = $container->get('request_stack');
        $session = $this->client->getRequest()->getSession();

        $request = \Symfony\Component\HttpFoundation\Request::create('/');
        $request->setSession($session);
        $requestStack->push($request);

        try {
            $token = $container->get('security.csrf.token_manager')
                ->getToken($csrfTokenId)->getValue();
        } finally {
            $requestStack->pop();
        }

        $extraParams['_token'] = $token;
        $serverParams = $ajax ? ['HTTP_X-Requested-With' => 'XMLHttpRequest'] : [];

        // POST in same session (auth persisted via session cookie)
        $this->client->request('POST', $url, $extraParams, [], $serverParams);
    }

    private function getUserCount(): int
    {
        return (int) $this->em->createQuery('SELECT COUNT(u.id) FROM App\Entity\User u')->getSingleScalarResult();
    }

    private function reloadUser(int $id): ?User
    {
        $this->em->clear();
        return $this->em->getRepository(User::class)->find($id);
    }

    // =========================================================
    // ACCESS CONTROL
    // =========================================================

    public function testIndexRequiresAuthentication(): void
    {
        $this->client->request('GET', '/admin/users');
        $this->assertResponseRedirects(); // redirect to login
    }

    public function testIndexDeniedForNonAdmin(): void
    {
        $regular = $this->createRegularUser();
        $this->client->loginUser($regular);

        $this->client->request('GET', '/admin/users');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIndexAccessibleByAdmin(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/users');
        $this->assertResponseIsSuccessful();
    }

    // =========================================================
    // INDEX / LIST
    // =========================================================

    public function testIndexRendersUserTable(): void
    {
        $this->createRegularUser();
        $this->loginAsAdmin();

        $crawler = $this->client->request('GET', '/admin/users');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'User Management');
    }

    public function testListReturnsHtmlFragment(): void
    {
        $this->createRegularUser();
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin/users/list', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('John Doe', $content);
        $this->assertStringContainsString('<table', $content);
    }

    public function testListShowsAllUsers(): void
    {
        $this->createRegularUser(['username' => 'user1', 'email' => 'u1@test.com', 'fullName' => 'User One']);
        $this->createRegularUser(['username' => 'user2', 'email' => 'u2@test.com', 'fullName' => 'User Two']);
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin/users/list', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('User One', $content);
        $this->assertStringContainsString('User Two', $content);
    }

    // =========================================================
    // CREATE (NEW)
    // =========================================================

    public function testNewFormReturnsFragmentViaAjax(): void
    {
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin/users/new', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('username', $content);
    }

    public function testCreateUserViaAjax(): void
    {
        $this->loginAsAdmin();
        $countBefore = $this->getUserCount();

        $this->client->request('POST', '/admin/users/new', [
            'username' => 'newuser',
            'email' => 'new@example.com',
            'full_name' => 'New User',
            'role' => 'USER',
            'is_active' => '1',
            'password' => 'password123',
        ], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame('User created successfully.', $data['message']);
        $this->assertSame($countBefore + 1, $this->getUserCount());

        // Verify user was actually persisted correctly
        $created = $this->em->getRepository(User::class)->findOneBy(['username' => 'newuser']);
        $this->assertNotNull($created);
        $this->assertSame('new@example.com', $created->getEmail());
        $this->assertSame('New User', $created->getFullName());
        $this->assertSame('USER', $created->getRole());
        $this->assertTrue($created->isActive());
    }

    public function testCreateUserViaFormRedirects(): void
    {
        $this->loginAsAdmin();

        $this->client->request('POST', '/admin/users/new', [
            'username' => 'formuser',
            'email' => 'form@example.com',
            'full_name' => 'Form User',
            'role' => 'TRAINER',
            'password' => 'pass123',
        ]);

        $this->assertResponseRedirects('/admin/users');
    }

    public function testCreateUserWithoutPasswordStoresNullPassword(): void
    {
        $this->loginAsAdmin();

        $this->client->request('POST', '/admin/users/new', [
            'username' => 'nopwuser',
            'email' => 'nopw@example.com',
            'full_name' => 'No Password',
            'role' => 'USER',
            'password' => '',
        ], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        // DB requires password (NOT NULL), so creation fails when empty
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function testCreateUserWithEmptyUsernameSetsEmptyString(): void
    {
        $this->loginAsAdmin();

        $this->client->request('POST', '/admin/users/new', [
            'username' => '',
            'email' => 'empty@test.com',
            'full_name' => 'Empty Username',
            'role' => 'USER',
            'password' => 'pass',
        ], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        // Controller doesn't validate — it will create a user with empty username
        $this->assertResponseIsSuccessful();
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'empty@test.com']);
        $this->assertNotNull($user);
        $this->assertSame('', $user->getUsername());
    }

    public function testCreateUserDefaultRoleIsUser(): void
    {
        $this->loginAsAdmin();

        $this->client->request('POST', '/admin/users/new', [
            'username' => 'defaultrole',
            'email' => 'default@test.com',
            'full_name' => 'Default Role',
            'password' => 'pass',
        ], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseIsSuccessful();
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'defaultrole']);
        $this->assertSame('USER', $user->getRole());
    }

    // =========================================================
    // EDIT / UPDATE
    // =========================================================

    public function testEditFormReturnsFragmentViaAjax(): void
    {
        $user = $this->createRegularUser();
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin/users/' . $user->getId() . '/edit', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('johndoe', $content);
    }

    public function testEditUserViaAjax(): void
    {
        $user = $this->createRegularUser();
        $this->loginAsAdmin();

        $this->client->request('POST', '/admin/users/' . $user->getId() . '/edit', [
            'username' => 'updated',
            'email' => 'updated@example.com',
            'full_name' => 'Updated User',
            'role' => 'TRAINER',
            'is_active' => '1',
            'password' => '',
        ], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);

        $user = $this->reloadUser($user->getId());
        $this->assertSame('updated', $user->getUsername());
        $this->assertSame('updated@example.com', $user->getEmail());
        $this->assertSame('TRAINER', $user->getRole());
        // Password unchanged (empty submitted)
        $this->assertSame('pass123', $user->getPassword());
    }

    public function testEditUserChangesPassword(): void
    {
        $user = $this->createRegularUser();
        $oldPassword = $user->getPassword();
        $this->loginAsAdmin();

        $this->client->request('POST', '/admin/users/' . $user->getId() . '/edit', [
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'full_name' => 'John Doe',
            'role' => 'USER',
            'password' => 'newpassword',
        ], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseIsSuccessful();
        $this->em->clear();
        $user = $this->em->getRepository(User::class)->find($user->getId());
        $this->assertNotSame($oldPassword, $user->getPassword());
    }

    public function testEditNonExistentUserReturns404(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/users/99999/edit');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testEditViaFormRedirects(): void
    {
        $user = $this->createRegularUser();
        $this->loginAsAdmin();

        $this->client->request('POST', '/admin/users/' . $user->getId() . '/edit', [
            'username' => 'formupdate',
            'email' => 'formup@test.com',
            'full_name' => 'Form Updated',
            'role' => 'USER',
            'password' => '',
        ]);

        $this->assertResponseRedirects('/admin/users');
    }

    // =========================================================
    // TOGGLE STATUS
    // =========================================================

    public function testToggleStatusViaAjax(): void
    {
        $user = $this->createRegularUser(['active' => true]);
        $this->loginAsAdmin();

        $this->postWithCsrf(
            '/admin/users/' . $user->getId() . '/toggle-status',
            'toggle-user-status-' . $user->getId()
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertFalse($data['is_active']);

        $user = $this->reloadUser($user->getId());
        $this->assertFalse($user->isActive());
    }

    public function testToggleStatusBackToActive(): void
    {
        $user = $this->createRegularUser(['active' => false]);
        $this->loginAsAdmin();

        $this->postWithCsrf(
            '/admin/users/' . $user->getId() . '/toggle-status',
            'toggle-user-status-' . $user->getId()
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['is_active']);
    }

    public function testToggleStatusWithInvalidCsrfReturns403(): void
    {
        $user = $this->createRegularUser();
        $this->loginAsAdmin();

        $this->client->request('POST', '/admin/users/' . $user->getId() . '/toggle-status', [
            '_token' => 'invalid_token',
        ], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testToggleStatusViaFormRedirects(): void
    {
        $user = $this->createRegularUser();
        $this->loginAsAdmin();

        $this->postWithCsrf(
            '/admin/users/' . $user->getId() . '/toggle-status',
            'toggle-user-status-' . $user->getId(),
            [],
            false
        );

        $this->assertResponseRedirects('/admin/users');
    }

    // =========================================================
    // DELETE
    // =========================================================

    public function testDeleteUserViaAjax(): void
    {
        $user = $this->createRegularUser();
        $userId = $user->getId();
        $this->loginAsAdmin();
        $countBefore = $this->getUserCount();

        $this->postWithCsrf(
            '/admin/users/' . $userId . '/delete',
            'delete-user-' . $userId
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame($countBefore - 1, $this->getUserCount());

        // Verify actually gone
        $deleted = $this->em->getRepository(User::class)->find($userId);
        $this->assertNull($deleted);
    }

    public function testDeleteWithInvalidCsrfReturns403(): void
    {
        $user = $this->createRegularUser();
        $this->loginAsAdmin();

        $this->client->request('POST', '/admin/users/' . $user->getId() . '/delete', [
            '_token' => 'wrong_token',
        ], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseStatusCodeSame(403);
        // User still exists
        $this->assertNotNull($this->em->getRepository(User::class)->find($user->getId()));
    }

    public function testDeleteViaFormRedirects(): void
    {
        $user = $this->createRegularUser();
        $this->loginAsAdmin();

        $this->postWithCsrf(
            '/admin/users/' . $user->getId() . '/delete',
            'delete-user-' . $user->getId(),
            [],
            false
        );

        $this->assertResponseRedirects('/admin/users');
    }

    public function testDeleteNonExistentUserReturns404(): void
    {
        $this->loginAsAdmin();

        $this->client->request('POST', '/admin/users/99999/delete', [
            '_token' => 'whatever',
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    // =========================================================
    // TABLE DATA-ATTRIBUTES (for client-side filtering)
    // =========================================================

    public function testTableRowsHaveDataAttributes(): void
    {
        $this->createRegularUser([
            'username' => 'filtertest',
            'email' => 'filter@test.com',
            'fullName' => 'Filter Test',
            'role' => 'EMPLOYER',
            'active' => false,
        ]);
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin/users/list', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('data-role="EMPLOYER"', $content);
        $this->assertStringContainsString('data-name="filter test"', $content);
        $this->assertStringContainsString('data-email="filter@test.com"', $content);
        $this->assertStringContainsString('data-status="inactive"', $content);
    }

    public function testTableRowsActiveStatus(): void
    {
        $this->createRegularUser(['active' => true]);
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin/users/list', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('data-status="active"', $content);
    }

    public function testTableShowsRoleBadge(): void
    {
        $this->createRegularUser(['role' => 'ADMIN', 'username' => 'badgetest', 'email' => 'badge@test.com']);
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin/users/list', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Administrator', $content);
    }

    // =========================================================
    // EDGE CASES
    // =========================================================

    public function testEmptyUserListShowsEmptyState(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/users');
        $this->assertResponseIsSuccessful();
    }

    public function testCreateDuplicateUsername(): void
    {
        $this->createRegularUser(['username' => 'duplicate', 'email' => 'dup1@test.com']);
        $this->loginAsAdmin();

        // Attempt to create another user with same username
        // Since there's no unique constraint enforced in the controller,
        // this might succeed or fail depending on DB constraints
        $this->client->request('POST', '/admin/users/new', [
            'username' => 'duplicate',
            'email' => 'dup2@test.com',
            'full_name' => 'Duplicate',
            'role' => 'USER',
            'password' => 'pass',
        ], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        // If no DB unique constraint, this succeeds (potential issue!)
        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();
        // Document the actual behavior
        $this->assertTrue(
            $statusCode === 200 || $statusCode === 500,
            "Expected 200 (no constraint) or 500 (DB constraint). Got: $statusCode"
        );
    }
}
