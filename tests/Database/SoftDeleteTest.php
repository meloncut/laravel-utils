<?php
namespace Tests\Database;

use App\model\Dummy;
use Tests\TestCase;

class SoftDeleteTest extends TestCase {

    public function testSoftDelete() {
        $dummy = Dummy::factory()->create();
        $dummy->delete();
        $dummy->refresh();
        $this->assertNotNull($dummy->deleted_at);
        $this->assertEquals(1, $dummy->deleted);
    }

    public function testSoftRestore() {
        $dummy = Dummy::factory()->create();
        $dummy->delete();
        $this->assertNotNull($dummy->deleted_at);
        $this->assertEquals(1, $dummy->deleted);
    }
}