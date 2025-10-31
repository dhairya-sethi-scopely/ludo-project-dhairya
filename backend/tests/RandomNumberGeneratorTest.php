<?php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../classes/RandomNumberGenerator.php';

class RandomNumberGeneratorTest extends TestCase {

    #[Test]
    public function diceRollWithinRange(): void {
        // TC-001 & TC-004 & TC-005
        $value = RandomNumberGenerator::between(1, 6);
        $this->assertIsInt($value, "Result should be an integer");
        $this->assertGreaterThanOrEqual(1, $value, "Result should be >= 1");
        $this->assertLessThanOrEqual(6, $value, "Result should be <= 6");
    }

    #[Test]
    public function autoSwapsRangeWhenMinGreaterThanMax(): void {
        // TC-002
        $value = RandomNumberGenerator::between(6, 1); // reversed inputs
        $this->assertGreaterThanOrEqual(1, $value, "Value should still be >= 1 after swap");
        $this->assertLessThanOrEqual(6, $value, "Value should still be <= 6 after swap");
    }

    #[Test]
    public function handlesLargeRangesCorrectly(): void {
        // TC-007
        $value = RandomNumberGenerator::between(1, 1000);
        $this->assertGreaterThanOrEqual(1, $value);
        $this->assertLessThanOrEqual(1000, $value);
    }

}
