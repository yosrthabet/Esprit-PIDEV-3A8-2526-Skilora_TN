<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Finance\Entity\JobReview;
use PHPUnit\Framework\TestCase;

class JobReviewTest extends TestCase
{
    public function testRatingClamped(): void
    {
        $review = new JobReview();
        $review->setRating(10);
        $this->assertSame(5, $review->getRating());

        $review->setRating(-1);
        $this->assertSame(1, $review->getRating());
    }

    public function testSubRatings(): void
    {
        $review = new JobReview();
        $review->setCommunicationRating(4)
               ->setQualityRating(5)
               ->setTimelinessRating(3);

        $this->assertSame(4, $review->getCommunicationRating());
        $this->assertSame(5, $review->getQualityRating());
        $this->assertSame(3, $review->getTimelinessRating());
    }

    public function testAverageRating(): void
    {
        $review = new JobReview();
        $review->setRating(4)
               ->setCommunicationRating(5)
               ->setQualityRating(3)
               ->setTimelinessRating(4);

        $this->assertSame(4.0, $review->getAverageRating());
    }

    public function testAverageRatingWithNulls(): void
    {
        $review = new JobReview();
        $review->setRating(3);
        $this->assertSame(3.0, $review->getAverageRating());
    }
}
