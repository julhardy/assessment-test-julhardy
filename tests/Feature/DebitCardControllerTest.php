<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards
        DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disable_at' => null
        ]);
        $response = $this->getJson("/debit-cards");
        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['number', 'type', 'expiration_date', 'disabled_at']
            ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $other = User::factory()->create();
        $odc = DebitCard::factory(2)->create([
            'user_id' => $other->id,
            'disable_at' => null
        ]);
        DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disable_at' => null
        ]);
        $response = $this->getJson("/debit-cards");
        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['number', 'type', 'expiration_date', 'disabled_at']
            ])
            ->assertJsonMissing(['id' => $odc[0]->id])
            ->assertJsonMissing(['id' => $odc[1]->id]);
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $req = ['type' => 'Mastercard'];
        $response = $this->postJson('/debit-cards', $req);
        $response->assertStatus(201)
            ->assertJsonFragment($req)
            ->assertJsonStructure(['number', 'type', 'expiration_date', 'disabled_at']);

        $this->assertDatabaseHas(
            'debit-cards',
            array_merge($req, ['user_id' => $this->user->id])
        );
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $dc = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disable_at' => null
        ]);
        $response = $this->getJson('/api/debit-cards/' . $dc->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['number', 'type', 'expiration_date', 'disabled_at']
            ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $dc = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disable_at' => null
        ]);
        $response = $this->getJson('/api/debit-cards/' . $dc->id);
        $response->assertStatus(404)
            ->assertJsonMissing(['id' => $dc->id]);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $dc = DebitCard::factory()->create(
            [
                'user_id' => $this->user->id,
                'disable_at' => now()
            ]
        );
        $response = $this->putJson('api/debit-cards/' . $dc->id, ['is_active' => true]);
        $response->assertStatus(200);

        $this->assertNull($dc->fresh()->disabled_at);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $dc = DebitCard::factory()->create(
            [
                'user_id' => $this->user->id,
                'disable_at' => null
            ]
        );
        $response = $this->putJson('api/debit-cards/' . $dc->id, ['is_active' => false]);
        $response->assertStatus(200);
        $this->assertNotNull($dc->fresh()->disabled_at);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $dc = DebitCard::factory()->create(
            [
                'user_id' => $this->user->id
            ]
        );
        $response = $this->putJson('api/debit-cards/' . $dc->id, ['disable_at' => 1]);
        $response->assertStatus(422)
            ->assertJsonValidationErrorFor(['disable_at']);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $dc = DebitCard::factory()->create(['user_id' => $this->user->id]);
        $response = $this->deleteJson('api/debit-cards/' . $dc->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('debit_cards', ['id' => $dc->id]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $dc = DebitCard::factory()->create(['user_id' => $this->user->id]);
        DebitCardTransaction::factory()->create(['debit_card_id' => $dc->id]);
        $response = $this->deleteJson('api/debit-cards/' . $dc->id);
        $response->assertStatus(400);
        $this->assertDatabaseHas('debit_cards', ['id' => $dc->id]);
    }

    // Extra bonus for extra tests :)
}
