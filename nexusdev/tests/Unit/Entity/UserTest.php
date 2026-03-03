<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Entity\Coach;
use App\Entity\Player;
use App\Entity\Order;
use App\Entity\VirtualCurrency;
use App\Entity\Content;
use App\Entity\ForumPost;
use App\Entity\Comment;
use App\Entity\Notification;
use App\Entity\ProductPurchase;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testUserInitialization(): void
    {
        $this->assertNull($this->user->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->user->getCreatedAt());
        $this->assertEquals('ACTIVE', $this->user->getStatus());
        $this->assertEquals('REGISTERED', $this->user->getUserType());
        $this->assertFalse($this->user->hasPlayer());
        $this->assertInstanceOf(ArrayCollection::class, $this->user->getOrders());
        $this->assertInstanceOf(ArrayCollection::class, $this->user->getVirtualCurrencies());
        $this->assertInstanceOf(ArrayCollection::class, $this->user->getContents());
        $this->assertInstanceOf(ArrayCollection::class, $this->user->getForumPosts());
        $this->assertInstanceOf(ArrayCollection::class, $this->user->getComments());
        $this->assertInstanceOf(ArrayCollection::class, $this->user->getNotifications());
        $this->assertInstanceOf(ArrayCollection::class, $this->user->getProductPurchases());
    }

    public function testSetUsernameAndGetUsername(): void
    {
        $username = 'testuser';
        $this->user->setUsername($username);
        
        $this->assertEquals($username, $this->user->getUsername());
    }

    public function testSetEmailAndGetEmail(): void
    {
        $email = 'test@example.com';
        $this->user->setEmail($email);
        
        $this->assertEquals($email, $this->user->getEmail());
        $this->assertEquals($email, $this->user->getUserIdentifier());
    }

    public function testSetPasswordAndGetPassword(): void
    {
        $password = 'hashedpassword';
        $this->user->setPassword($password);
        
        $this->assertEquals($password, $this->user->getPassword());
    }

    public function testSetProfilePictureAndGetProfilePicture(): void
    {
        $profilePicture = '/uploads/profile/test.jpg';
        $this->user->setProfilePicture($profilePicture);
        
        $this->assertEquals($profilePicture, $this->user->getProfilePicture());
    }

    public function testSetProfilePictureWithNull(): void
    {
        $this->user->setProfilePicture(null);
        
        $this->assertNull($this->user->getProfilePicture());
    }

    public function testSetStatusAndGetStatus(): void
    {
        $status = 'BANNED';
        $this->user->setStatus($status);
        
        $this->assertEquals($status, $this->user->getStatus());
    }

    public function testSetUserTypeAndGetType(): void
    {
        $userType = 'ADMIN';
        $this->user->setUserType($userType);
        
        $this->assertEquals($userType, $this->user->getUserType());
    }

    public function testGetRolesForRegisteredUser(): void
    {
        $this->user->setUserType('REGISTERED');
        $roles = $this->user->getRoles();
        
        $this->assertContains('ROLE_USER', $roles);
        $this->assertCount(1, $roles);
    }

    public function testGetRolesForAdminUser(): void
    {
        $this->user->setUserType('ADMIN');
        $roles = $this->user->getRoles();
        
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertCount(2, $roles);
    }

    public function testGetRolesForCoachUser(): void
    {
        $this->user->setUserType('COACH');
        $roles = $this->user->getRoles();
        
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_COACH', $roles);
        $this->assertCount(2, $roles);
    }

    public function testGetRolesForOrganizationUser(): void
    {
        $this->user->setUserType('ORGANIZATION');
        $roles = $this->user->getRoles();
        
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ORGANIZATION', $roles);
        $this->assertCount(2, $roles);
    }

    public function testGetRolesForVisitorUser(): void
    {
        $this->user->setUserType('VISITOR');
        $roles = $this->user->getRoles();
        
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_VISITOR', $roles);
        $this->assertCount(2, $roles);
    }

    public function testEraseCredentials(): void
    {
        $this->assertNull($this->user->eraseCredentials());
    }

    public function testSetCoachAndGetCoach(): void
    {
        $coach = $this->createMock(Coach::class);
        $this->user->setCoach($coach);
        
        $this->assertEquals($coach, $this->user->getCoach());
    }

    public function testSetCoachWithNull(): void
    {
        $this->user->setCoach(null);
        
        $this->assertNull($this->user->getCoach());
    }

    public function testSetPlayerAndGetPlayer(): void
    {
        $player = $this->createMock(Player::class);
        $this->user->setPlayer($player);
        
        $this->assertEquals($player, $this->user->getPlayer());
    }

    public function testSetPlayerWithNull(): void
    {
        $this->user->setPlayer(null);
        
        $this->assertNull($this->user->getPlayer());
    }

    public function testSetHasPlayerAndGetHasPlayer(): void
    {
        $this->user->setHasPlayer(true);
        
        $this->assertTrue($this->user->hasPlayer());
        
        $this->user->setHasPlayer(false);
        
        $this->assertFalse($this->user->hasPlayer());
    }

    public function testSetRiotSummonerNameAndGetRiotSummonerName(): void
    {
        $summonerName = 'TestSummoner';
        $this->user->setRiotSummonerName($summonerName);
        
        $this->assertEquals($summonerName, $this->user->getRiotSummonerName());
    }

    public function testSetRiotRegionAndGetRiotRegion(): void
    {
        $region = 'EUW';
        $this->user->setRiotRegion($region);
        
        $this->assertEquals($region, $this->user->getRiotRegion());
    }

    public function testSetRiotPuuidAndGetRiotPuuid(): void
    {
        $puuid = 'test-puuid-12345';
        $this->user->setRiotPuuid($puuid);
        
        $this->assertEquals($puuid, $this->user->getRiotPuuid());
    }

    public function testSetRiotSummonerIdAndGetRiotSummonerId(): void
    {
        $summonerId = 'test-summoner-id-12345';
        $this->user->setRiotSummonerId($summonerId);
        
        $this->assertEquals($summonerId, $this->user->getRiotSummonerId());
    }

    public function testSetRiotLastSyncAtAndGetRiotLastSyncAt(): void
    {
        $lastSyncAt = new \DateTimeImmutable('2023-01-01');
        $this->user->setRiotLastSyncAt($lastSyncAt);
        
        $this->assertEquals($lastSyncAt, $this->user->getRiotLastSyncAt());
    }

    public function testSetRiotLastSyncAtWithNull(): void
    {
        $this->user->setRiotLastSyncAt(null);
        
        $this->assertNull($this->user->getRiotLastSyncAt());
    }

    public function testSetRecentMatchesAndGetRecentMatches(): void
    {
        $matches = [
            ['gameId' => 1, 'win' => true],
            ['gameId' => 2, 'win' => false]
        ];
        $this->user->setRecentMatches($matches);
        
        $this->assertEquals($matches, $this->user->getRecentMatches());
    }

    public function testSetRecentMatchesWithNull(): void
    {
        $this->user->setRecentMatches(null);
        
        $this->assertNull($this->user->getRecentMatches());
    }

    public function testSetUnreadMessageCountAndGetUnreadMessageCount(): void
    {
        $count = 5;
        $this->user->setUnreadMessageCount($count);
        
        $this->assertEquals($count, $this->user->getUnreadMessageCount());
    }

    public function testGetUnreadMessageCountDefault(): void
    {
        $this->assertEquals(0, $this->user->getUnreadMessageCount());
    }

    public function testUserFluentInterface(): void
    {
        $this->assertInstanceOf(User::class, $this->user->setUsername('test'));
        $this->assertInstanceOf(User::class, $this->user->setEmail('test@example.com'));
        $this->assertInstanceOf(User::class, $this->user->setPassword('password'));
        $this->assertInstanceOf(User::class, $this->user->setProfilePicture('test.jpg'));
        $this->assertInstanceOf(User::class, $this->user->setCreatedAt(new \DateTimeImmutable()));
        $this->assertInstanceOf(User::class, $this->user->setStatus('ACTIVE'));
        $this->assertInstanceOf(User::class, $this->user->setUserType('REGISTERED'));
        $this->assertInstanceOf(User::class, $this->user->setCoach(null));
        $this->assertInstanceOf(User::class, $this->user->setPlayer(null));
        $this->assertInstanceOf(User::class, $this->user->setHasPlayer(true));
        $this->assertInstanceOf(User::class, $this->user->setRiotSummonerName('test'));
        $this->assertInstanceOf(User::class, $this->user->setRiotRegion('EUW'));
        $this->assertInstanceOf(User::class, $this->user->setRiotPuuid('test'));
        $this->assertInstanceOf(User::class, $this->user->setRiotSummonerId('test'));
        $this->assertInstanceOf(User::class, $this->user->setRiotLastSyncAt(new \DateTimeImmutable()));
        $this->assertInstanceOf(User::class, $this->user->setRecentMatches([]));
        $this->assertInstanceOf(User::class, $this->user->setUnreadMessageCount(0));
    }

    public function testCollectionsAreInitialized(): void
    {
        $this->assertEquals(0, $this->user->getOrders()->count());
        $this->assertEquals(0, $this->user->getVirtualCurrencies()->count());
        $this->assertEquals(0, $this->user->getContents()->count());
        $this->assertEquals(0, $this->user->getForumPosts()->count());
        $this->assertEquals(0, $this->user->getComments()->count());
        $this->assertEquals(0, $this->user->getNotifications()->count());
        $this->assertEquals(0, $this->user->getProductPurchases()->count());
    }
}
