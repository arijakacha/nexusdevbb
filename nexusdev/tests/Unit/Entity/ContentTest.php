<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Content;
use App\Entity\User;
use App\Entity\Comment;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase
{
    private Content $content;
    private User $user;

    protected function setUp(): void
    {
        $this->user = $this->createMock(User::class);
        $this->content = new Content();
        $this->content->setAuthor($this->user);
    }

    public function testContentInitialization(): void
    {
        $this->assertNull($this->content->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->content->getCreatedAt());
        $this->assertInstanceOf(ArrayCollection::class, $this->content->getComments());
        $this->assertEquals(0, $this->content->getComments()->count());
    }

    public function testSetTitleAndGetTitle(): void
    {
        $title = 'Test Content Title';
        $this->content->setTitle($title);
        
        $this->assertEquals($title, $this->content->getTitle());
    }

    public function testSetTypeAndGetType(): void
    {
        $type = 'NEWS';
        $this->content->setType($type);
        
        $this->assertEquals($type, $this->content->getType());
    }

    public function testSetBodyAndGetBody(): void
    {
        $body = 'This is the content body.';
        $this->content->setBody($body);
        
        $this->assertEquals($body, $this->content->getBody());
    }

    public function testSetImageAndGetImage(): void
    {
        $image = '/uploads/content/test-image.jpg';
        $this->content->setImage($image);
        
        $this->assertEquals($image, $this->content->getImage());
    }

    public function testSetImageWithNull(): void
    {
        $this->content->setImage(null);
        
        $this->assertNull($this->content->getImage());
    }

    public function testSetAuthorAndGetAuthor(): void
    {
        $this->assertEquals($this->user, $this->content->getAuthor());
        
        $newUser = $this->createMock(User::class);
        $this->content->setAuthor($newUser);
        
        $this->assertEquals($newUser, $this->content->getAuthor());
    }

    public function testSetCreatedAtAndGetCreatedAt(): void
    {
        $createdAt = new \DateTimeImmutable('2023-01-01');
        $this->content->setCreatedAt($createdAt);
        
        $this->assertEquals($createdAt, $this->content->getCreatedAt());
    }

    public function testSetDeletedAtAndGetDeletedAt(): void
    {
        $deletedAt = new \DateTimeImmutable('2023-12-01');
        $this->content->setDeletedAt($deletedAt);
        
        $this->assertEquals($deletedAt, $this->content->getDeletedAt());
    }

    public function testSetDeletedAtWithNull(): void
    {
        $this->content->setDeletedAt(null);
        
        $this->assertNull($this->content->getDeletedAt());
    }

    public function testAddComment(): void
    {
        $comment = $this->createMock(Comment::class);
        $comment->expects($this->once())
            ->method('setGuide')
            ->with($this->content);
        
        $this->content->addComment($comment);
        
        $this->assertTrue($this->content->getComments()->contains($comment));
        $this->assertEquals(1, $this->content->getComments()->count());
    }

    public function testAddDuplicateComment(): void
    {
        $comment = $this->createMock(Comment::class);
        $comment->expects($this->once())
            ->method('setGuide')
            ->with($this->content);
        
        $this->content->addComment($comment);
        $this->content->addComment($comment);
        
        $this->assertTrue($this->content->getComments()->contains($comment));
        $this->assertEquals(1, $this->content->getComments()->count());
    }

    public function testRemoveComment(): void
    {
        $comment = $this->createMock(Comment::class);
        $comment->expects($this->atLeastOnce())
            ->method('getGuide')
            ->willReturn($this->content);
        $comment->expects($this->atLeastOnce())
            ->method('setGuide')
            ->with($this->anything());
        
        $this->content->addComment($comment);
        $this->content->removeComment($comment);
        
        $this->assertFalse($this->content->getComments()->contains($comment));
        $this->assertEquals(0, $this->content->getComments()->count());
    }

    public function testRemoveCommentWithDifferentGuide(): void
    {
        $comment = $this->createMock(Comment::class);
        $comment->expects($this->once())
            ->method('getGuide')
            ->willReturn($this->createMock(Content::class));
        
        $this->content->addComment($comment);
        $this->content->removeComment($comment);
        
        $this->assertFalse($this->content->getComments()->contains($comment));
    }

    public function testContentFluentInterface(): void
    {
        $this->assertInstanceOf(Content::class, $this->content->setTitle('Test'));
        $this->assertInstanceOf(Content::class, $this->content->setType('NEWS'));
        $this->assertInstanceOf(Content::class, $this->content->setBody('Test body'));
        $this->assertInstanceOf(Content::class, $this->content->setImage('test.jpg'));
        $this->assertInstanceOf(Content::class, $this->content->setAuthor($this->user));
        $this->assertInstanceOf(Content::class, $this->content->setCreatedAt(new \DateTimeImmutable()));
        $this->assertInstanceOf(Content::class, $this->content->setDeletedAt(null));
        $this->assertInstanceOf(Content::class, $this->content->addComment($this->createMock(Comment::class)));
        $this->assertInstanceOf(Content::class, $this->content->removeComment($this->createMock(Comment::class)));
    }

    public function testContentWithNewsType(): void
    {
        $this->content->setType('NEWS');
        $this->assertEquals('NEWS', $this->content->getType());
    }

    public function testContentWithGuideType(): void
    {
        $this->content->setType('GUIDE');
        $this->assertEquals('GUIDE', $this->content->getType());
    }

    public function testContentWithEmptyTitle(): void
    {
        $this->content->setTitle('');
        $this->assertEquals('', $this->content->getTitle());
    }

    public function testContentWithLongTitle(): void
    {
        $longTitle = str_repeat('a', 150);
        $this->content->setTitle($longTitle);
        $this->assertEquals($longTitle, $this->content->getTitle());
    }

    public function testContentWithEmptyBody(): void
    {
        $this->content->setBody('');
        $this->assertEquals('', $this->content->getBody());
    }

    public function testContentWithHtmlBody(): void
    {
        $htmlBody = '<h1>Test Content</h1><p>This is a test with <strong>HTML</strong> formatting.</p>';
        $this->content->setBody($htmlBody);
        $this->assertEquals($htmlBody, $this->content->getBody());
    }
}
