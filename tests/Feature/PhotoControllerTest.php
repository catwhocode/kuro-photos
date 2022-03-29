<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    // Index Test

    public function test_index_show_latest_photos()
    {
        User::factory()->create();
        $photo = Photo::factory()->create();

        $response = $this->get('/photos');
        $response->assertStatus(200)
            ->assertSee($photo->title)
            ->assertSee($photo->description)
            ->assertSee($photo->image);

        $this->assertDatabaseHas('photos', [
            'id' => $photo->id,
            'title' => $photo->title,
            'description' => $photo->description,
            'image' => $photo->image,
        ]);
    }

    public function test_index_empty()
    {
        $response = $this->get('/photos');
        $response->assertStatus(200)
            ->assertSee('Nothing to see here.');

        $this->assertDatabaseMissing('photos', []);
    }

    // Create photos test

    public function test_create_photo_return_200_but_guest_redirect_to_login()
    {
        $response = $this->get('/photos/create');
        $response->assertRedirect('login');

        $user = User::factory()->create();

        Auth::login($user);

        $response = $this->get('/photos/create');
        $response->assertStatus(200);
    }

    public function test_store_photo()
    {
        $user = User::factory()->create();
        Auth::login($user);
        $data = [
            'title' => 'Test title',
            'description' => $this->faker->text(),
            'image' => UploadedFile::fake()->image('test.png'),
        ];

        $response = $this->post('/photos', $data);
        $response->assertRedirect('/photos/1');

        $this->assertDatabaseHas('photos', [
            'title' => $data['title'],
            'description' => $data['description'],
            'image' => 'photos/' . $data['image']->hashName()
        ]);
    }

    public function test_validate_store()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $response = $this->post('/photos', []);
        $response->assertStatus(302)
            ->assertSessionHasErrors(['title', 'description', 'image']);
    }
}
