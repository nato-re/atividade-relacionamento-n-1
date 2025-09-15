<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use App\Models\Album;
use App\Models\Music;

class AlbumMusicTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function migration_albums_table_has_expected_columns()
    {
        Artisan::call('migrate');
        $this->assertTrue(Schema::hasTable('albums'));
        $this->assertTrue(Schema::hasColumns('albums', ['id', 'name', 'year', 'url_img', 'created_at', 'updated_at']));
    }

    /** @test */
    public function musics_table_has_album_id_foreign_key()
    {
        Artisan::call('migrate');
        $this->assertTrue(Schema::hasColumn('musics', 'album_id'));
        // Check foreign key constraint
        $connection = Schema::getConnection();
        $sm = $connection->getDoctrineSchemaManager();
        $foreignKeys = $sm->listTableForeignKeys('musics');
        $found = false;
        foreach ($foreignKeys as $fk) {
            if (in_array('album_id', $fk->getLocalColumns()) && $fk->getForeignTableName() === 'albums') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Chave estrangeira album_id referenciando albums não encontrada.');
    }

    /** @test */
    public function album_music_models_have_n_to_1_relationship()
    {
    $album = Album::factory()->create();
    $music = Music::factory()->create(['album_id' => $album->id]);
    $this->assertEquals($album->id, $music->album->id);
    $this->assertTrue($album->musics->contains($music));
    }

    /** @test */
    public function music_index_route_lists_album_name()
    {
        $album = Album::factory()->create();
        $music = Music::factory()->create(['album_id' => $album->id]);
        $response = $this->get('/music');
        $response->assertStatus(200);
        $response->assertSee($album->name);
        $response->assertSee($album->url_img);
    }

    /** @test */
    public function album_show_lists_its_musics()
    {
        $album = Album::factory()->create();
        $music1 = Music::factory()->create(['album_id' => $album->id]);
        $music2 = Music::factory()->create(['album_id' => $album->id]);
        $response = $this->get('/albums/' . $album->id);
        $response->assertStatus(200);
        $response->assertSee($music1->name);
        $response->assertSee($music2->name);
    }
}
